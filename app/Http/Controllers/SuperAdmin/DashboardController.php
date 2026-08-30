<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\FinancialService;
use App\Services\SupabaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(protected FinancialService $financialService)
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        // Fetch all counts from Supabase (5 API calls total)
        $totalSales = $this->supabase->count('sales');
        $pendingOrders = $this->supabase->count('orders', ['status' => 'eq.pending']);
        $pendingCashiers = $this->supabase->count('users', [
            'role' => 'eq.cashier',
            'status' => 'eq.pending',
        ]);
        $pendingAdmins = $this->supabase->count('users', [
            'role' => 'eq.branch_admin',
            'status' => 'eq.pending',
        ]);

        // Fetch branches
        $rawBranches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        // Fetch ALL cashier counts in one go
        $allCashiers = $this->supabase->query('users', [
            'select' => 'branch_id',
            'role' => 'eq.cashier',
        ]);
        $cashierCounts = [];
        foreach ($allCashiers as $c) {
            $bid = $c['branch_id'] ?? null;
            if ($bid) {
                $cashierCounts[$bid] = ($cashierCounts[$bid] ?? 0) + 1;
            }
        }

        // Fetch today's sales in one call and group by branch
        $today = Carbon::today()->toDateString();
        $todaySalesRaw = $this->supabase->query('sales', [
            'select' => 'branch_id,total',
            'created_at' => "gte.{$today}T00:00:00",
        ]);
        $todaySalesByBranch = [];
        foreach ($todaySalesRaw as $s) {
            $bid = $s['branch_id'] ?? null;
            if ($bid) {
                $todaySalesByBranch[$bid] = ($todaySalesByBranch[$bid] ?? 0) + ($s['total'] ?? 0);
            }
        }

        $activeBranches = count(array_filter($rawBranches, fn($b) => $b['is_active'] ?? false));

        // Build branch objects for the view
        $branches = collect($rawBranches)->map(function ($branch) use ($cashierCounts, $todaySalesByBranch) {
            $branch['cashiers_count'] = $cashierCounts[$branch['id']] ?? 0;
            $branch['today_sales'] = $todaySalesByBranch[$branch['id']] ?? 0;

            $todaySales = $todaySalesByBranch[$branch['id']] ?? 0;
            $branch['sales'] = collect([
                (object) [
                    'created_at' => Carbon::today()->toIso8601String(),
                    'total' => $todaySales,
                ],
            ]);

            return (object) $branch;
        });

        $todayFinancials = $this->financialService->getCompanyFinancials(
            Carbon::today(), Carbon::now()
        );

        return view('super-admin.dashboard', [
            'branches' => $branches,
            'totalSales' => $totalSales,
            'pendingOrders' => $pendingOrders,
            'pendingCashiers' => $pendingCashiers,
            'pendingAdmins' => $pendingAdmins,
            'activeBranches' => $activeBranches,
            'todayFinancials' => $todayFinancials,
            'pendingCount' => $pendingCashiers + $pendingAdmins,
        ]);
    }
}
