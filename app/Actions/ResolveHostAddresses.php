<?php

namespace App\Actions;

use DomainException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use JsonException;

class ResolveHostAddresses
{
    /**
     * Create a new class instance.
     */
    public function __construct(private int $timeout = 5)
    {
        //
    }

    /**
     * Invoke the class instance.
     */
    public function __invoke(string $host): array
    {
        $script = <<<'PHP'
$addresses = @gethostbynamel($argv[1]) ?: [];
foreach (@dns_get_record($argv[1], DNS_AAAA) ?: [] as $record) {
    if (isset($record['ipv6'])) {
        $addresses[] = $record['ipv6'];
    }
}
echo json_encode(array_values(array_unique($addresses)), JSON_THROW_ON_ERROR);
PHP;

        try {
            $result = Process::timeout($this->timeout)->run([PHP_BINARY, '-r', $script, $host]);
        } catch (ProcessTimedOutException) {
            throw new DomainException('dns_timeout');
        }

        try {
            $addresses = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new DomainException('dns_failure');
        }

        if (! $result->successful() || ! is_array($addresses) || $addresses === []) {
            throw new DomainException('dns_failure');
        }

        return $addresses;
    }
}
