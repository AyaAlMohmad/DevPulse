<?php

namespace App\Http\Controllers;

use App\Actions\MonitorCheckHistory;
use App\Models\Monitor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MonitorHistoryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Monitor $monitor, MonitorCheckHistory $history): AnonymousResourceCollection
    {
        //
        Gate::authorize('view', $monitor);

        return $history->execute($monitor);
    }
}
