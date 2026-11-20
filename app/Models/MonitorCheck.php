<?php

namespace App\Models;

use Database\Factories\MonitorCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_url', 'final_url', 'redirect_count', 'status', 'http_status', 'response_time_ms', 'error_type', 'timings', 'checked_at'])]
class MonitorCheck extends Model
{
    /** @use HasFactory<MonitorCheckFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'redirect_count' => 'integer',
            'response_time_ms' => 'integer',
            'timings' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
