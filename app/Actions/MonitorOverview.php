<?php

namespace App\Actions;

use App\Models\Monitor;
use App\Models\User;

class MonitorOverview
{
    /**
     * Create a new class instance.
     */
    public function __construct(private Monitor $monitor)
    {
        //
    }

    public function execute(User $user): array
    {
        $summary = $this->monitor->newQuery()->whereBelongsTo($user)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_paused = 1 THEN 1 ELSE 0 END) as paused')
            ->selectRaw('SUM(CASE WHEN is_paused = 0 AND status = ? THEN 1 ELSE 0 END) as up', ['up'])
            ->selectRaw('SUM(CASE WHEN is_paused = 0 AND status = ? THEN 1 ELSE 0 END) as down', ['down'])
            ->selectRaw('SUM(CASE WHEN is_paused = 0 AND status = ? THEN 1 ELSE 0 END) as unknown', ['unknown'])
            ->selectRaw('AVG(CASE WHEN is_paused = 0 THEN last_response_time_ms END) as average_response_time_ms')
            ->first();

        return [
            'total' => (int) $summary->total,
            'up' => (int) $summary->up,
            'down' => (int) $summary->down,
            'unknown' => (int) $summary->unknown,
            'paused' => (int) $summary->paused,
            'average_response_time_ms' => $summary->average_response_time_ms === null
                ? null : round((float) $summary->average_response_time_ms, 1),
        ];
    }
}
