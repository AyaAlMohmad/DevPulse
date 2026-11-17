<?php

namespace App\Http\Controllers;

use App\Actions\SaveMonitor;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MonitorController extends Controller
{
    //

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Monitor::class);

        return MonitorResource::collection($request->user()->monitors()->latest('id')->paginate(20));
    }

    public function store(StoreMonitorRequest $request, SaveMonitor $saveMonitor): MonitorResource
    {
        return new MonitorResource($saveMonitor->execute($request->user(), $request->validated()));
    }

    public function show(Monitor $monitor): MonitorResource
    {
        Gate::authorize('view', $monitor);

        return new MonitorResource($monitor);
    }

    public function update(UpdateMonitorRequest $request, Monitor $monitor, SaveMonitor $saveMonitor): MonitorResource
    {
        return new MonitorResource($saveMonitor->execute($request->user(), $request->validated(), $monitor));
    }

    public function pause(Monitor $monitor): MonitorResource
    {
        Gate::authorize('update', $monitor);
        $monitor->is_paused = true;
        $monitor->next_check_at = null;
        $monitor->save();

        return new MonitorResource($monitor);
    }

    public function resume(Monitor $monitor): MonitorResource
    {
        Gate::authorize('update', $monitor);

        if ($monitor->is_paused) {
            $monitor->is_paused = false;
            $monitor->next_check_at = now();
            $monitor->save();
        }

        return new MonitorResource($monitor);
    }

    public function destroy(Monitor $monitor): Response
    {
        Gate::authorize('delete', $monitor);
        $monitor->delete();

        return response()->noContent();
    }
}
