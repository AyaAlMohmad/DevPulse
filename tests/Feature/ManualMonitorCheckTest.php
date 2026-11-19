<?php

namespace Tests\Feature;

use App\Actions\CheckMonitor;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ManualMonitorCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_only_the_owner_can_queue_a_check(): void
    {
        $monitor = Monitor::factory()->create();
        $this->postJson('/monitors/'.$monitor->id.'/check')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/monitors/'.$monitor->id.'/check')->assertForbidden();
        $this->actingAs($monitor->user)->postJson('/monitors/'.$monitor->id.'/check')
            ->assertAccepted()->assertJsonPath('data.is_check_pending', true);
        $this->assertNotNull($monitor->fresh()->check_token);
        Queue::assertPushed(RunMonitorCheck::class, fn ($job) => $job->monitorId === $monitor->id
            && $job->connection === 'database' && $job->queue === 'monitor-checks');
        Http::assertNothingSent();
    }

    public function test_paused_and_already_pending_monitors_are_rejected(): void
    {
        $monitor = Monitor::factory()->create(['is_paused' => true]);
        $this->actingAs($monitor->user)->postJson('/monitors/'.$monitor->id.'/check')
            ->assertUnprocessable()->assertJsonValidationErrors('check');
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk();
        $this->postJson('/monitors/'.$monitor->id.'/check')->assertAccepted();
        $this->postJson('/monitors/'.$monitor->id.'/check')->assertUnprocessable()->assertJsonValidationErrors('check');
        Queue::assertPushed(RunMonitorCheck::class, 1);
    }

    public function test_browser_requests_redirect_with_a_queue_confirmation(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user)->post('/monitors/'.$monitor->id.'/check')
            ->assertRedirect('/dashboard')->assertSessionHas('success');
    }

    public function test_stale_reservations_can_be_replaced_without_old_jobs_clearing_the_new_token(): void
    {
        $monitor = Monitor::factory()->create([
            'check_token' => 'old-token', 'check_requested_at' => now()->subMinutes(6),
        ]);
        $this->actingAs($monitor->user)->postJson('/monitors/'.$monitor->id.'/check')->assertAccepted();
        $newToken = $monitor->fresh()->check_token;
        $this->assertNotSame('old-token', $newToken);
        (new RunMonitorCheck($monitor->id, 'old-token'))->handle(app(CheckMonitor::class));
        $this->assertSame($newToken, $monitor->fresh()->check_token);
    }

    public function test_pausing_or_changing_the_url_invalidates_pending_work(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user)->postJson('/monitors/'.$monitor->id.'/check')->assertAccepted();
        $this->patchJson('/monitors/'.$monitor->id.'/pause')->assertOk()->assertJsonPath('data.is_check_pending', false);
        $this->assertNull($monitor->fresh()->check_token);
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk();
        $this->postJson('/monitors/'.$monitor->id.'/check')->assertAccepted();
        $this->patchJson('/monitors/'.$monitor->id, ['url' => 'https://example.org/health'])
            ->assertOk()->assertJsonPath('data.is_check_pending', false);
    }

    public function test_manual_checks_are_rate_limited(): void
    {
        $user = User::factory()->create();
        $monitors = Monitor::factory()->count(11)->for($user)->create();
        $this->actingAs($user);
        foreach ($monitors->take(10) as $monitor) {
            $this->postJson('/monitors/'.$monitor->id.'/check')->assertAccepted();
        }
        $this->postJson('/monitors/'.$monitors->last()->id.'/check')->assertStatus(429);
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
