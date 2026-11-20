<?php

namespace Tests\Feature;

use App\Actions\CheckMonitor;
use App\Actions\ResolveHostAddresses;
use App\Actions\SaveMonitor;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MonitorHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->mock(ResolveHostAddresses::class)->shouldReceive('__invoke')
            ->andReturn(['93.184.216.34']);
    }

    private function pendingMonitor(): Monitor
    {
        return Monitor::factory()->create([
            'url' => 'https://api.example.com/health',
            'check_token' => (string) Str::uuid(),
            'check_requested_at' => now(),
        ]);
    }

    private function runCheck(Monitor $monitor): void
    {
        (new RunMonitorCheck($monitor->id, $monitor->check_token))
            ->handle(app(CheckMonitor::class));
    }

    public function test_success_is_recorded_and_transport_safeguards_are_enforced(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function ($request, array $options) {
            $this->assertSame('GET', $request->method());
            $this->assertFalse($options['allow_redirects']);
            $this->assertFalse($options['stream']);
            $this->assertFalse($options['cookies']);
            $this->assertFalse($options['decode_content']);
            $this->assertTrue($options['verify']);
            $this->assertSame('', $options['proxy']);
            $this->assertSame(10, $options['timeout']);
            $this->assertSame(3, $options['connect_timeout']);
            $this->assertSame(['api.example.com:443:93.184.216.34'], $options['curl'][CURLOPT_RESOLVE]);
            $this->assertTrue($options['curl'][CURLOPT_FRESH_CONNECT]);
            $this->assertTrue($options['curl'][CURLOPT_FORBID_REUSE]);
            $factory = new CurlFactory(0);
            $handle = $factory->create(new Request('GET', $request->url()), $options);
            $factory->release($handle);

            return Http::response('not stored', 204);
        });
        $this->runCheck($monitor);
        $monitor->refresh();
        $this->assertSame('up', $monitor->status);
        $this->assertSame(204, $monitor->last_http_status);
        $this->assertSame($monitor->url, $monitor->checks()->sole()->target_url);
        $this->assertNotNull($monitor->last_response_time_ms);
        $this->assertNull($monitor->check_token);
        $this->assertSame(1, $monitor->checks()->count());
        $this->assertNull($monitor->checks()->sole()->error_type);
        $timings = $monitor->checks()->sole()->timings;
        $this->assertCount(1, $timings);
        $this->assertSame('https://api.example.com/health', $timings[0]['url']);
        $this->assertTrue($monitor->next_check_at->isFuture());
    }

    public function test_relative_redirects_are_followed_and_the_final_result_is_saved(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fakeSequence()->push('', 307, ['Location' => '/admin/expenses?token=private#section'])->push('', 200);
        $this->runCheck($monitor);
        $this->assertSame('up', $monitor->fresh()->status);
        $this->assertSame(200, $monitor->fresh()->last_http_status);
        $this->assertSame('https://api.example.com/admin/expenses', $monitor->fresh()->last_final_url);
        $this->assertSame(1, $monitor->fresh()->last_redirect_count);
        $this->assertSame(1, $monitor->checks()->sole()->redirect_count);
        $this->assertSame($monitor->fresh()->last_final_url, $monitor->checks()->sole()->final_url);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.com/admin/expenses?token=private');
        Http::assertSentCount(2);
    }

    public function test_cross_host_redirects_are_resolved_and_pinned_without_browser_credentials(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function ($request, array $options) {
            $host = parse_url($request->url(), PHP_URL_HOST);
            $this->assertSame([$host.':443:93.184.216.34'], $options['curl'][CURLOPT_RESOLVE]);
            $this->assertFalse($options['allow_redirects']);
            $this->assertFalse($options['cookies']);
            $this->assertTrue($options['verify']);
            $this->assertFalse($request->hasHeader('Authorization'));
            $this->assertFalse($request->hasHeader('Cookie'));
            $this->assertFalse($request->hasHeader('Referer'));

            return $host === 'api.example.com' ? Http::response('', 308, ['Location' => '//other.example.com/result']) : Http::response('', 204);
        });
        $this->runCheck($monitor);
        $this->assertSame('up', $monitor->fresh()->status);
        $this->assertSame('https://other.example.com/result', $monitor->checks()->sole()->final_url);
        Http::assertSentCount(2);
    }

    public function test_protected_responses_are_unknown_not_down_or_successful(): void
    {
        Http::fakeSequence()->push('', 401)->push('', 403);
        foreach ([401, 403] as $status) {
            $monitor = $this->pendingMonitor();
            $this->runCheck($monitor);
            $this->assertSame('unknown', $monitor->fresh()->status);
            $this->assertSame($status, $monitor->fresh()->last_http_status);
            $this->assertSame('access_restricted', $monitor->checks()->sole()->error_type);
        }
    }

    #[DataProvider('redirectFailures')]
    public function test_redirect_limits_loops_and_missing_locations_have_clear_unknown_results(string $error): void
    {
        $counter = 0;
        Http::fake(function () use ($error, &$counter) {
            $counter++;
            $location = match ($error) {
                'redirect_limit' => '/hop-'.$counter,
                'redirect_loop' => '/health',
                'redirect_missing_location' => '',
                'redirect_blocked' => 'http://api.example.com/plaintext',
                default => 'https://api.example.com/unsafe\\value',
            };

            return Http::response('', 307, ['Location' => $location]);
        });
        $monitor = $this->pendingMonitor();
        $this->runCheck($monitor);
        $this->assertSame('unknown', $monitor->fresh()->status);
        $this->assertSame($error, $monitor->checks()->sole()->error_type);
        $this->assertSame($error === 'redirect_limit' ? 4 : 1, $counter);
    }

    public static function redirectFailures(): array
    {
        return [['redirect_limit'], ['redirect_loop'], ['redirect_missing_location'], ['invalid_redirect'], ['redirect_blocked']];
    }

    public function test_each_redirect_destination_is_checked_again_for_private_dns_addresses(): void
    {
        $this->mock(ResolveHostAddresses::class)->shouldReceive('__invoke')->twice()
            ->andReturn(['93.184.216.34'], ['127.0.0.1']);
        Http::fakeSequence()->push('', 307, ['Location' => 'https://other.example.com/private']);
        $monitor = $this->pendingMonitor();
        $this->runCheck($monitor);
        $this->assertSame('redirect_blocked', $monitor->checks()->sole()->error_type);
        $this->assertSame('unknown', $monitor->fresh()->status);
        $this->assertSame(0, $monitor->fresh()->last_redirect_count);
        Http::assertSentCount(1);
    }

    #[DataProvider('unsafeRedirectDestinations')]
    public function test_redirects_cannot_reach_private_metadata_or_other_protocols(string $location): void
    {
        Http::fakeSequence()->push('', 307, ['Location' => $location]);
        $monitor = $this->pendingMonitor();
        $this->runCheck($monitor);
        $this->assertSame('unknown', $monitor->fresh()->status);
        $this->assertSame('redirect_blocked', $monitor->checks()->sole()->error_type);
        Http::assertSentCount(1);
    }

    public static function unsafeRedirectDestinations(): array
    {
        return [
            ['https://127.0.0.1/private'], ['https://169.254.169.254/metadata'],
            ['https://user:password@other.example.com'], ['file:///etc/passwd'],
            ['https://internal.test/private'], ['https://api.example.com:8443/other'],
        ];
    }

    #[DataProvider('redirectStatuses')]
    public function test_standard_redirect_status_codes_are_followed_without_assuming_a_login_path(int $status): void
    {
        Http::fakeSequence()->push('', $status, ['Location' => '/catalog'])->push('', 200);
        $monitor = $this->pendingMonitor();
        $this->runCheck($monitor);
        $this->assertSame('up', $monitor->fresh()->status);
        $this->assertSame('https://api.example.com/catalog', $monitor->fresh()->last_final_url);
        Http::assertSentCount(2);
        app(SaveMonitor::class)->execute($monitor->user, ['url' => 'https://example.org/new'], $monitor);
        $this->assertNull($monitor->fresh()->last_final_url);
        $this->assertNull($monitor->fresh()->last_redirect_count);
        $this->assertSame(1, $monitor->checks()->sole()->redirect_count);
    }

    public static function redirectStatuses(): array
    {
        return [[301], [302], [303], [307], [308]];
    }

    public function test_redirect_body_limit_is_shared_across_all_hops(): void
    {
        $counter = 0;
        Http::fake(function ($request, array $options) use (&$counter) {
            $counter++;
            $options['sink']->write(str_repeat('a', $counter === 1 ? 40000 : 30000));

            return Http::response('', 302, ['Location' => '/next']);
        });
        $monitor = $this->pendingMonitor();
        $this->runCheck($monitor);
        $this->assertSame('response_too_large', $monitor->checks()->sole()->error_type);
        $this->assertSame(2, $counter);
    }

    public function test_unsafe_redirects_are_blocked_and_404_and_503_are_down(): void
    {
        Http::fakeSequence()
            ->push('', 302, ['Location' => 'http://127.0.0.1'])->push('', 404)->push('', 503);
        foreach ([302, 404, 503] as $status) {
            $monitor = $this->pendingMonitor();
            $this->runCheck($monitor);
            $this->assertSame($status === 302 ? 'unknown' : 'down', $monitor->fresh()->status);
            $this->assertSame($status, $monitor->checks()->sole()->http_status);
        }
        Http::assertSentCount(3);
    }

    public function test_connection_failure_is_recorded_without_exception_details(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(fn () => throw new ConnectionException('secret diagnostic'));
        $this->runCheck($monitor);
        $this->assertSame('down', $monitor->fresh()->status);
        $this->assertSame('connection_failed', $monitor->checks()->sole()->error_type);
        $this->assertNull($monitor->checks()->sole()->http_status);
    }

    public function test_unsafe_targets_do_not_send_http_and_remain_unknown(): void
    {
        $monitor = $this->pendingMonitor();
        $monitor->url = 'http://169.254.169.254';
        $monitor->save();
        Http::fake();
        $this->runCheck($monitor);
        Http::assertNothingSent();
        $this->assertSame('unknown', $monitor->fresh()->status);
        $this->assertSame('blocked_target', $monitor->checks()->sole()->error_type);
        $this->assertNull($monitor->fresh()->last_response_time_ms);
    }

    public function test_oversized_bodies_are_aborted_before_buffering(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function ($request, array $options) {
            $options['on_headers'](new Response(200));
            $this->assertSame(65536, $options['sink']->write(str_repeat('a', 65536)));
            $this->assertSame(0, $options['sink']->getSize());
            $options['sink']->write('overflow');

            return Http::response('', 200);
        });
        $this->runCheck($monitor);
        $this->assertSame('response_too_large', $monitor->checks()->sole()->error_type);
        $this->assertSame('unknown', $monitor->fresh()->status);
    }

    public function test_duplicate_job_delivery_does_not_record_or_send_twice(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(['*' => Http::response('', 200)]);
        $this->runCheck($monitor);
        $this->runCheck($monitor);
        $this->assertSame(1, $monitor->checks()->count());
        Http::assertSentCount(1);
    }

    public function test_result_is_discarded_when_url_changes_away_and_back_during_the_check(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function () use ($monitor) {
            $save = app(SaveMonitor::class);
            $save->execute($monitor->user, ['url' => 'https://example.org'], $monitor);
            $save->execute($monitor->user, ['url' => $monitor->url], $monitor);

            return Http::response('', 200);
        });
        $this->runCheck($monitor);
        $this->assertSame(0, $monitor->checks()->count());
        $this->assertSame('unknown', $monitor->fresh()->status);
    }

    public function test_paused_deleted_and_expired_jobs_skip_network_calls(): void
    {
        Http::fake();
        $paused = $this->pendingMonitor();
        $paused->is_paused = true;
        $paused->save();
        $this->runCheck($paused);
        $deleted = $this->pendingMonitor();
        $deleted->delete();
        $this->runCheck($deleted);
        $expired = $this->pendingMonitor();
        $expired->check_requested_at = now()->subMinutes(6);
        $expired->save();
        $this->runCheck($expired);
        Http::assertNothingSent();
        $this->assertDatabaseCount('monitor_checks', 0);
    }

    #[DataProvider('transportErrors')]
    public function test_transport_failures_have_safe_error_categories(int $errno, string $error): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function ($request, array $options) use ($errno) {
            $options['on_stats'](new TransferStats(new Request('GET', $request->url()), null, 0.01, $errno));
            throw new ConnectionException('private network diagnostic');
        });
        $this->runCheck($monitor);
        $this->assertSame($error, $monitor->checks()->sole()->error_type);
        $this->assertNull($monitor->fresh()->check_token);
    }

    public static function transportErrors(): array
    {
        return [[28, 'timeout'], [60, 'tls_failure'], [35, 'tls_failure'], [7, 'connection_failed']];
    }

    public function test_large_content_length_is_rejected_before_reading_the_body(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function ($request, array $options) {
            $options['on_headers'](new Response(200, ['Content-Length' => '1000000']));
            $this->fail('The response body should not be read.');
        });
        $this->runCheck($monitor);
        $this->assertSame('response_too_large', $monitor->checks()->sole()->error_type);
    }

    public function test_pausing_and_resuming_during_a_request_discards_its_result(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(function () use ($monitor) {
            $this->actingAs($monitor->user)->patchJson('/monitors/'.$monitor->id.'/pause')->assertOk();
            $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk();

            return Http::response('', 200);
        });
        $this->runCheck($monitor);
        $this->assertSame(0, $monitor->checks()->count());
        $this->assertSame('unknown', $monitor->fresh()->status);
    }

    public function test_unexpected_worker_errors_release_the_reservation(): void
    {
        $monitor = $this->pendingMonitor();
        $check = $this->mock(CheckMonitor::class);
        $check->shouldReceive('execute')->andThrow(new \RuntimeException('Worker failure'));
        try {
            (new RunMonitorCheck($monitor->id, $monitor->check_token))->handle($check);
            $this->fail('Expected worker failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Worker failure', $exception->getMessage());
        }
        $this->assertNull($monitor->fresh()->check_token);
    }

    public function test_worker_lock_defers_overlapping_jobs_without_sending_another_request(): void
    {
        $monitor = $this->pendingMonitor();
        Http::fake(['*' => Http::response('', 200)]);
        $job = new RunMonitorCheck($monitor->id, $monitor->check_token);
        $middleware = $job->middleware()[0];
        $lock = Cache::lock($middleware->getLockKey($job), 60);
        $this->assertTrue($lock->get());
        try {
            $middleware->handle($job, fn ($job) => $job->handle(app(CheckMonitor::class)));
            Http::assertNothingSent();
            $this->assertNotNull($monitor->fresh()->check_token);
        } finally {
            $lock->release();
        }
        $middleware->handle($job, fn ($job) => $job->handle(app(CheckMonitor::class)));
        Http::assertSentCount(1);
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
