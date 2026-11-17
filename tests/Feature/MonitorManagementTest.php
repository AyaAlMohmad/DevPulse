<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_monitors(): void
    {
        $this->getJson('/monitors')->assertUnauthorized();
        $this->postJson('/monitors', [])->assertUnauthorized();
    }

    public function test_owner_can_create_and_list_only_their_monitors(): void
    {
        $user = User::factory()->create();
        Monitor::factory()->create();

        $this->actingAs($user)->postJson('/monitors', [
            'name' => 'Production API',
            'url' => 'https://example.com/health',
            'interval_seconds' => 60,
            'user_id' => 999,
            'status' => 'up',
        ])->assertCreated()->assertJsonPath('data.status', 'unknown');

        $this->assertDatabaseHas('monitors', ['user_id' => $user->id, 'name' => 'Production API']);
        $this->getJson('/monitors')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_owner_can_update_pause_resume_and_delete_a_monitor(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user);

        $this->getJson('/monitors/'.$monitor->id)->assertOk();
        $this->patchJson('/monitors/'.$monitor->id, [
            'name' => 'Updated API',
            'url' => 'https://example.com/status',
            'interval_seconds' => 300,
        ])->assertOk()->assertJsonPath('data.name', 'Updated API');
        $this->patchJson('/monitors/'.$monitor->id.'/pause')->assertOk()
            ->assertJsonPath('data.is_paused', true)->assertJsonPath('data.next_check_at', null);
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk()
            ->assertJsonPath('data.is_paused', false);
        $this->assertNotNull($monitor->fresh()->next_check_at);

        $check = MonitorCheck::factory()->for($monitor)->create();
        $this->deleteJson('/monitors/'.$monitor->id)->assertNoContent();
        $this->assertDatabaseMissing('monitor_checks', ['id' => $check->id]);
    }

    public function test_other_users_cannot_access_or_change_a_monitor(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->getJson('/monitors/'.$monitor->id)->assertForbidden();
        $this->patchJson('/monitors/'.$monitor->id, [])->assertForbidden();
        $this->patchJson('/monitors/'.$monitor->id.'/pause')->assertForbidden();
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertForbidden();
        $this->deleteJson('/monitors/'.$monitor->id)->assertForbidden();
    }

    public function test_monitor_configuration_is_validated(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/monitors', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'url', 'interval_seconds']);
        $this->postJson('/monitors', [
            'name' => 'Invalid',
            'url' => 'ftp://example.com',
            'interval_seconds' => 5,
        ])->assertUnprocessable()->assertJsonValidationErrors(['url', 'interval_seconds']);
    }

    public function test_url_changes_reset_latest_health_without_deleting_history(): void
    {
        $monitor = Monitor::factory()->create([
            'status' => 'down',
            'last_http_status' => 503,
            'last_response_time_ms' => 250,
            'last_checked_at' => now(),
        ]);
        $check = MonitorCheck::factory()->for($monitor)->create();

        $this->actingAs($monitor->user)->patchJson('/monitors/'.$monitor->id, [
            'url' => 'https://example.org/health',
            'user_id' => User::factory()->create()->id,
            'is_paused' => true,
            'status' => 'up',
        ])->assertOk()->assertJsonPath('data.status', 'unknown')
            ->assertJsonPath('data.last_http_status', null)
            ->assertJsonPath('data.last_response_time_ms', null)
            ->assertJsonPath('data.last_checked_at', null)
            ->assertJsonPath('data.is_paused', false);

        $this->assertSame($monitor->user_id, $monitor->fresh()->user_id);
        $this->assertDatabaseHas('monitor_checks', ['id' => $check->id]);
    }

    public function test_pausing_preserves_health_and_resume_is_idempotent(): void
    {
        $monitor = Monitor::factory()->create(['status' => 'down']);
        $this->actingAs($monitor->user);
        $this->patchJson('/monitors/'.$monitor->id.'/pause')->assertOk()
            ->assertJsonPath('data.status', 'down');
        $this->patchJson('/monitors/'.$monitor->id, ['interval_seconds' => 300])->assertOk()
            ->assertJsonPath('data.next_check_at', null);
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk();
        $nextCheckAt = $monitor->fresh()->next_check_at->toIso8601String();
        $this->travel(10)->seconds();
        $this->patchJson('/monitors/'.$monitor->id.'/resume')->assertOk()
            ->assertJsonPath('data.next_check_at', $nextCheckAt);
    }

    public function test_update_rejects_invalid_configuration_and_missing_monitors_return_404(): void
    {
        $monitor = Monitor::factory()->create();
        $this->actingAs($monitor->user)->patchJson('/monitors/'.$monitor->id, [
            'name' => '',
            'url' => 'invalid',
            'interval_seconds' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'url', 'interval_seconds']);
        $this->getJson('/monitors/999999')->assertNotFound();
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
