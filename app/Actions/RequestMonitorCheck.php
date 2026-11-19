<?php

namespace App\Actions;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestMonitorCheck
{
    /**
     * Create a new class instance.
     */
    public function __construct(private DatabaseManager $database)
    {
        //
    }

    public function execute(Monitor $monitor): void
    {
        $this->database->transaction(function () use ($monitor): void {
            $monitor = Monitor::query()->whereKey($monitor->id)->lockForUpdate()->firstOrFail();

            if ($monitor->is_paused) {
                throw ValidationException::withMessages(['check' => __('Resume this monitor before requesting a check.')]);
            }

            if ($monitor->hasPendingCheck()) {
                throw ValidationException::withMessages(['check' => __('A check is already queued or running. Refresh results shortly.')]);
            }

            $monitor->check_token = (string) Str::uuid();
            $monitor->check_requested_at = now();
            $monitor->save();

            RunMonitorCheck::dispatch($monitor->id, $monitor->check_token)
                ->onConnection('database')->onQueue('monitor-checks')->afterCommit();
        });
    }
}
