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
    // DAILY SALES OVERVIEW (Chief cashier / Super Admin)
    // ========================

    /**
     * Company-wide daily sales overview for the chief cashier (HQ cashier):
     * daily sales, expenses and actual sales across all branches, each
     * branch's percentage contribution to company sales, and every staff
     * member's percentage contribution within their branch.
     */
    public function dailySalesOverview()
    {
        $todayStart = Carbon::today()->startOfDay()->toIso8601String();

        // All active branches
        $branches = $this->supabase->query('branches', [
            'is_active' => 'eq.true',
            'select' => 'id,name',
            'order' => 'name.asc',
        ]);

        // One query for today's paid sales across all branches
        $sales = $this->supabase->query('sales', [
            'payment_status' => 'eq.paid',
            'created_at' => "gte.{$todayStart}",
            'select' => 'id,branch_id,cashier_id,total',
        ]);

        // One query for today's expenses across all branches
        $expenses = $this->supabase->query('expenses', [
            'created_at' => "gte.{$todayStart}",
            'select' => 'branch_id,user_id,amount',
        ]);

        // Cost of goods sold per branch (from per-sale unit costs).
        $cogsByBranch = (new \App\Services\FinancialService())->getCompanyProfitSummary(Carbon::today(), Carbon::now())['by_branch'] ?? [];

        // Resolve staff names/roles for everyone who sold or recorded expenses today
        $staffIds = [];
        foreach ($sales as $s) {
            if (!empty($s['cashier_id'])) $staffIds[(int) $s['cashier_id']] = true;
        }
        foreach ($expenses as $e) {
            if (!empty($e['user_id'])) $staffIds[(int) $e['user_id']] = true;
        }
        $staffMap = [];
        $staffIds = array_keys($staffIds);
        if (!empty($staffIds)) {
            $staffRows = $this->supabase->query('users', [
                'select' => 'id,name,role,branch_id',
                'id' => 'in.(' . implode(',', $staffIds) . ')',
                'limit' => 500,
            ]);
            foreach ($staffRows as $u) {
                $staffMap[(int) $u['id']] = $u;
            }
        }

        $companySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $sales));

        $rows = collect($branches)->map(function ($b) use ($sales, $expenses, $staffMap, $companySales, $cogsByBranch) {
            $id = (int) $b['id'];

            $branchSales = array_values(array_filter($sales, fn ($s) => (int) ($s['branch_id'] ?? 0) === $id));
            $branchExpenses = array_values(array_filter($expenses, fn ($e) => (int) ($e['branch_id'] ?? 0) === $id));

            $dailySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $branchSales));
            $dailyExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $branchExpenses));
            $cogs = (float) ($cogsByBranch[$id]['cogs'] ?? 0);

            // Staff contribution within the branch (sales by cashier_id,
            // expenses by user_id, actual = sales - expenses per staff).
            $salesByStaff = [];
            foreach ($branchSales as $s) {
                $sid = (int) ($s['cashier_id'] ?? 0);
                $salesByStaff[$sid] = ($salesByStaff[$sid] ?? 0) + (float) ($s['total'] ?? 0);
            }
            $expensesByStaff = [];
            foreach ($branchExpenses as $e) {
                $sid = (int) ($e['user_id'] ?? 0);
                $expensesByStaff[$sid] = ($expensesByStaff[$sid] ?? 0) + (float) ($e['amount'] ?? 0);
            }

            $staffIdsForBranch = array_unique(array_merge(array_keys($salesByStaff), array_keys($expensesByStaff)));
            $staff = collect($staffIdsForBranch)->map(function ($sid) use ($salesByStaff, $expensesByStaff, $staffMap, $dailySales) {
                $info = $staffMap[$sid] ?? null;
                $sTotal = (float) ($salesByStaff[$sid] ?? 0);
                $eTotal = (float) ($expensesByStaff[$sid] ?? 0);

                return (object) [
                    'id' => $sid,
                    'name' => $info['name'] ?? ('User #' . $sid),
                    'role' => $info['role'] ?? 'staff',
                    'sales' => $sTotal,
                    'expenses' => $eTotal,
                    'actual' => $sTotal - $eTotal,
                    'salesPercent' => $dailySales > 0 ? round(($sTotal / $dailySales) * 100, 1) : 0.0,
                ];
            })->sortByDesc('sales')->values();

            return (object) [
                'id' => $id,
                'name' => $b['name'] ?? ('Branch #' . $id),
                'dailySales' => $dailySales,
                'dailyExpenses' => $dailyExpenses,
                'actualSales' => $dailySales - $dailyExpenses,
                'cogs' => $cogs,
                'salesPercent' => $companySales > 0 ? round(($dailySales / $companySales) * 100, 1) : 0.0,
                'transactions' => count($branchSales),
                'staff' => $staff,
            ];
        })->sortByDesc('dailySales')->values();

        return view('cashier.daily-sales-overview', [
            'rows' => $rows,
            'companySales' => $companySales,
            'companyExpenses' => (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses)),
            'isSuperAdmin' => $this->scope->isSuperAdmin(),
        ]);
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
