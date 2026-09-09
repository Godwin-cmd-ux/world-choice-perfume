<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Share pending approval count with all super-admin views (for sidebar badge)
        View::composer('super-admin.*', function ($view) {
            try {
                $supabase = new SupabaseService();
                $pendingCount = $supabase->count('users', [
                    'role' => 'in.(cashier,branch_admin,stock_manager,customer_care,seller)',
                    'status' => 'eq.pending',
                ]);
                $view->with('pendingCount', $pendingCount);
                $view->with('unreadNotifications', (int) $supabase->count('admin_notifications', [
                    'is_read' => 'eq.false',
                ]));
            } catch (\Exception $e) {
                $view->with('pendingCount', 0);
                $view->with('unreadNotifications', 0);
            }
        });
    }
}
