<?php

namespace App\Actions;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

class SaveMonitor
{
    /**
     * Create a new class instance.
     */
    public function __construct(private DatabaseManager $database)
    {
        //
    }

    public function execute(User $user, array $attributes, ?Monitor $monitor = null): Monitor
    {
        return $this->database->transaction(function () use ($user, $attributes, $monitor): Monitor {
            $monitor = $monitor?->exists
                ? $user->monitors()->lockForUpdate()->findOrFail($monitor->id)
                : $user->monitors()->make();
            $monitor->fill($attributes);

            if ($monitor->isDirty('url')) {
                $monitor->check_token = null;
                $monitor->check_requested_at = null;
                $monitor->last_error_type = null;
                $monitor->last_final_url = null;
                $monitor->last_redirect_count = null;
                $monitor->status = 'unknown';
                $monitor->last_http_status = null;
                $monitor->last_response_time_ms = null;
                $monitor->last_checked_at = null;
            }

            if (! $monitor->exists || $monitor->isDirty(['url', 'interval_seconds'])) {
                $monitor->next_check_at = $monitor->is_paused ? null : now();
            }

            $monitor->save();

            return $monitor->refresh();
        });
    }
}
