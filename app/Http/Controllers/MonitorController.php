<?php

namespace App\Http\Controllers;

use App\Actions\MonitorOverview;
use App\Actions\SaveMonitor;
use App\Http\Requests\MonitorIndexRequest;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MonitorController extends Controller
{
    //

    public function index(MonitorIndexRequest $request, MonitorOverview $overview): AnonymousResourceCollection|InertiaResponse
    {
        $filters = [
            'search' => trim($request->validated('search') ?? ''),
            'status' => $request->validated('status') ?? '',
        ];
        $query = $request->user()->monitors()->latest('id');

        if ($filters['search'] !== '') {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('url', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($filters['status'] === 'paused') {
            $query->where('is_paused', true);
        } elseif ($filters['status'] !== '') {
            $query->where('is_paused', false)->where('status', $filters['status']);
        }

        $monitors = MonitorResource::collection($query->paginate(20)->withQueryString());

        return $request->expectsJson() ? $monitors : Inertia::render('Dashboard', [
            'monitors' => $monitors,
            'stats' => $overview->execute($request->user()),
            'filters' => $filters,
        ]);
    }

    public function store(StoreMonitorRequest $request, SaveMonitor $saveMonitor): MonitorResource|RedirectResponse
    {
        $monitor = $saveMonitor->execute($request->user(), $request->validated());

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', 'Monitor created. Health checks are not enabled yet.');
    }

    public function show(Monitor $monitor): MonitorResource
    {
        Gate::authorize('view', $monitor);

        return new MonitorResource($monitor);
    }

    public function update(UpdateMonitorRequest $request, Monitor $monitor, SaveMonitor $saveMonitor): MonitorResource|RedirectResponse
    {
        $monitor = $saveMonitor->execute($request->user(), $request->validated(), $monitor);

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', 'Monitor updated.');
    }

    public function pause(Request $request, Monitor $monitor): MonitorResource|RedirectResponse
    {
        Gate::authorize('update', $monitor);
        $monitor->is_paused = true;
        $monitor->next_check_at = null;
        $monitor->save();

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', 'Monitor paused.');
    }

    public function resume(Request $request, Monitor $monitor): MonitorResource|RedirectResponse
    {
        Gate::authorize('update', $monitor);

        if ($monitor->is_paused) {
            $monitor->is_paused = false;
            $monitor->next_check_at = now();
            $monitor->save();
        }

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', 'Monitor resumed. Health checks are not enabled yet.');
    }

    public function destroy(Request $request, Monitor $monitor): Response|RedirectResponse
    {
        Gate::authorize('delete', $monitor);
        $monitor->delete();

        return $request->expectsJson() ? response()->noContent()
            : redirect()->route('dashboard')->with('success', 'Monitor and check history deleted.');
    }
}
