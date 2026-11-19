<?php

namespace App\Actions;

use App\Http\Resources\MonitorCheckResource;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MonitorCheckHistory
{
    /**
     * Create a new class instance.
     */
    public function __construct(private int $perPage = 10)
    {
        //
    }

    public function execute(Monitor $monitor): AnonymousResourceCollection
    {
        $checks = $monitor->checks()->latest('checked_at')->latest('id')->paginate($this->perPage)->withQueryString();
        $records = $checks->getCollection();
        $last = $records->last();
        $older = $last ? $monitor->checks()->where(function (Builder $query) use ($last): void {
            $query->where('checked_at', '<', $last->checked_at)
                ->orWhere(function (Builder $query) use ($last): void {
                    $query->where('checked_at', $last->checked_at)->where('id', '<', $last->id);
                });
        })->latest('checked_at')->latest('id')->first() : null;

        foreach ($records as $index => $check) {
            $check->setRelation('previousCheck', $records->get($index + 1) ?? $older);
        }

        return MonitorCheckResource::collection($checks);
    }
}
