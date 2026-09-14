<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\CashierScope;
use App\Services\SupabaseService;
use Carbon\Carbon;

class CashierController extends Controller
{
    private SupabaseService $supabase;
    private CashierScope $scope;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->scope = new CashierScope($this->supabase);
    }

    // ========================
    // CROSS-BRANCH MONITORING
    // ========================

    public function crossBranchDashboard()
    {
        $myBranchId = (int) (auth()->user()->branch_id ?? 0);
        $isSuperAdmin = $this->scope->isSuperAdmin();

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $rows = collect($branches)
            ->when(
                !$isSuperAdmin,
                fn ($branches) => $branches->reject(fn ($b) => (int) $b['id'] === $myBranchId)
            )
            ->map(function ($b) {
                $id = (int) $b['id'];

                // Today's sales summary
                $todayStart = Carbon::now()->startOfDay()->toIso8601String();
                $todaySales = $this->supabase->query('sales', [
                    'branch_id' => "eq.{$id}",
                    'created_at' => "gte.{$todayStart}",
                    'select' => 'id,total,payment_status',
                ]);

                $todayRevenue = array_sum(array_map(fn($s) => (float) ($s['total'] ?? 0), $todaySales));
                $todayTransactions = count($todaySales);
                $todayPaid = count(array_filter($todaySales, fn($s) => ($s['payment_status'] ?? '') === 'paid'));

                // Pending orders
                $pendingOrders = $this->supabase->count('orders', [
                    'branch_id' => "eq.{$id}",
                    'status' => 'eq.pending',
                ]);

                // Active cashiers today
                $cashierIds = array_unique(array_filter(array_map(fn($s) => $s['cashier_id'] ?? null, $todaySales)));

                return (object) [
                    'id' => $id,
                    'name' => $b['name'] ?? '',
                    'address' => $b['address'] ?? null,
                    'todayRevenue' => $todayRevenue,
                    'todayTransactions' => $todayTransactions,
                    'todayPaid' => $todayPaid,
                    'pendingOrders' => $pendingOrders,
                    'activeCashiers' => count($cashierIds),
                ];
            });

        return view('cashier.cross-branch', [
            'rows' => $rows,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function enterCrossBranch($branch)
    {
        $branchId = (int) $branch;
        $branchRow = $this->supabase->find('branches', $branchId, 'id,name');
        if (!$branchRow) {
            abort(404);
        }

        $this->scope->enterBranch($branchId);

        return redirect()->route('cashier.dashboard')
            ->with('success', 'Now monitoring: ' . ($branchRow['name'] ?? 'Branch #' . $branchId));
    }

    public function exitCrossBranch()
    {
        $this->scope->exitBranch();

        if ($this->scope->isSuperAdmin()) {
            return redirect()->route('cashier.cross-branch')
                ->with('success', 'You are back on the branch list.');
        }

        return redirect()->route('cashier.dashboard')->with('success', 'You are back to your own branch.');
    }
}
