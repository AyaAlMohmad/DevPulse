<?php

namespace App\Models;

use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'url', 'interval_seconds'])]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    public const CHECK_LEASE_SECONDS = 300;

    public function hasPendingCheck(): bool
    {
        return $this->check_token !== null && $this->check_requested_at !== null
            && $this->check_requested_at->greaterThan(now()->subSeconds(self::CHECK_LEASE_SECONDS));
    }

    protected function casts(): array
    {
        return [
            'is_paused' => 'boolean',
            'interval_seconds' => 'integer',
            'last_http_status' => 'integer',
            'last_redirect_count' => 'integer',
            'last_response_time_ms' => 'integer',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'check_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }
}
