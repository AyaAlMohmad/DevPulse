<?php

namespace App\Jobs;

use App\Actions\CheckMonitor;
use App\Models\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 25;

    public int $maxExceptions = 1;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $monitorId, public string $token)
    {
        //
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('monitor-check:'.$this->monitorId))->shared()->releaseAfter(3)->expireAfter(60)];
    }

    /**
     * Execute the job.
     */
    public function handle(CheckMonitor $check): void
    {
        try {
            $check->execute($this->monitorId, $this->token);
        } finally {
            $this->releaseReservation();
        }
        //
    }

    public function failed(?Throwable $exception): void
    {
        $this->releaseReservation();
    }

    private function releaseReservation(): void
    {
        Monitor::query()->whereKey($this->monitorId)->where('check_token', $this->token)
            ->update(['check_token' => null, 'check_requested_at' => null]);
    }
}
