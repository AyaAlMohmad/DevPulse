<?php

namespace App\Actions;

use DomainException;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\IpUtils;

class ResolveMonitorTarget
{
    /**
     * Create a new class instance.
     */
    public function __construct(private ResolveHostAddresses $resolveHost, private bool $allowPrivateTargets = false)
    {
        //
    }

    public function execute(string $url): array
    {
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url) !== 0) {
            throw new DomainException('blocked_target');
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($scheme, ['http', 'https'], true) || $host === ''
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || $port !== ($scheme === 'https' ? 443 : 80)) {
            throw new DomainException('blocked_target');
        }

        $literal = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (! $literal && (! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || preg_match('/\.[a-z][a-z0-9-]*\z/', $host) !== 1
            || (! $this->allowPrivateTargets
                && preg_match('/\.(localhost|local|internal|lan|home|test|invalid)\z/', $host) === 1))) {
            throw new DomainException('blocked_target');
        }

        $addresses = $literal ? [$host] : ($this->resolveHost)($host);

        if ($addresses === []) {
            throw new DomainException('dns_failure');
        }

        foreach ($addresses as $address) {
            if (! is_string($address) || filter_var($address, FILTER_VALIDATE_IP) === false
                || (! $this->allowPrivateTargets && ! $this->isPublicAddress($address))) {
                throw new DomainException('blocked_target');
            }
        }

        $ip = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];

        return [
            'url' => (string) (new Uri($url))->withScheme($scheme)
                ->withHost(str_contains($host, ':') ? '['.$host.']' : $host),
            'resolve' => $literal ? [] : ["{$host}:{$port}:{$ip}"],
        ];
    }

    private function isPublicAddress(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        if (str_contains($address, ':')) {
            return IpUtils::checkIp($address, '2000::/3') && ! IpUtils::checkIp($address, [
                '2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20',
            ]);
        }

        return ! IpUtils::checkIp($address, [
            '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8',
            '169.254.0.0/16', '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24',
            '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
            '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4', '168.63.129.16/32',
        ]);
    }
}
