<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Services\FinancialService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of cashier.dashboard: today's money and transactions, the
 * pending queue, the cashier's own picked orders, recent sales and the
 * branch's daily sales/expenses/actual summary. Normal mode counts only
 * this cashier's sales; while an HQ monitor watches another branch the
 * same figures come from the monitored branch, exactly like the Blade page.
 */
class CaDashboardController extends CaBaseController
{
    /** Just the scope flags — the app shapes navigation from this. */
    public function scope(Request $request)
    {
        return response()->json(['scope' => $this->scopePayload($request)]);
    }

    public function dashboard(Request $request)
    {
        $activeBranchId = $this->activeBranchId($request);
        $userId = $this->performingUserId($request);
        $inCrossBranch = $this->scopePayload($request)['in_cross_branch'];

        $today = BusinessDay::dayFilter();

        if ($inCrossBranch) {
            // Cross-branch: show all sales for the monitored branch today.
            $todaySalesData = $this->supabase->query('sales', [
                'branch_id' => "eq.{$activeBranchId}",
                'created_at' => $today,
                'select' => 'id,total',
            ]);
            $recentSales = $this->supabase->query('sales', [
                'branch_id' => "eq.{$activeBranchId}",
                'select' => '*, cashier:users(id,name), items:sale_items(*, product:products(id,name,brand))',
                'order' => 'created_at.desc',
                'limit' => 10,
            ]);
        } else {
            // Normal mode: show only this cashier's data.
            $todaySalesData = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$userId}",
                'created_at' => $today,
                'select' => 'id,total',
            ]);
            $recentSales = $this->supabase->query('sales', [
                'cashier_id' => "eq.{$userId}",
                'select' => '*, items:sale_items(*, product:products(id,name,brand))',
                'order' => 'created_at.desc',
                'limit' => 5,
            ]);
        }

        // The pending queue always belongs to the branch on screen.
        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$activeBranchId}",
            'status' => 'eq.pending',
        ]);

        // Orders the cashier is carrying (branch-wide while monitoring).
        $activeOrders = $this->supabase->query('orders', $inCrossBranch ? [
            'branch_id' => "eq.{$activeBranchId}",
            'select' => 'id,status',
        ] : [
            'cashier_id' => "eq.{$userId}",
            'select' => 'id,status',
        ]);
        $myAssignedCount = count(array_filter($activeOrders, fn ($o) => ($o['status'] ?? '') === 'picked'));

        // Daily financials for the branch the dashboard is currently scoped to.
        $dailySummary = (new FinancialService($this->supabase))->getDailySummary((int) $activeBranchId);

        return response()->json([
            'todaySales' => array_sum(array_map(fn ($s) => $s['total'] ?? 0, $todaySalesData)),
            'todayTransactions' => count($todaySalesData),
            'pendingOrders' => $pendingOrders,
            'myAssignedOrders' => $myAssignedCount,
            'recentSales' => array_values($recentSales),
            'dailySummary' => $dailySummary,
            'scope' => $this->scopePayload($request),
        ]);
    }
}
