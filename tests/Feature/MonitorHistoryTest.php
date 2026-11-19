<?php

namespace Tests\Feature;

use App\Actions\SaveMonitor;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonitorHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    public function test_detail_page_and_history_are_only_available_to_the_owner(): void
    {
        $monitor = Monitor::factory()->create();
        $this->get('/monitors/'.$monitor->id)->assertRedirect('/login');
        $this->getJson('/monitors/'.$monitor->id.'/checks')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get('/monitors/'.$monitor->id)->assertForbidden();
        $this->getJson('/monitors/'.$monitor->id.'/checks')->assertForbidden();
        $this->actingAs($monitor->user)->get('/monitors/'.$monitor->id)
            ->assertInertia(fn ($page) => $page->component('Monitors/Show')
                ->where('monitor.data.id', $monitor->id)->where('checks.meta.total', 0));
        $this->getJson('/monitors/'.$monitor->id)->assertOk()->assertJsonPath('data.id', $monitor->id);
    }

    public function test_history_is_owner_scoped_and_sorted_with_stable_comparisons_across_pages(): void
    {
        $monitor = Monitor::factory()->create();
        MonitorCheck::factory()->create();
        $records = collect();
        for ($index = 1; $index <= 11; $index++) {
            $records->push(MonitorCheck::factory()->for($monitor)->create([
                'target_url' => $monitor->url, 'response_time_ms' => $index * 10, 'checked_at' => now(),
            ]));
        }
        $this->actingAs($monitor->user)->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 11)
            ->assertJsonPath('data.0.id', $records->last()->id)
            ->assertJsonPath('data.9.comparison.previous_id', $records->first()->id)
            ->assertJsonPath('data.9.comparison.response_time_delta_ms', 10);
        $this->getJson('/monitors/'.$monitor->id.'/checks?page=2')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.comparison.reason', 'first_check');
    }

    public function test_result_transitions_are_exposed_but_failed_checks_do_not_produce_latency_comparisons(): void
    {
        $monitor = Monitor::factory()->create();
        $previous = MonitorCheck::factory()->for($monitor)->create(['target_url' => $monitor->url]);
        MonitorCheck::factory()->for($monitor)->create([
            'target_url' => $monitor->url, 'status' => 'down', 'http_status' => 503,
            'error_type' => 'http_error', 'response_time_ms' => null,
        ]);
        $this->actingAs($monitor->user)->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertJsonPath('data.0.comparison.previous_id', $previous->id)
            ->assertJsonPath('data.0.comparison.status_changed', true)
            ->assertJsonPath('data.0.comparison.http_status_changed', true)
            ->assertJsonPath('data.0.comparison.error_changed', true)
            ->assertJsonPath('data.0.comparison.response_time_delta_ms', null);
    }

    public function test_legacy_and_changed_urls_are_not_assumed_to_be_the_same_target(): void
    {
        $monitor = Monitor::factory()->create();
        MonitorCheck::factory()->for($monitor)->create(['target_url' => null]);
        MonitorCheck::factory()->for($monitor)->create(['target_url' => $monitor->url]);
        MonitorCheck::factory()->for($monitor)->create(['target_url' => 'https://example.org/new']);
        $this->actingAs($monitor->user)->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertJsonPath('data.0.comparison.reason', 'different_target')
            ->assertJsonPath('data.1.comparison.reason', 'unknown_target')
            ->assertJsonPath('data.2.target_url', null);
    }

    public function test_details_can_request_checks_without_leaving_the_page_and_expose_expired_reservations(): void
    {
        Queue::fake();
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user)->post('/monitors/'.$monitor->id.'/check', ['redirect' => 'details'])
            ->assertRedirect('/monitors/'.$monitor->id);
        $this->getJson('/monitors/'.$monitor->id)->assertJsonPath('data.is_check_pending', true)
            ->assertJsonPath('data.check_reservation_expired', false);
        $this->travel(6)->minutes();
        $this->getJson('/monitors/'.$monitor->id)->assertJsonPath('data.is_check_pending', false)
            ->assertJsonPath('data.check_reservation_expired', true);
        $this->postJson('/monitors/'.$monitor->id.'/check', ['redirect' => 'https://example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('redirect');
    }

    public function test_language_switch_can_return_to_owned_details_but_not_another_users_monitor(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user)->post('/locale', [
            'locale' => 'ar', 'redirect' => 'monitors.show', 'monitor_id' => $monitor->id,
        ])->assertRedirect('/monitors/'.$monitor->id);
        $other = Monitor::factory()->create();
        $this->post('/locale', ['locale' => 'fr', 'redirect' => 'monitors.show', 'monitor_id' => $other->id])->assertNotFound();
    }

    public function test_successful_checks_report_improvements_and_unchanged_timings(): void
    {
        $monitor = Monitor::factory()->create();
        foreach ([100, 40, 40] as $milliseconds) {
            MonitorCheck::factory()->for($monitor)->create([
                'target_url' => $monitor->url, 'response_time_ms' => $milliseconds,
            ]);
        }
        $this->actingAs($monitor->user)->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertJsonPath('data.0.comparison.response_time_delta_ms', 0)
            ->assertJsonPath('data.1.comparison.response_time_delta_ms', -60);
        app(SaveMonitor::class)->execute($monitor->user, ['url' => 'https://example.org/new'], $monitor);
        $this->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertJsonPath('data.0.target_url', $monitor->url);
    }

    public function test_redirect_metadata_is_returned_without_comparing_different_final_destinations(): void
    {
        $monitor = Monitor::factory()->create();
        foreach (['first', 'second'] as $path) {
            MonitorCheck::factory()->for($monitor)->create([
                'target_url' => $monitor->url, 'final_url' => 'https://example.com/'.$path,
                'redirect_count' => 1,
            ]);
        }
        $this->actingAs($monitor->user)->getJson('/monitors/'.$monitor->id.'/checks')
            ->assertJsonPath('data.0.final_url', 'https://example.com/second')
            ->assertJsonPath('data.0.redirect_count', 1)
            ->assertJsonPath('data.0.comparison.final_url_changed', true)
            ->assertJsonPath('data.0.comparison.response_time_delta_ms', null);
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
