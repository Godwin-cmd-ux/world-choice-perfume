<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinancialService;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * JSON twin of the Super Admin dashboard plus the three monitoring screens
 * the sidebar links (Daily Sales Overview, Cross-Branch Stock, Cross-Branch
 * Sales). Every figure is assembled by the same Supabase queries and
 * FinancialService calls as the website controllers, so the numbers can never
 * drift apart — only the presentation differs.
 */
class AdminDashboardController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(protected FinancialService $financialService)
    {
        $this->supabase = new SupabaseService();
    }

    /** Mirrors SuperAdmin\DashboardController::index. */
    public function dashboard()
    {
        $todayStart = BusinessDay::start();
        $todayEnd = BusinessDay::end();

        $todaySalesRaw = $this->supabase->query('sales', [
            'select' => 'branch_id,total',
            'created_at' => BusinessDay::dayFilter(),
        ]);
        $todaySalesByBranch = [];
        $todayTotalRevenue = 0;
        foreach ($todaySalesRaw as $s) {
            $bid = $s['branch_id'] ?? null;
            $amt = $s['total'] ?? 0;
            $todayTotalRevenue += $amt;
            if ($bid) {
                $todaySalesByBranch[$bid] = ($todaySalesByBranch[$bid] ?? 0) + $amt;
            }
        }

        $pendingOrdersRaw = $this->supabase->queryFresh('orders', [
            'select' => 'id,branch_id,customer_id,created_at,order_number,status',
            'status' => 'eq.pending',
            'order' => 'created_at.asc',
        ]);
        $pendingOrdersByBranch = [];
        foreach ($pendingOrdersRaw as $o) {
            $bid = $o['branch_id'] ?? null;
            if ($bid) {
                $pendingOrdersByBranch[$bid][] = $o;
            }
        }

        $pickedOrdersRaw = $this->supabase->queryFresh('orders', [
            'select' => 'id,branch_id,status',
            'status' => 'eq.picked',
        ]);
        $inProgressByBranch = [];
        foreach ($pickedOrdersRaw as $o) {
            $bid = $o['branch_id'] ?? null;
            if ($bid) {
                $inProgressByBranch[$bid] = ($inProgressByBranch[$bid] ?? 0) + 1;
            }
        }

        $pendingApprovals = $this->supabase->count('users', [
            'role' => 'in.(cashier,branch_admin,stock_manager,customer_care,seller,graphic_designer)',
            'status' => 'eq.pending',
        ]);

        $rawBranches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

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

        $activeBranches = count(array_filter($rawBranches, fn ($b) => $b['is_active'] ?? false));

        $allCustomerIds = array_unique(array_map(fn ($o) => $o['customer_id'] ?? null, $pendingOrdersRaw));
        $allCustomerIds = array_filter($allCustomerIds);
        $customerNames = [];
        if (! empty($allCustomerIds)) {
            $customerIdsStr = implode(',', $allCustomerIds);
            $customers = $this->supabase->query('customers', [
                'select' => 'id,name',
                'id' => "in.({$customerIdsStr})",
            ]);
            foreach ($customers as $c) {
                $customerNames[$c['id']] = $c['name'] ?? 'Walk-in';
            }
        }

        $branches = [];
        foreach ($rawBranches as $branch) {
            $branchId = $branch['id'];
            $branch['cashiers_count'] = $cashierCounts[$branchId] ?? 0;
            $branch['today_sales'] = $todaySalesByBranch[$branchId] ?? 0;

            $branchPending = $pendingOrdersByBranch[$branchId] ?? [];
            $branch['pending_orders'] = array_map(function ($order) use ($customerNames) {
                $createdAt = Carbon::parse($order['created_at']);
                $minutesAgo = (int) $createdAt->diffInMinutes(now());

                return [
                    'id' => $order['id'],
                    'order_number' => $order['order_number'] ?? 'N/A',
                    'customer_name' => $customerNames[$order['customer_id'] ?? null] ?? 'Walk-in',
                    'created_at' => $order['created_at'],
                    'minutes_ago' => $minutesAgo,
                    'duration_label' => $minutesAgo < 60 ? "{$minutesAgo}m" : ($minutesAgo < 1440 ? floor($minutesAgo / 60).'h '.($minutesAgo % 60).'m' : floor($minutesAgo / 1440).'d '.floor(($minutesAgo % 1440) / 60).'h'),
                ];
            }, $branchPending);
            $branch['pending_count'] = count($branchPending);
            $branch['in_progress_count'] = $inProgressByBranch[$branchId] ?? 0;

            $branches[] = $branch;
        }

        $todayFinancials = $this->financialService->getCompanyFinancials($todayStart, $todayEnd);
        $todayFinancials['actual_sales'] = (float) $todayFinancials['revenue'] - (float) $todayFinancials['expenses'];

        return response()->json([
            'today_total_revenue' => $todayTotalRevenue,
            'total_sales_count' => count($todaySalesRaw),
            'pending_orders' => count($pendingOrdersRaw),
            'pending_approvals' => $pendingApprovals,
            'active_branches' => $activeBranches,
            'today_financials' => $todayFinancials,
            'branches' => $branches,
        ]);
    }

    /** Mirrors CashierController::dailySalesOverview (the Super Admin link). */
    public function dailySales()
    {
        $today = BusinessDay::dayFilter();

        $branches = $this->supabase->query('branches', [
            'is_active' => 'eq.true',
            'select' => 'id,name',
            'order' => 'name.asc',
        ]);

        $sales = $this->supabase->query('sales', [
            'payment_status' => 'eq.paid',
            'created_at' => $today,
            'select' => 'id,branch_id,cashier_id,total',
        ]);

        $expenses = $this->supabase->query('expenses', [
            'created_at' => $today,
            'select' => 'branch_id,user_id,amount',
        ]);

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
        $companyExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses));

        $rows = [];
        foreach ($branches as $b) {
            $id = (int) $b['id'];

            $branchSales = array_values(array_filter($sales, fn ($s) => (int) ($s['branch_id'] ?? 0) === $id));
            $branchExpenses = array_values(array_filter($expenses, fn ($e) => (int) ($e['branch_id'] ?? 0) === $id));

            $dailySales = (float) array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $branchSales));
            $dailyExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $branchExpenses));

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
            $staff = [];
            foreach ($staffIdsForBranch as $sid) {
                $info = $staffMap[$sid] ?? null;
                $sTotal = (float) ($salesByStaff[$sid] ?? 0);
                $eTotal = (float) ($expensesByStaff[$sid] ?? 0);
                $staff[] = [
                    'id' => $sid,
                    'name' => $info['name'] ?? ('User #'.$sid),
                    'role' => $info['role'] ?? 'staff',
                    'sales' => $sTotal,
                    'expenses' => $eTotal,
                    'actual' => $sTotal - $eTotal,
                    'sales_percent' => $dailySales > 0 ? round(($sTotal / $dailySales) * 100, 1) : 0.0,
                ];
            }
            usort($staff, fn ($a, $b2) => $b2['sales'] <=> $a['sales']);

            $rows[] = [
                'id' => $id,
                'name' => $b['name'] ?? ('Branch #'.$id),
                'daily_sales' => $dailySales,
                'daily_expenses' => $dailyExpenses,
                'actual_sales' => $dailySales - $dailyExpenses,
                'sales_percent' => $companySales > 0 ? round(($dailySales / $companySales) * 100, 1) : 0.0,
                'transactions' => count($branchSales),
                'staff' => $staff,
            ];
        }
        usort($rows, fn ($a, $b2) => $b2['daily_sales'] <=> $a['daily_sales']);

        return response()->json([
            'rows' => $rows,
            'company_sales' => $companySales,
            'company_expenses' => $companyExpenses,
            'company_actual' => $companySales - $companyExpenses,
        ]);
    }

    /** Mirrors StockManagerController::crossBranchDashboard (stock view). */
    public function crossBranchStock()
    {
        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $rows = [];
        foreach ($branches as $b) {
            $id = (int) $b['id'];

            $branchStock = $this->supabase->query('branch_stock', [
                'select' => 'quantity,selling_price',
                'branch_id' => "eq.{$id}",
            ]);
            $bottleStock = $this->supabase->query('bottle_stock', [
                'select' => 'quantity',
                'branch_id' => "eq.{$id}",
            ]);
            $oilStock = $this->supabase->query('oil_fragrance_stock', [
                'select' => 'quantity',
                'branch_id' => "eq.{$id}",
            ]);

            $rows[] = [
                'id' => $id,
                'name' => $b['name'] ?? '',
                'address' => $b['address'] ?? null,
                'total_products' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $branchStock)),
                'low_stock' => count(array_filter($branchStock, fn ($s) => ($s['quantity'] ?? 0) <= 5)),
                'total_bottles' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $bottleStock)),
                'total_oils' => array_sum(array_map(fn ($s) => $s['quantity'] ?? 0, $oilStock)),
            ];
        }

        return response()->json(['rows' => $rows]);
    }

    /** Mirrors CashierController::crossBranchDashboard (sales view). */
    public function crossBranchSales()
    {
        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'order' => 'name.asc',
        ]);

        $today = BusinessDay::dayFilter();
        $rows = [];
        foreach ($branches as $b) {
            $id = (int) $b['id'];

            $todaySales = $this->supabase->query('sales', [
                'branch_id' => "eq.{$id}",
                'created_at' => $today,
                'select' => 'id,total,payment_status,cashier_id',
            ]);

            $todayRevenue = array_sum(array_map(fn ($s) => (float) ($s['total'] ?? 0), $todaySales));
            $cashierIds = array_unique(array_filter(array_map(fn ($s) => $s['cashier_id'] ?? null, $todaySales)));

            $rows[] = [
                'id' => $id,
                'name' => $b['name'] ?? '',
                'address' => $b['address'] ?? null,
                'today_revenue' => $todayRevenue,
                'today_transactions' => count($todaySales),
                'today_paid' => count(array_filter($todaySales, fn ($s) => ($s['payment_status'] ?? '') === 'paid')),
                'pending_orders' => $this->supabase->count('orders', [
                    'branch_id' => "eq.{$id}",
                    'status' => 'eq.pending',
                ]),
                'active_cashiers' => count($cashierIds),
            ];
        }

        return response()->json(['rows' => $rows]);
    }
}
