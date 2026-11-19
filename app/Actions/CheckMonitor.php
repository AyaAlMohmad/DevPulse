<?php

namespace App\Actions;

use App\Models\Monitor;
use Illuminate\Database\DatabaseManager;

class CheckMonitor
{
    /**
     * Create a new class instance.
     */
    public function __construct(private ProbeMonitorUrl $probe, private DatabaseManager $database)
    {
        //
    }

    public function execute(int $monitorId, string $token): void
    {
        $snapshot = Monitor::query()->whereKey($monitorId)->where('check_token', $token)->first();

        if (! $snapshot || $snapshot->is_paused || ! $snapshot->hasPendingCheck()) {
            return;
        }

        $result = $this->probe->execute($snapshot->url);

        $this->database->transaction(function () use ($monitorId, $token, $snapshot, $result): void {
            $monitor = Monitor::query()->whereKey($monitorId)->lockForUpdate()->first();

            if (! $monitor || $monitor->check_token !== $token || $monitor->is_paused
                || ! $monitor->hasPendingCheck() || $monitor->url !== $snapshot->url) {
                return;
            }

            $checkedAt = now();
            $monitor->checks()->create([...$result, 'checked_at' => $checkedAt]);
            $monitor->status = $result['status'];
            $monitor->last_http_status = $result['http_status'];
            $monitor->last_response_time_ms = $result['response_time_ms'];
            $monitor->last_error_type = $result['error_type'];
            $monitor->last_checked_at = $checkedAt;
            $monitor->next_check_at = $checkedAt->copy()->addSeconds($monitor->interval_seconds);
            $monitor->check_token = null;
            $monitor->check_requested_at = null;
            $monitor->save();
        });
    }
}
