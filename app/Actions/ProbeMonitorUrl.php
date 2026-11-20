<?php

namespace App\Actions;

use DomainException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LengthException;
use Psr\Http\Message\ResponseInterface;

class ProbeMonitorUrl
{
    public const MAX_RESPONSE_BYTES = 65536;

    public const MAX_REDIRECTS = 3;

    public const MAX_CHECK_SECONDS = 15;

    /**
     * Create a new class instance.
     */
    public function __construct(private ResolveMonitorTarget $resolveTarget)
    {
        //
    }

    public function execute(string $url): array
    {
        if (! extension_loaded('curl')) {
            return $this->result('unknown', null, null, 'curl_unavailable');
        }

        $started = hrtime(true);
        $bytes = 0;
        $httpStatus = null;
        $finalUrl = null;
        $redirects = 0;
        $seen = [];
        $requested = false;
        $following = false;
        $timings = [];
        $finish = function (string $status, ?string $error) use (&$httpStatus, &$finalUrl, &$redirects, &$requested, &$timings, $started): array {
            return $this->result($status, $httpStatus, $requested ? (int) round((hrtime(true) - $started) / 1_000_000) : null,
                $error, $finalUrl, $redirects, $timings);
        };

        while (true) {
            if ((hrtime(true) - $started) / 1_000_000_000 >= self::MAX_CHECK_SECONDS) {
                return $finish('down', 'timeout');
            }

            try {
                $target = $this->resolveTarget->execute($url);
            } catch (DomainException $exception) {
                $error = $exception->getMessage();

                return $finish($error === 'blocked_target' ? 'unknown' : 'down',
                    $following && $error === 'blocked_target' ? 'redirect_blocked' : $error);
            }

            if (isset($seen[$target['url']])) {
                return $finish('unknown', 'redirect_loop');
            }
            $seen[$target['url']] = true;
            $remaining = self::MAX_CHECK_SECONDS - (hrtime(true) - $started) / 1_000_000_000;
            if ($remaining <= 0) {
                return $finish('down', 'timeout');
            }

            $redirects += $following ? 1 : 0;
            $requested = true;
            $finalUrl = (string) (new Uri($target['url']))->withQuery('')->withFragment('');
            $hop = $this->request($target, $remaining, $bytes);
            $httpStatus = $hop['http_status'];
            $timings[] = $hop['timing'];
            if (in_array($httpStatus, [401, 403], true)) {
                return $finish('unknown', 'access_restricted');
            }
            if ($hop['error_type'] !== null) {
                return $finish($hop['error_type'] === 'response_too_large' ? 'unknown' : 'down', $hop['error_type']);
            }
            if ($httpStatus >= 200 && $httpStatus < 300) {
                return $finish('up', null);
            }
            if (! in_array($httpStatus, [301, 302, 303, 307, 308], true)) {
                return $finish($httpStatus >= 300 && $httpStatus < 400 ? 'unknown' : 'down',
                    $httpStatus >= 300 && $httpStatus < 400 ? 'redirect_blocked' : 'http_error');
            }
            if ($redirects >= self::MAX_REDIRECTS) {
                return $finish('unknown', 'redirect_limit');
            }

            $location = trim($hop['location'] ?? '');
            if ($location === '') {
                return $finish('unknown', 'redirect_missing_location');
            }
            if (strlen($location) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $location) !== 0) {
                return $finish('unknown', 'invalid_redirect');
            }
            try {
                $next = UriResolver::resolve(new Uri($target['url']), new Uri($location))->withFragment('');
            } catch (InvalidArgumentException) {
                return $finish('unknown', 'invalid_redirect');
            }
            if (parse_url($target['url'], PHP_URL_SCHEME) === 'https' && $next->getScheme() === 'http') {
                return $finish('unknown', 'redirect_blocked');
            }
            $url = (string) $next;
            $following = true;
        }
    }

    private function request(array $target, float $remaining, int &$bytes): array
    {
        $tooLarge = false;
        $httpStatus = null;
        $curlError = null;
        $error = null;
        $location = null;
        $handlerStats = [];
        $sink = FnStream::decorate(Utils::streamFor(''), [
            'write' => function (string $chunk) use (&$bytes, &$tooLarge): int {
                $bytes += strlen($chunk);

                if ($bytes > self::MAX_RESPONSE_BYTES) {
                    $tooLarge = true;
                    throw new LengthException('Response body limit exceeded.');
                }

                return strlen($chunk);
            },
        ]);

        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'cookies' => false,
                'proxy' => '',
                'verify' => true,
                'stream' => false,
                'decode_content' => false,
                'idn_conversion' => false,
                'protocols' => ['http', 'https'],
                'http_errors' => false,
                'timeout' => min(10, $remaining),
                'connect_timeout' => min(3, $remaining),
                'sink' => $sink,
                'headers' => ['User-Agent' => 'DevPulse/1.0', 'Accept-Encoding' => 'identity'],
                'curl' => [
                    CURLOPT_RESOLVE => $target['resolve'],
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_FORBID_REUSE => true,
                    CURLOPT_DNS_CACHE_TIMEOUT => 0,
                ],
                'on_stats' => function (TransferStats $stats) use (&$curlError, &$handlerStats): void {
                    $data = $stats->getHandlerErrorData();
                    $curlError = is_int($data) ? $data : null;
                    $handlerStats = $stats->getHandlerStats();
                },
                'on_headers' => function (ResponseInterface $response) use (&$httpStatus, &$tooLarge, &$bytes): void {
                    $httpStatus = $response->getStatusCode();

                    if ((float) $response->getHeaderLine('Content-Length') > self::MAX_RESPONSE_BYTES - $bytes) {
                        $tooLarge = true;
                        throw new LengthException('Response body limit exceeded.');
                    }
                },
            ])->setHandler(new CurlHandler)->get($target['url']);
            $httpStatus = $response->status();
            $locations = $response->toPsrResponse()->getHeader('Location');
            $location = count($locations) === 1 ? $locations[0] : null;
        } catch (ConnectionException) {
            $error = $tooLarge ? 'response_too_large' : match ($curlError) {
                CURLE_OPERATION_TIMEDOUT => 'timeout',
                CURLE_SSL_CACERT, CURLE_SSL_CONNECT_ERROR => 'tls_failure',
                default => 'connection_failed',
            };
        } catch (GuzzleException|LengthException) {
            $error = $tooLarge ? 'response_too_large' : 'request_failed';
        } finally {
            $sink->close();
        }

        return ['http_status' => $httpStatus, 'error_type' => $error, 'location' => $location,
            'timing' => $this->timing($target['url'], $handlerStats)];

    }

    private function timing(string $url, array $stats): array
    {
        $ms = fn (string $key): ?int => isset($stats[$key]) ? (int) round($stats[$key] * 1000) : null;
        $connect = $ms('connect_time');
        $tls = isset($stats['appconnect_time']) && $stats['appconnect_time'] > 0
            ? max(0, (int) round($stats['appconnect_time'] * 1000) - ($connect ?? 0))
            : null;

        return [
            'url' => (string) (new Uri($url))->withQuery('')->withFragment(''),
            'dns_ms' => $ms('namelookup_time'),
            'connect_ms' => $connect,
            'tls_ms' => $tls,
            'ttfb_ms' => $ms('starttransfer_time'),
            'total_ms' => $ms('total_time'),
        ];
    }

    private function result(string $status, ?int $httpStatus, ?int $responseTime, ?string $error, ?string $finalUrl = null, int $redirectCount = 0, array $timings = []): array
    {
        return [
            'status' => $status,
            'http_status' => $httpStatus,
            'response_time_ms' => $responseTime,
            'error_type' => $error,
            'final_url' => $finalUrl,
            'redirect_count' => $redirectCount,
            'timings' => $timings === [] ? null : $timings,
        ];
    }
}
