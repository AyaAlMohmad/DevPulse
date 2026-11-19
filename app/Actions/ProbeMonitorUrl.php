<?php

namespace App\Actions;

use DomainException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use LengthException;
use Psr\Http\Message\ResponseInterface;

class ProbeMonitorUrl
{
    public const MAX_RESPONSE_BYTES = 65536;

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

        try {
            $target = $this->resolveTarget->execute($url);
        } catch (DomainException $exception) {
            $error = $exception->getMessage();

            return $this->result($error === 'blocked_target' ? 'unknown' : 'down', null, null, $error);
        }

        $started = hrtime(true);
        $bytes = 0;
        $tooLarge = false;
        $httpStatus = null;
        $curlError = null;
        $error = null;
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
                'timeout' => 10,
                'connect_timeout' => 3,
                'sink' => $sink,
                'headers' => ['User-Agent' => 'DevPulse/1.0', 'Accept-Encoding' => 'identity'],
                'curl' => [
                    CURLOPT_RESOLVE => $target['resolve'],
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_FORBID_REUSE => true,
                    CURLOPT_DNS_CACHE_TIMEOUT => 0,
                ],
                'on_stats' => function (TransferStats $stats) use (&$curlError): void {
                    $data = $stats->getHandlerErrorData();
                    $curlError = is_int($data) ? $data : null;
                },
                'on_headers' => function (ResponseInterface $response) use (&$httpStatus, &$tooLarge): void {
                    $httpStatus = $response->getStatusCode();

                    if ((float) $response->getHeaderLine('Content-Length') > self::MAX_RESPONSE_BYTES) {
                        $tooLarge = true;
                        throw new LengthException('Response body limit exceeded.');
                    }
                },
            ])->setHandler(new CurlHandler)->get($target['url']);
            $httpStatus = $response->status();
            $error = $response->successful() ? null : ($response->redirect() ? 'redirect_blocked' : 'http_error');
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

        return $this->result($error === null ? 'up' : 'down', $httpStatus,
            (int) round((hrtime(true) - $started) / 1_000_000), $error);
    }

    private function result(string $status, ?int $httpStatus, ?int $responseTime, ?string $error): array
    {
        return [
            'status' => $status,
            'http_status' => $httpStatus,
            'response_time_ms' => $responseTime,
            'error_type' => $error,
        ];
    }
}
