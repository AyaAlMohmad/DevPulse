<?php

namespace App\Http\Middleware;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'locales' => SetLocale::SUPPORTED,
            'translations' => fn () => json_decode(file_get_contents(lang_path(app()->getLocale().'.json')), true, flags: JSON_THROW_ON_ERROR),
            'auth' => [
                'user' => fn () => $request->user()?->only('id', 'name', 'email'),
            ],
            'urls' => [
                'home' => route('home'),
                'locale' => route('locale.update'),
                'login' => route('login'),
                'register' => route('register'),
                'logout' => route('logout'),
                'dashboard' => route('dashboard'),
                'monitors' => route('monitors.store'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'pending_checks' => fn () => $request->user() !== null && Monitor::query()
                ->where('user_id', $request->user()->id)
                ->whereNotNull('check_token')
                ->where('check_requested_at', '>', now()->subSeconds(Monitor::CHECK_LEASE_SECONDS))
                ->exists(),
            //
        ];
    }
}
