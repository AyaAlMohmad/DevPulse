<?php

namespace App\Http\Controllers;

use App\Actions\RequestMonitorCheck;
use App\Http\Requests\RunMonitorCheckRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MonitorCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RunMonitorCheckRequest $request, Monitor $monitor, RequestMonitorCheck $requestCheck): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $monitor);
        $requestCheck->execute($monitor);

        return $request->expectsJson()
            ? (new MonitorResource($monitor->refresh()))->response()->setStatusCode(202)
            : redirect()->route(
                $request->validated('redirect') === 'details' ? 'monitors.show' : 'dashboard',
                $request->validated('redirect') === 'details' ? ['monitor' => $monitor->id] : [],
            )->with('success', __('Check queued. Refresh results after the queue worker processes it.'));
        //
    }
}
