<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use App\Services\FinancialService;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * JSON twin of branch-admin.dashboard: today's money and transactions,
 * the pending queue, low stock and stock value, the daily
 * sales/expenses/actual summary and the month's financials — all scoped to
 * the admin's own branch, exactly like the website.
 */
class BaDashboardController extends BaBaseController
{
    /** Just the branch flags — the app shapes navigation from this. */
    public function scope(Request $request)
    {
        return response()->json(['scope' => $this->scopePayload($request)]);
    }

    public function dashboard(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        if (! $branchId) {
            return response()->json([
                'todaySales' => 0,
                'todayTransactions' => 0,
                'pendingOrders' => 0,
                'lowStock' => 0,
                'totalStockValue' => 0,
                'dailySummary' => ['daily_sales' => 0, 'daily_expenses' => 0, 'actual_sales' => 0, 'transaction_count' => 0],
                'financials' => ['revenue' => 0, 'expenses' => 0, 'transaction_count' => 0, 'products_sold' => 0, 'stock_remaining' => 0],
                'scope' => $this->scopePayload($request),
            ]);
        }

        $monthStart = Carbon::now()->startOfMonth()->toIso8601String();

        // Today's sales — the shop's business day, matching the daily summary.
        $todaySalesData = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => BusinessDay::dayFilter(),
            'select' => 'id,total',
        ]);
        $todaySales = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $todaySalesData));
        $todayTransactions = count($todaySalesData);

        $dailySummary = (new FinancialService($this->supabase))->getDailySummary((int) $branchId);

        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'eq.pending',
        ]);

        $allStock = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'quantity,selling_price',
        ]);
        $lowStock = count(array_filter($allStock, fn ($s) => ($s['quantity'] ?? 0) <= 5));
        $totalStockValue = array_sum(array_map(fn ($s) => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0), $allStock));

        $monthlySales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$monthStart}",
            'select' => 'id,total',
        ]);
        $revenue = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $monthlySales));

        $monthlyExpenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
            'created_at' => "gte.{$monthStart}",
            'select' => 'id,amount',
        ]);
        $totalExpenses = array_sum(array_map(fn ($e) => $e['amount'] ?? 0, $monthlyExpenses));

        return response()->json([
            'todaySales' => $todaySales,
            'todayTransactions' => $todayTransactions,
            'pendingOrders' => $pendingOrders,
            'lowStock' => $lowStock,
            'totalStockValue' => $totalStockValue,
            'dailySummary' => $dailySummary,
            'financials' => [
                'revenue' => $revenue,
                'expenses' => $totalExpenses,
                'transaction_count' => count($monthlySales),
                'products_sold' => 0,
                'stock_remaining' => 0,
            ],
            'scope' => $this->scopePayload($request),
        ]);
    }
}
