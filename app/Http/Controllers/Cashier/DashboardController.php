<?php

namespace App\Http\Controllers\Cashier;

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
        $user = auth()->user();
        $branchId = $user->branch_id;
        $supabaseUserId = $user->supabase_id ?? $user->id;

        $today = Carbon::today()->toDateString();

        // Batch 1: Today's sales — filter by date in query
        $todaySalesData = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$supabaseUserId}",
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

        // Batch 3: My active orders (assigned + ready) — filter in query
        $activeOrders = $this->supabase->query('orders', [
            'cashier_id' => "eq.{$supabaseUserId}",
            'select' => 'id,status',
        ]);
        $myAssignedCount = count(array_filter($activeOrders, fn($o) => in_array($o['status'] ?? '', ['assigned', 'ready'])));

        // Batch 4: Recent sales with items
        $recentSales = $this->supabase->query('sales', [
            'cashier_id' => "eq.{$supabaseUserId}",
            'select' => '*, items:sale_items(*, product:products(id,name,brand))',
            'order' => 'created_at.desc',
            'limit' => 5,
        ]);

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
        ]);
    }
}
