<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'interval_seconds' => $this->interval_seconds,
            'is_paused' => $this->is_paused,
            'status' => $this->status,
            'last_http_status' => $this->last_http_status,
            'last_response_time_ms' => $this->last_response_time_ms,
            'last_error_type' => $this->last_error_type,
            'is_check_pending' => $this->resource->hasPendingCheck(),
            'check_requested_at' => $this->check_requested_at?->toIso8601String(),
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'next_check_at' => $this->next_check_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'links' => [
                'update' => route('monitors.update', $this->id),
                'check' => route('monitors.check', $this->id),
                'pause' => route('monitors.pause', $this->id),
                'resume' => route('monitors.resume', $this->id),
                'destroy' => route('monitors.destroy', $this->id),
            ],
        ];
    }
}
