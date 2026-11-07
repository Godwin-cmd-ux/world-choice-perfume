<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NavigationCountsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One place computes the sidebar numbers, so every role sees the same
        // counts on a page and from the live polling endpoint. Layouts only —
        // the public site must not touch staff tables.
        View::composer(['layouts.app', 'stock-manager.layouts.app'], function (ViewInstance $view) {
            $user = auth()->user();
            $route = request()->route()?->getName();
            $counts = app(NavigationCountsService::class);

            $navCounts = $counts->forUser($user instanceof User ? $user : null);

            $view->with([
                'navCounts' => $navCounts,
                'liveKeys' => $counts->keysForRoute($route),
                'liveRefresh' => $counts->shouldAutoRefresh($route),
            ]);
        });
    }
}
