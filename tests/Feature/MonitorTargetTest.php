<?php

namespace Tests\Feature;

use App\Actions\ResolveHostAddresses;
use App\Actions\ResolveMonitorTarget;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MonitorTargetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Process::preventStrayProcesses();
    }

    #[DataProvider('unsafeTargets')]
    public function test_unsafe_targets_are_rejected_without_network_access(string $url): void
    {
        $this->mock(ResolveHostAddresses::class)->shouldNotReceive('__invoke');
        $this->expectException(\DomainException::class);
        app(ResolveMonitorTarget::class)->execute($url);
    }

    public static function unsafeTargets(): array
    {
        return array_map(fn (string $url): array => [$url], [
            'http://127.0.0.1', 'http://10.0.0.1', 'http://172.16.0.1', 'http://192.168.1.1',
            'http://169.254.169.254/latest/meta-data', 'http://100.100.100.200', 'http://168.63.129.16',
            'http://0.0.0.0', 'http://192.0.2.1', 'http://198.18.0.1', 'http://224.0.0.1',
            'http://[::1]', 'http://[fc00::1]', 'http://[fe80::1]', 'http://[::ffff:127.0.0.1]',
            'http://[64:ff9b::7f00:1]', 'http://[2001:db8::1]', 'http://[2002:7f00:1::]',
            'http://2130706433', 'http://0177.0.0.1', 'http://0x7f000001',
            'http://localhost', 'http://service.internal', 'file:///etc/passwd', 'ftp://example.com',
            'https://user:secret@example.com', 'http://example.com:8080', 'https://example.com:80',
            'http://example.com#fragment', "http://example.com/\r\nHost:localhost", 'http://example.com\\@127.0.0.1',
        ]);
    }

    public function test_dns_results_are_all_checked_and_one_public_ip_is_pinned(): void
    {
        $this->mock(ResolveHostAddresses::class)->shouldReceive('__invoke')->with('api.example.com')
            ->andReturn(['93.184.216.34', '2606:4700:4700::1111']);
        $target = app(ResolveMonitorTarget::class)->execute('https://api.example.com/health');
        $this->assertSame(['api.example.com:443:93.184.216.34'], $target['resolve']);
        $this->assertSame('https://api.example.com/health', $target['url']);
    }

    public function test_mixed_public_and_private_dns_is_rejected(): void
    {
        $this->mock(ResolveHostAddresses::class)->shouldReceive('__invoke')
            ->andReturn(['93.184.216.34', '127.0.0.1']);
        $this->expectException(\DomainException::class);
        app(ResolveMonitorTarget::class)->execute('https://api.example.com');
    }

    public function test_public_ip_literals_do_not_need_dns(): void
    {
        $this->mock(ResolveHostAddresses::class)->shouldNotReceive('__invoke');
        foreach (['https://1.1.1.1/health', 'https://[2606:4700:4700::1111]/health'] as $url) {
            $this->assertSame([], app(ResolveMonitorTarget::class)->execute($url)['resolve']);
        }
    }

    public function test_dns_runs_in_a_bounded_subprocess(): void
    {
        Process::fake([
            '*' => Process::result(output: '["93.184.216.34","2606:4700:4700::1111"]'),
        ]);
        $this->assertCount(2, app(ResolveHostAddresses::class)('api.example.com'));
        Process::assertRan(fn ($process) => $process->timeout === 5
            && is_array($process->command) && end($process->command) === 'api.example.com');
    }

    public function test_empty_dns_results_are_a_controlled_failure(): void
    {
        Process::fake(['*' => Process::result(output: '[]')]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('dns_failure');
        app(ResolveHostAddresses::class)('api.example.com');
    }

    public function test_dns_timeout_is_reported_without_process_details(): void
    {
        $process = new \Symfony\Component\Process\Process([PHP_BINARY, '-r', '']);
        $process->setTimeout(5);
        $original = new \Symfony\Component\Process\Exception\ProcessTimedOutException(
            $process, \Symfony\Component\Process\Exception\ProcessTimedOutException::TYPE_GENERAL,
        );
        $exception = new ProcessTimedOutException(
            $original, Process::result(),
        );
        Process::fake(fn () => throw $exception);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('dns_timeout');
        app(ResolveHostAddresses::class)('api.example.com');
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
