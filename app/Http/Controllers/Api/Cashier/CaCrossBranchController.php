<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of the two HQ-only cashier screens: the cross-branch monitor
 * (every branch's today, with the HQ cashier's own branch hidden the way
 * the website hides it) and the company-wide daily sales overview with the
 * per-branch and per-staff contribution percentages.
 *
 * Website gate: `cashier-cross-branch.access` — "Only the HQ cashier or the
 * Super Admin can monitor other branches." The same rule is recomputed here
 * from the database row on every call.
 */
class CaCrossBranchController extends CaBaseController
{
    /**
     * @never — 403 for anyone who is not an HQ monitor.
     */
    private function assertMonitor(Request $request): void
    {
        if (! $this->isCrossBranchMonitor($request)) {
            abort(403, 'Only the HQ cashier or the Super Admin can monitor other branches.');
        }
    }

    /** The branch list with each branch's today figures. */
    public function crossBranch(Request $request)
    {
        $this->assertMonitor($request);

        $isSuperAdmin = ((string) ($this->user($request)['role'] ?? '')) === 'super_admin';
        $myBranchId = $this->ownBranchId($request);

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $today = BusinessDay::dayFilter();

        $rows = collect($branches)
            ->when(
                ! $isSuperAdmin,
                fn ($branches) => $branches->reject(fn ($b) => (int) $b['id'] === $myBranchId)
            )
            ->map(function ($b) use ($today) {
                $id = (int) $b['id'];

                // Today's sales summary (shop business day).
                $todaySales = $this->supabase->query('sales', [
                    'branch_id' => "eq.{$id}",
                    'created_at' => $today,
                    'select' => 'id,total,payment_status',
                ]);

                $todayRevenue = array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $todaySales));
                $todayTransactions = count($todaySales);
                $todayPaid = count(array_filter($todaySales, fn ($s) => ($s['payment_status'] ?? '') === 'paid'));

                // Pending orders.
                $pendingOrders = $this->supabase->count('orders', [
                    'branch_id' => "eq.{$id}",
                    'status' => 'eq.pending',
                ]);

                // Active cashiers today.
                $cashierIds = array_unique(array_filter(array_map(fn ($s) => $s['cashier_id'] ?? null, $todaySales)));

                return [
                    'id' => $id,
                    'name' => $b['name'] ?? '',
                    'address' => $b['address'] ?? null,
                    'todayRevenue' => $todayRevenue,
                    'todayTransactions' => $todayTransactions,
                    'todayPaid' => $todayPaid,
                    'pendingOrders' => $pendingOrders,
                    'activeCashiers' => count($cashierIds),
                ];
            })->values();

        return response()->json([
            'branches' => $rows->all(),
            'isSuperAdmin' => $isSuperAdmin,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Company-wide daily sales overview: daily sales, expenses and actual
     * sales across all branches, each branch's percentage contribution to
     * company sales, and every staff member's percentage contribution
     * within their branch. "Today" is the shop's business day.
     */
    public function dailySalesOverview(Request $request)
    {
        $this->assertMonitor($request);

        $today = BusinessDay::dayFilter();

        // All active branches.
        $branches = $this->supabase->query('branches', [
            'is_active' => 'eq.true',
            'select' => 'id,name',
            'order' => 'name.asc',
        ]);

        // One query for today's paid sales across all branches.
        $sales = $this->supabase->query('sales', [
            'payment_status' => 'eq.paid',
            'created_at' => $today,
            'select' => 'id,branch_id,cashier_id,total',
        ]);

        // One query for today's expenses across all branches.
        $expenses = $this->supabase->query('expenses', [
            'created_at' => $today,
            'select' => 'branch_id,user_id,amount',
        ]);

        // Resolve staff names/roles for everyone who sold or recorded expenses today.
        $staffIds = [];
        foreach ($sales as $s) {
            if (! empty($s['cashier_id'])) {
                $staffIds[(int) $s['cashier_id']] = true;
            }
        }
        foreach ($expenses as $e) {
            if (! empty($e['user_id'])) {
                $staffIds[(int) $e['user_id']] = true;
            }
        }
        $staffMap = [];
        $staffIds = array_keys($staffIds);
        if (! empty($staffIds)) {
            $staffRows = $this->supabase->query('users', [
                'select' => 'id,name,role,branch_id',
                'id' => 'in.('.implode(',', $staffIds).')',
                'limit' => 500,
            ]);
            foreach ($staffRows as $u) {
                $staffMap[(int) $u['id']] = $u;
            }
        }

        $companySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $sales));

        $rows = collect($branches)->map(function ($b) use ($sales, $expenses, $staffMap, $companySales) {
            $id = (int) $b['id'];

            $branchSales = array_values(array_filter($sales, fn ($s) => (int) ($s['branch_id'] ?? 0) === $id));
            $branchExpenses = array_values(array_filter($expenses, fn ($e) => (int) ($e['branch_id'] ?? 0) === $id));

            $dailySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $branchSales));
            $dailyExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $branchExpenses));

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

                return [
                    'id' => $sid,
                    'name' => $info['name'] ?? ('User #'.$sid),
                    'role' => $info['role'] ?? 'staff',
                    'sales' => $sTotal,
                    'expenses' => $eTotal,
                    'actual' => $sTotal - $eTotal,
                    'salesPercent' => $dailySales > 0 ? round(($sTotal / $dailySales) * 100, 1) : 0.0,
                ];
            })->sortByDesc('sales')->values()->all();

            return [
                'id' => $id,
                'name' => $b['name'] ?? ('Branch #'.$id),
                'dailySales' => $dailySales,
                'dailyExpenses' => $dailyExpenses,
                'actualSales' => $dailySales - $dailyExpenses,
                'salesPercent' => $companySales > 0 ? round(($dailySales / $companySales) * 100, 1) : 0.0,
                'transactions' => count($branchSales),
                'staff' => $staff,
            ];
        })->sortByDesc('dailySales')->values();

        return response()->json([
            'rows' => $rows->all(),
            'companySales' => $companySales,
            'companyExpenses' => (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses)),
            'isSuperAdmin' => ((string) ($this->user($request)['role'] ?? '')) === 'super_admin',
            'scope' => $this->scopePayload($request),
        ]);
    }
}
