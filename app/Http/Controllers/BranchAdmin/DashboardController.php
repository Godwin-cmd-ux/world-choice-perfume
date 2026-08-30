<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $localUser = auth()->user();
        $branchId = $localUser->branch_id;

        if (!$branchId) {
            return view('branch-admin.dashboard', [
                'todaySales' => 0,
                'todayTransactions' => 0,
                'pendingOrders' => 0,
                'lowStock' => 0,
                'totalStockValue' => 0,
                'financials' => ['revenue' => 0, 'gross_profit' => 0, 'expenses' => 0, 'net_profit' => 0, 'transaction_count' => 0, 'products_sold' => 0, 'cogs' => 0, 'stock_remaining' => 0],
            ]);
        }

        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toIso8601String();
        $now = Carbon::now()->toIso8601String();

        // Batch 1: Today's sales — filter by date in the query, not PHP
        $todaySalesData = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$today}T00:00:00",
            'select' => 'id,total',
        ]);
        $todaySales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $todaySalesData));
        $todayTransactions = count($todaySalesData);

        // Batch 2: Pending orders count
        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'eq.pending',
        ]);

        // Batch 3: Low stock items — fetch quantity+buying_cost in one query
        $allStock = $this->supabase->query('branch_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'quantity,buying_cost',
        ]);
        $lowStock = count(array_filter($allStock, fn($s) => ($s['quantity'] ?? 0) <= 5));
        $totalStockValue = array_sum(array_map(fn($s) => ($s['quantity'] ?? 0) * ($s['buying_cost'] ?? 0), $allStock));

        // Batch 4: Monthly sales — filter by date in the query
        $monthlySales = $this->supabase->query('sales', [
            'branch_id' => "eq.{$branchId}",
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$monthStart}",
            'select' => 'id,total',
        ]);
        $revenue = array_sum(array_map(fn($s) => $s['total'] ?? 0, $monthlySales));

        // Batch 5: Monthly expenses — filter by date in the query
        $monthlyExpenses = $this->supabase->query('expenses', [
            'branch_id' => "eq.{$branchId}",
            'created_at' => "gte.{$monthStart}",
            'select' => 'id,amount',
        ]);
        $totalExpenses = array_sum(array_map(fn($e) => $e['amount'] ?? 0, $monthlyExpenses));

        $financials = [
            'revenue' => $revenue,
            'gross_profit' => $revenue,
            'expenses' => $totalExpenses,
            'net_profit' => $revenue - $totalExpenses,
            'transaction_count' => count($monthlySales),
            'products_sold' => 0,
            'cogs' => 0,
            'stock_remaining' => 0,
        ];

        return view('branch-admin.dashboard', compact(
            'todaySales', 'todayTransactions', 'pendingOrders',
            'lowStock', 'totalStockValue', 'financials'
        ));
    }
}
