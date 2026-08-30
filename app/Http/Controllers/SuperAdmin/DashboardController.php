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
        $today = Carbon::today()->toDateString();
        $now = Carbon::now()->toIso8601String();
        $todayStart = Carbon::today()->toIso8601String();

        // 1. Today's sales — fetch all and sum by branch
        $todaySalesRaw = $this->supabase->query('sales', [
            'select' => 'branch_id,total',
            'created_at' => "gte.{$todayStart}",
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

        // 2. Pending orders — fetch all pending and group by branch
        $pendingOrdersRaw = $this->supabase->queryFresh('orders', [
            'select' => 'id,branch_id,created_at,order_number,status',
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

        // 3. Assigned (in-progress) orders per branch
        $assignedOrdersRaw = $this->supabase->queryFresh('orders', [
            'select' => 'id,branch_id,status',
            'status' => 'eq.assigned',
        ]);
        $readyOrdersRaw = $this->supabase->queryFresh('orders', [
            'select' => 'id,branch_id,status',
            'status' => 'eq.ready',
        ]);
        $inProgressByBranch = [];
        foreach (array_merge($assignedOrdersRaw, $readyOrdersRaw) as $o) {
            $bid = $o['branch_id'] ?? null;
            if ($bid) {
                $inProgressByBranch[$bid] = ($inProgressByBranch[$bid] ?? 0) + 1;
            }
        }

        // 4. Pending approvals
        $pendingCashiers = $this->supabase->count('users', [
            'role' => 'eq.cashier',
            'status' => 'eq.pending',
        ]);
        $pendingAdmins = $this->supabase->count('users', [
            'role' => 'eq.branch_admin',
            'status' => 'eq.pending',
        ]);

        // 5. Fetch branches
        $rawBranches = $this->supabase->query('branches', [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        // 6. Cashier counts per branch (one query)
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

        $activeBranches = count(array_filter($rawBranches, fn($b) => $b['is_active'] ?? false));

        // Build branch objects for the view
        // Fetch customer names in bulk for pending orders
        $allCustomerIds = array_unique(array_map(fn($o) => $o['customer_id'] ?? null, $pendingOrdersRaw));
        $allCustomerIds = array_filter($allCustomerIds);
        $customerNames = [];
        if (!empty($allCustomerIds)) {
            $customerIdsStr = implode(',', $allCustomerIds);
            $customers = $this->supabase->query('customers', [
                'select' => 'id,name',
                'id' => "in.({$customerIdsStr})",
            ]);
            foreach ($customers as $c) {
                $customerNames[$c['id']] = $c['name'] ?? 'Walk-in';
            }
        }

        $branches = collect($rawBranches)->map(function ($branch) use ($cashierCounts, $todaySalesByBranch, $pendingOrdersByBranch, $inProgressByBranch, $customerNames) {
            $branchId = $branch['id'];
            $branch['cashiers_count'] = $cashierCounts[$branchId] ?? 0;
            $branch['today_sales'] = $todaySalesByBranch[$branchId] ?? 0;

            // Pending orders for this branch with duration
            $branchPending = $pendingOrdersByBranch[$branchId] ?? [];
            $branch['pending_orders'] = collect($branchPending)->map(function ($order) use ($customerNames) {
                $createdAt = Carbon::parse($order['created_at']);
                $minutesAgo = (int) $createdAt->diffInMinutes(now());
                return (object) [
                    'id' => $order['id'],
                    'order_number' => $order['order_number'] ?? 'N/A',
                    'customer_name' => $customerNames[$order['customer_id'] ?? null] ?? 'Walk-in',
                    'created_at' => $order['created_at'],
                    'minutes_ago' => $minutesAgo,
                    'duration_label' => $minutesAgo < 60 ? "{$minutesAgo}m" : ($minutesAgo < 1440 ? floor($minutesAgo / 60) . "h " . ($minutesAgo % 60) . 'm' : floor($minutesAgo / 1440) . 'd ' . floor(($minutesAgo % 1440) / 60) . 'h'),
                ];
            });
            $branch['pending_count'] = count($branchPending);
            $branch['in_progress_count'] = $inProgressByBranch[$branchId] ?? 0;

            return (object) $branch;
        });

        // Financials
        $todayFinancials = $this->financialService->getCompanyFinancials(
            Carbon::today(), Carbon::now()
        );

        return view('super-admin.dashboard', [
            'branches' => $branches,
            'todayTotalRevenue' => $todayTotalRevenue,
            'totalSalesCount' => count($todaySalesRaw),
            'pendingOrders' => count($pendingOrdersRaw),
            'pendingCashiers' => $pendingCashiers,
            'pendingAdmins' => $pendingAdmins,
            'activeBranches' => $activeBranches,
            'todayFinancials' => $todayFinancials,
            'pendingCount' => $pendingCashiers + $pendingAdmins,
        ]);
    }
}
