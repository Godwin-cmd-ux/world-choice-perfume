<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\CashierScope;
use App\Services\FinancialService;
use App\Services\SupabaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private SupabaseService $supabase;
    private CashierScope $scope;
    private FinancialService $financials;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->scope = new CashierScope($this->supabase);
        $this->financials = new FinancialService();
    }

    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $supabaseUserId = $user->supabase_id ?? $user->id;
        $inCrossBranch = $this->scope->inCrossBranchMode();
        $activeBranchId = $this->scope->activeBranchId();
        $activeBranchName = $this->scope->activeBranchName();

        $today = Carbon::today()->toDateString();

        $financialScopeBranchId = $inCrossBranch ? (int) $activeBranchId : (int) $branchId;

        if ($inCrossBranch) {
            // Cross-branch: show all sales for the monitored branch today
            $todaySalesData = $this->supabase->query('sales', [
                'branch_id' => "eq.{$activeBranchId}",
                'created_at' => "gte.{$today}T00:00:00",
                'select' => 'id,total',
            ]);
            $pendingOrders = $this->supabase->count('orders', [
                'branch_id' => "eq.{$activeBranchId}",
                'status' => 'eq.pending',
            ]);
            $activeOrders = $this->supabase->query('orders', [
                'branch_id' => "eq.{$activeBranchId}",
                'select' => 'id,status',
            ]);
            $recentSales = $this->supabase->query('sales', [
                'branch_id' => "eq.{$activeBranchId}",
                'select' => '*, cashier:users(id,name), items:sale_items(*, product:products(id,name,brand))',
                'order' => 'created_at.desc',
                'limit' => 10,
            ]);
        } else {
            // Normal mode: show only this cashier's data
            $todaySalesData = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$supabaseUserId}",
                'created_at' => "gte.{$today}T00:00:00",
                'select' => 'id,total',
            ]);
            $pendingOrders = $this->supabase->count('orders', [
                'branch_id' => "eq.{$branchId}",
                'status' => 'eq.pending',
            ]);
            $activeOrders = $this->supabase->query('orders', [
                'cashier_id' => "eq.{$supabaseUserId}",
                'select' => 'id,status',
            ]);
            $recentSales = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$supabaseUserId}",
                'select' => '*, items:sale_items(*, product:products(id,name,brand))',
                'order' => 'created_at.desc',
                'limit' => 5,
            ]);
        }

        $todaySales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $todaySalesData));
        $todayTransactions = count($todaySalesData);
        $myAssignedCount = count(array_filter($activeOrders, fn($o) => in_array($o['status'] ?? '', ['assigned', 'ready'])));

        // Daily financials (sales, expenses, actual = sales - expenses) for the
        // branch the dashboard is currently scoped to.
        $dailySummary = $this->financials->getDailySummary($financialScopeBranchId);

        // Cast for views
        $recentSales = collect($recentSales)->map(function ($s) {
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        return view('cashier.dashboard', compact(
            'todaySales', 'todayTransactions', 'pendingOrders',
            'recentSales'
        ) + [
            'myAssignedOrders' => $myAssignedCount,
            'inCrossBranch' => $inCrossBranch,
            'activeBranchName' => $activeBranchName,
            'dailySummary' => $dailySummary,
        ]);
    }
}
