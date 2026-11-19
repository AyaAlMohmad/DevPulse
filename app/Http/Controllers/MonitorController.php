<?php

namespace App\Http\Controllers;

use App\Actions\MonitorCheckHistory;
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
            : redirect()->route('dashboard')->with('success', __('Monitor created. Use Check Now to run its first health check.'));
    }

    public function show(Request $request, Monitor $monitor, MonitorCheckHistory $history): MonitorResource|InertiaResponse
    {
        Gate::authorize('view', $monitor);

        return $request->expectsJson() ? new MonitorResource($monitor) : Inertia::render('Monitors/Show', [
            'monitor' => new MonitorResource($monitor),
            'checks' => $history->execute($monitor),
        ]);
    }

    public function update(UpdateMonitorRequest $request, Monitor $monitor, SaveMonitor $saveMonitor): MonitorResource|RedirectResponse
    {
        $monitor = $saveMonitor->execute($request->user(), $request->validated(), $monitor);

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', __('Monitor updated.'));
    }

    public function pause(Request $request, Monitor $monitor): MonitorResource|RedirectResponse
    {
        Gate::authorize('update', $monitor);
        Monitor::query()->whereKey($monitor->id)->update([
            'is_paused' => true,
            'next_check_at' => null,
            'check_token' => null,
            'check_requested_at' => null,
        ]);
        $monitor->refresh();

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', __('Monitor paused.'));
    }

    public function resume(Request $request, Monitor $monitor): MonitorResource|RedirectResponse
    {
        Gate::authorize('update', $monitor);

        Monitor::query()->whereKey($monitor->id)->where('is_paused', true)->update([
            'is_paused' => false,
            'next_check_at' => now(),
            'check_token' => null,
            'check_requested_at' => null,
        ]);
        $monitor->refresh();

        return $request->expectsJson() ? new MonitorResource($monitor)
            : redirect()->route('dashboard')->with('success', __('Monitor resumed. Use Check Now to refresh its status.'));
    }

    public function destroy(Request $request, Monitor $monitor): Response|RedirectResponse
    {
        Gate::authorize('delete', $monitor);
        $monitor->delete();

        return $request->expectsJson() ? response()->noContent()
            : redirect()->route('dashboard')->with('success', __('Monitor and check history deleted.'));
    }
}
