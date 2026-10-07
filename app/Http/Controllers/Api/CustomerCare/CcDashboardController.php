<?php

namespace App\Http\Controllers\Api\CustomerCare;

use App\Services\FinancialService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * JSON twin of customer-care.dashboard: the branch's sales, orders and
 * client figures for everyone, plus the inquiry counts and the info@
 * mailbox figures only Head Quarters ever sees (the website builds them
 * behind the same $isHq flag).
 */
class CcDashboardController extends CcBaseController
{
    /** Just the branch/HQ flags — the app shapes navigation from this. */
    public function scope(Request $request)
    {
        return response()->json(['scope' => $this->scopePayload($request)]);
    }

    public function dashboard(Request $request)
    {
        $branchId = $this->ownBranchId($request);
        $isHq = $this->isHq($request);

        // Inquiries + info@ mailbox — HQ only.
        $inquiriesCount = 0;
        $unreadInquiries = 0;
        $recentInquiries = [];
        $mailStats = null;
        $unreadMails = 0;
        $recentMails = [];

        if ($isHq) {
            $inquiriesCount = $this->supabase->count('inquiries', [
                'branch_id' => "eq.{$branchId}",
            ]);
            $unreadInquiries = $this->supabase->count('inquiries', [
                'branch_id' => "eq.{$branchId}",
                'is_read' => 'eq.false',
            ]);

            $recent = collect($this->supabase->query('inquiries', [
                'select' => '*',
                'branch_id' => "eq.{$branchId}",
                'order' => 'created_at.desc',
                'limit' => 10,
            ]));
            $recentInquiries = $recent->map(fn ($i) => $this->withInquiryUser($i))->values()->all();

            $mailSvc = $this->mails();
            $mailStats = $mailSvc->dashboardStatistics();
            $unreadMails = $mailSvc->unreadCount();
            [$inboxRows] = $mailSvc->inbox();
            $recentMails = collect($inboxRows)->take(5)->values()->all();
        }

        // Sales — branch scoped.
        $sales = collect($this->supabase->query('sales', [
            'select' => '*, items:sale_items(*, product:products(id,name))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]));

        $branches = $this->names('branches', $sales->pluck('branch_id'));
        $customers = $this->names('customers', $sales->pluck('customer_id'));
        $cashiers = $this->names('users', $sales->pluck('cashier_id'));

        $sales = $sales->map(function ($s) use ($branches, $customers, $cashiers) {
            $s = (array) $s;
            if (isset($s['items']) && is_array($s['items'])) {
                $s['items'] = array_map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) {
                        $item['product'] = (object) $item['product'];
                    }

                    return (object) $item;
                }, $s['items']);
            }
            $s['branch'] = $this->ref($s['branch_id'] ?? null, $branches);
            $s['customer'] = $this->ref($s['customer_id'] ?? null, $customers);
            $s['cashier'] = $this->ref($s['cashier_id'] ?? null, $cashiers);

            return (object) $s;
        });

        $todaySales = $sales->filter(fn ($s) => BusinessDay::withinDay($s->created_at ?? null));

        // Branch daily financials (sales, expenses, actual = sales - expenses).
        $dailySummary = (new FinancialService($this->supabase))->getDailySummary((int) $branchId);

        // Orders — branch scoped.
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
                $o['items'] = array_map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) {
                        $item['product'] = (object) $item['product'];
                    }

                    return (object) $item;
                }, $o['items']);
            }

            return (object) $o;
        });

        $statusCounts = [];
        foreach ($orders as $o) {
            $status = (string) ($o->status ?? 'pending');
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        $pendingOrders = $this->supabase->count('orders', [
            'branch_id' => "eq.{$branchId}",
            'status' => 'eq.pending',
        ]);

        // Clients (customer records are not branch-scoped; latest 100).
        $clients = collect($this->supabase->query('customers', [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 100,
        ]))->map(fn ($c) => (object) $c);

        return response()->json([
            'isHq' => $isHq,
            'inquiriesCount' => $inquiriesCount,
            'unreadInquiries' => $unreadInquiries,
            'recentInquiries' => $recentInquiries,
            'mailStats' => $mailStats,
            'unreadMails' => $unreadMails,
            'recentMails' => $recentMails,
            'sales' => $sales->values()->all(),
            'totalRevenue' => (float) $sales->sum('total'),
            'totalSalesCount' => $sales->count(),
            'todayRevenue' => (float) $todaySales->sum('total'),
            'orders' => $orders->values()->all(),
            'ordersTotal' => (float) $orders->sum('total'),
            'orderStatusCounts' => $statusCounts,
            'pendingOrders' => $pendingOrders,
            'clients' => $clients->values()->all(),
            'clientsTotal' => $clients->count(),
            'clientsWithPhone' => $clients->filter(fn ($c) => ! empty($c->phone))->count(),
            'dailySummary' => $dailySummary,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** PHP-side user join for one inquiry row (PostgREST expansions return 0 rows). */
    private function withInquiryUser(array $i): array
    {
        $i['user'] = null;
        if (! empty($i['user_id'])) {
            $user = $this->supabase->find('users', $i['user_id'], 'id,name');
            if ($user) {
                $i['user'] = ['id' => $i['user_id'], 'name' => $user['name']];
            }
        }

        return $i;
    }

    /** id => name map for a set of referenced ids (branches/customers/users). */
    private function names(string $table, $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids->all()))));
        if (empty($ids)) {
            return [];
        }

        $map = [];
        foreach ($this->supabase->query($table, [
            'select' => 'id,name',
            'id' => 'in.('.implode(',', $ids).')',
        ]) as $row) {
            $map[(int) $row['id']] = $row['name'];
        }

        return $map;
    }

    private function ref($id, array $names): ?object
    {
        if ($id === null || $id === '' || ! isset($names[(int) $id])) {
            return null;
        }

        return (object) ['id' => (int) $id, 'name' => $names[(int) $id]];
    }
}
