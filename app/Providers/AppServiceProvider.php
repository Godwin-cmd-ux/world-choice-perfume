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
                $pendingCashiers = $supabase->count('users', [
                    'role' => 'eq.cashier',
                    'status' => 'eq.pending',
                ]);
                $pendingAdmins = $supabase->count('users', [
                    'role' => 'eq.branch_admin',
                    'status' => 'eq.pending',
                ]);
                $view->with('pendingCount', $pendingCashiers + $pendingAdmins);
            } catch (\Exception $e) {
                $view->with('pendingCount', 0);
            }
        });
    }
}
