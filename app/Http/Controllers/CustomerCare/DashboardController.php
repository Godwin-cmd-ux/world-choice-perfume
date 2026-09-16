<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\CustomerCareScope;
use App\Services\FinancialService;
use App\Services\SupabaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private SupabaseService $supabase;
    private FinancialService $financials;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->financials = new FinancialService();
    }

    public function index()
    {
        $branchId = auth()->user()->branch_id;
        $isHq = (new CustomerCareScope())->isHqCustomerCare();

        // Inquiries (HQ only)
        $inquiriesCount = 0;
        $unreadInquiries = 0;
        $recentInquiries = collect();

        if ($isHq) {
            $inquiriesCount = $this->supabase->count('inquiries', [
                'branch_id' => "eq.{$branchId}",
            ]);

            $unreadInquiries = $this->supabase->count('inquiries', [
                'branch_id' => "eq.{$branchId}",
                'is_read' => 'eq.false',
            ]);

            $recentInquiries = collect($this->supabase->query('inquiries', [
                'select' => '*',
                'branch_id' => "eq.{$branchId}",
                'order' => 'created_at.desc',
                'limit' => 10,
            ]));

            $userIds = $recentInquiries->pluck('user_id')->filter()->unique()->values()->toArray();
            $users = [];
            if (!empty($userIds)) {
                $usersList = $this->supabase->query('users', [
                    'select' => 'id,name',
                    'id' => 'in.(' . implode(',', $userIds) . ')',
                ]);
                foreach ($usersList as $u) {
                    $users[$u['id']] = $u['name'];
                }
            }

            $recentInquiries = $recentInquiries->map(function ($i) use ($users) {
                $i['user'] = isset($i['user_id']) && isset($users[$i['user_id']])
                    ? (object) ['id' => $i['user_id'], 'name' => $users[$i['user_id']]] : null;
                return (object) $i;
            });
        }

        // Sales — branch scoped
        $sales = collect($this->supabase->query('sales', [
            'select' => '*, items:sale_items(*, product:products(id,name))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]));

        $saleCustomerIds = $sales->pluck('customer_id')->filter()->unique()->values()->toArray();
        $saleCashierIds = $sales->pluck('cashier_id')->filter()->unique()->values()->toArray();
        $saleBranchIds = $sales->pluck('branch_id')->filter()->unique()->values()->toArray();

        $branches = [];
        if (!empty($saleBranchIds)) {
            foreach ($this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $saleBranchIds) . ')',
            ]) as $b) {
                $branches[$b['id']] = $b['name'];
            }
        }

        $customers = [];
        if (!empty($saleCustomerIds)) {
            foreach ($this->supabase->query('customers', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $saleCustomerIds) . ')',
            ]) as $c) {
                $customers[$c['id']] = $c['name'];
            }
        }

        $cashiers = [];
        if (!empty($saleCashierIds)) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $saleCashierIds) . ')',
            ]) as $u) {
                $cashiers[$u['id']] = $u['name'];
            }
        }

        $sales = $sales->map(function ($s) use ($branches, $customers, $cashiers) {
            $s = (array) $s;
            if (isset($s['items']) && is_array($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) {
                        $item['product'] = (object) $item['product'];
                    }
                    return (object) $item;
                });
            }
            $s['branch'] = isset($s['branch_id']) && isset($branches[$s['branch_id']])
                ? (object) ['id' => $s['branch_id'], 'name' => $branches[$s['branch_id']]]
                : null;
            $s['customer'] = isset($s['customer_id']) && isset($customers[$s['customer_id']])
                ? (object) ['id' => $s['customer_id'], 'name' => $customers[$s['customer_id']]]
                : null;
            $s['cashier'] = isset($s['cashier_id']) && isset($cashiers[$s['cashier_id']])
                ? (object) ['id' => $s['cashier_id'], 'name' => $cashiers[$s['cashier_id']]]
                : null;
            return (object) $s;
        });

        $totalRevenue = $sales->sum('total');
        $totalSalesCount = $sales->count();
        $todayStart = Carbon::now('Africa/Dar_es_Salaam')->startOfDay()->toIso8601String();
        $todaySales = $sales->filter(fn($s) => ($s->created_at ?? '') >= $todayStart);
        $todayRevenue = $todaySales->sum('total');

        // Branch daily financials (sales, expenses, actual = sales - expenses)
        $dailySummary = $this->financials->getDailySummary((int) $branchId);

        // Orders — branch scoped
        $orders = collect($this->supabase->query('orders', [
            'select' => '*, cashier:users!orders_cashier_id_fkey(id,name), customer:customers(id,name,phone), items:order_items(*, product:products(id,name))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]))->map(function ($o) {
            if (isset($o['cashier']) && is_array($o['cashier'])) {
                $o['cashier'] = (object) $o['cashier'];
            }
            if (isset($o['customer']) && is_array($o['customer'])) {
                $o['customer'] = (object) $o['customer'];
            }
            if (isset($o['items']) && is_array($o['items'])) {
                $o['items'] = collect($o['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) {
                        $item['product'] = (object) $item['product'];
                    }
                    return (object) $item;
                });
            }
            return (object) $o;
        });

        $ordersTotal = $orders->sum('total');
        $orderStatusCounts = $orders->groupBy('status')->map->count();

        // Clients — branch scoped where possible, otherwise all (customer records are not always branch-scoped)
        $clients = collect($this->supabase->query('customers', [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 100,
        ]))->map(fn($c) => (object) $c);

        $clientsTotal = $clients->count();
        $clientsWithPhone = $clients->filter(fn($c) => !empty($c->phone))->count();

        return view('customer-care.dashboard', compact(
            'inquiriesCount',
            'unreadInquiries',
            'recentInquiries',
            'isHq',
            'sales',
            'totalRevenue',
            'totalSalesCount',
            'todayRevenue',
            'orders',
            'ordersTotal',
            'orderStatusCounts',
            'clients',
            'clientsTotal',
            'clientsWithPhone',
            'dailySummary',
        ));
    }
}
