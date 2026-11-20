<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $previous = $this->resource->getRelation('previousCheck');
        $reason = match (true) {
            $previous === null => 'first_check',
            $this->target_url === null || $previous->target_url === null => 'unknown_target',
            $this->target_url !== $previous->target_url => 'different_target',
            default => null,
        };
        $comparable = $reason === null;
        $timed = $comparable && $this->status === 'up' && $previous->status === 'up'
            && $this->response_time_ms !== null && $previous->response_time_ms !== null
            && $this->final_url === $previous->final_url;

        return [
            'id' => $this->id,
            'target_url' => $this->target_url,
            'final_url' => $this->final_url,
            'redirect_count' => $this->redirect_count,
            'status' => $this->status,
            'http_status' => $this->http_status,
            'response_time_ms' => $this->response_time_ms,
            'error_type' => $this->error_type,
            'timings' => $this->timings,
            'checked_at' => $this->checked_at->toIso8601String(),
            'comparison' => [
                'reason' => $reason,
                'previous_id' => $previous?->id,
                'previous_status' => $previous?->status,
                'previous_http_status' => $previous?->http_status,
                'previous_error_type' => $previous?->error_type,
                'final_url_changed' => $comparable && $this->final_url !== null && $previous->final_url !== null
                    && $this->final_url !== $previous->final_url,
                'status_changed' => $comparable ? $this->status !== $previous->status : null,
                'http_status_changed' => $comparable ? $this->http_status !== $previous->http_status : null,
                'error_changed' => $comparable ? $this->error_type !== $previous->error_type : null,
                'response_time_delta_ms' => $timed ? $this->response_time_ms - $previous->response_time_ms : null,
            ],
        ];
    }
}
