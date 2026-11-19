<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        //
        $destination = $request->validated('redirect');
        $parameters = [];

        if ($destination === 'monitors.show') {
            abort_unless($request->user(), 401);
            $monitor = $request->user()->monitors()->findOrFail($request->validated('monitor_id'));
            $parameters = ['monitor' => $monitor->id];
        }

        $request->session()->put('locale', $request->validated('locale'));

        return redirect()->route($destination, $parameters);
    }
}
