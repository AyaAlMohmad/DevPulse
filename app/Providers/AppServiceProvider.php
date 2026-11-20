<?php

namespace App\Providers;

use App\Actions\ResolveMonitorTarget;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(ResolveMonitorTarget::class)
            ->needs('$allowPrivateTargets')
            ->give(fn (): bool => (bool) config('monitoring.allow_private_targets'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
