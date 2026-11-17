<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MonitorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_is_scoped_to_the_owner_and_excludes_paused_monitors_from_health_totals(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->for($user)->create(['status' => 'up', 'last_response_time_ms' => 100]);
        Monitor::factory()->for($user)->create(['status' => 'down', 'last_response_time_ms' => 300]);
        Monitor::factory()->for($user)->create();
        Monitor::factory()->for($user)->create(['is_paused' => true, 'status' => 'down', 'last_response_time_ms' => 900]);
        Monitor::factory()->create(['status' => 'up', 'last_response_time_ms' => 9000]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('monitors.data', 4)
            ->where('stats.total', 4)
            ->where('stats.up', 1)
            ->where('stats.down', 1)
            ->where('stats.unknown', 1)
            ->where('stats.paused', 1)
            ->where('stats.average_response_time_ms', 200)
            ->has('monitors.data.0.links.update'));
    }

    public function test_filters_are_server_side_and_do_not_change_overall_statistics(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->for($user)->create(['name' => 'Payments', 'status' => 'down']);
        Monitor::factory()->for($user)->create(['name' => 'Payments paused', 'status' => 'down', 'is_paused' => true]);
        Monitor::factory()->for($user)->create(['name' => 'Search', 'status' => 'up']);
        Monitor::factory()->create(['name' => 'Payments', 'status' => 'down']);

        $this->actingAs($user)->get('/dashboard?search=Payments&status=down')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total', 3)->has('monitors.data', 1)
            ->where('monitors.data.0.name', 'Payments')->where('filters.status', 'down'));
        $this->getJson('/monitors?status=paused')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Payments paused');
    }

    public function test_dashboard_paginates_and_empty_response_times_are_not_reported_as_zero(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->count(21)->for($user)->create();
        $this->actingAs($user)->get('/dashboard?page=2')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('monitors.data', 1)->where('monitors.meta.total', 21)
            ->where('stats.average_response_time_ms', null));
    }

    public function test_browser_mutations_redirect_and_preserve_json_behavior(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/monitors', [
            'name' => 'API', 'url' => 'https://example.com/health', 'interval_seconds' => 60,
        ])->assertRedirect('/dashboard')->assertSessionHas('success');
        $monitor = $user->monitors()->sole();
        $this->patch('/monitors/'.$monitor->id, ['name' => 'Updated'])->assertRedirect('/dashboard');
        $this->patch('/monitors/'.$monitor->id.'/pause')->assertRedirect('/dashboard');
        $this->patch('/monitors/'.$monitor->id.'/resume')->assertRedirect('/dashboard');
        $this->delete('/monitors/'.$monitor->id)->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/monitors?status=invalid&page=0')
            ->assertUnprocessable()->assertJsonValidationErrors(['status', 'page']);
    }

    public function test_inertia_protocol_returns_page_data_and_redirects_after_mutations(): void
    {
        $monitor = Monitor::factory()->create();
        $initial = $this->actingAs($monitor->user)->get('/dashboard');
        $headers = [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $initial->viewData('page')['version'] ?? '',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html,application/xhtml+xml',
        ];
        $this->get('/dashboard', $headers)->assertOk()->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Dashboard')->assertJsonCount(1, 'props.monitors.data');
        $this->patch('/monitors/'.$monitor->id, ['name' => 'Inertia update'], $headers)
            ->assertStatus(303)->assertRedirect('/dashboard');
        $this->get('/dashboard', $headers)->assertJsonPath('props.flash.success', 'Monitor updated.');
    }

    public function test_empty_workspace_has_zero_counts_and_null_latency(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total', 0)->where('stats.up', 0)->where('stats.down', 0)
            ->where('stats.paused', 0)->where('stats.unknown', 0)
            ->where('stats.average_response_time_ms', null)->has('monitors.data', 0));
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
