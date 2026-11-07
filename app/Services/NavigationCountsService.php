<?php

namespace App\Services;

use App\Models\User;
use App\Support\BusinessDay;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The numbers behind the sidebar badges, and the keys each page watches for
 * live updates.
 *
 * Every count here is work the signed-in person can act on: unread mail and
 * inquiries, orders waiting for their role, incoming transfers, pending
 * approvals, and the business day's new sales/expenses/customers. Counts are
 * scoped with the same branch rules as the pages themselves, so a badge never
 * promises a row the page will not show.
 */
class NavigationCountsService
{
    public const KEYS = [
        'orders',
        'inquiries',
        'mails',
        'notifications',
        'approvals',
        'staff',
        'transfers',
        'returns',
        'news',
        'customers',
        'sales',
        'expenses',
    ];

    /** Dashboards and a few list pages whose route does not end in .index. */
    private const EXACT_ROUTE_KEYS = [
        'super-admin.dashboard' => ['orders', 'approvals', 'notifications', 'mails', 'returns'],
        'super-admin.daily-sales' => ['sales'],
        'customer-care.dashboard' => ['orders', 'inquiries', 'mails', 'news', 'customers', 'sales'],
        'customer-care.customers.loyal' => ['customers'],
        'seller.dashboard' => ['orders', 'sales'],
        'cashier.dashboard' => ['orders', 'sales', 'expenses'],
        'cashier.daily-sales-overview' => ['sales'],
        'branch-admin.dashboard' => ['orders', 'sales', 'staff', 'expenses'],
        'stock-manager.dashboard' => ['orders', 'sales', 'transfers', 'returns'],
        'stock-manager.stock-transfers.returns' => ['returns'],
        'graphic-designer.dashboard' => ['news'],
    ];

    /** Module prefixes, used for every index/show/create/edit page in them. */
    private const PREFIX_ROUTE_KEYS = [
        'super-admin.orders.' => ['orders'],
        'super-admin.cashiers.' => ['approvals'],
        'super-admin.emails.' => ['mails'],
        'super-admin.notifications.' => ['notifications'],
        'super-admin.returned-stock.' => ['returns'],
        'customer-care.orders.' => ['orders'],
        'customer-care.inquiries.' => ['inquiries'],
        'customer-care.mails.' => ['mails'],
        'customer-care.news.' => ['news'],
        'customer-care.customers.' => ['customers'],
        'customer-care.sales.' => ['sales'],
        'seller.orders.' => ['orders'],
        'seller.sales.' => ['sales'],
        'cashier.orders.' => ['orders'],
        'cashier.sales.' => ['sales'],
        'cashier.expenses.' => ['expenses'],
        'branch-admin.orders.' => ['orders'],
        'branch-admin.sales.' => ['sales'],
        'branch-admin.staffs.' => ['staff'],
        'branch-admin.expenses.' => ['expenses'],
        'stock-manager.orders.' => ['orders'],
        'stock-manager.sales.' => ['sales'],
        'stock-manager.stock-transfers.' => ['transfers'],
        'stock-manager.returned-stock.' => ['returns'],
        'graphic-designer.news.' => ['news'],
    ];

    private SupabaseService $supabase;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    /**
     * @return array<string, int>
     */
    public function forUser(?User $user): array
    {
        $counts = array_fill_keys(self::KEYS, 0);

        if (! $user) {
            return $counts;
        }

        try {
            return match ($user->role) {
                'super_admin' => $this->forSuperAdmin($counts),
                'customer_care' => $this->forCustomerCare($counts, $user),
                'seller' => $this->forSeller($counts, $user),
                'cashier' => $this->forCashier($counts, $user),
                'branch_admin' => $this->forBranchAdmin($counts, $user),
                'stock_manager' => $this->forStockManager($counts, $user),
                'graphic_designer' => $this->forGraphicDesigner($counts, $user),
                default => $counts,
            };
        } catch (Throwable $e) {
            // A badge must never take a page down; the next poll tries again.
            Log::warning('Navigation counts failed: '.$e->getMessage());

            return array_fill_keys(self::KEYS, 0);
        }
    }

    /**
     * The badge keys a page reflects, so it only reloads for data it shows.
     *
     * @return array<int, string>
     */
    public function keysForRoute(?string $route): array
    {
        if (! is_string($route) || $route === '') {
            return [];
        }

        if (isset(self::EXACT_ROUTE_KEYS[$route])) {
            return self::EXACT_ROUTE_KEYS[$route];
        }

        foreach (self::PREFIX_ROUTE_KEYS as $prefix => $keys) {
            if (str_starts_with($route, $prefix)) {
                return $keys;
            }
        }

        return [];
    }

    /**
     * Only list-style screens reload themselves. Detail, create, edit and
     * print screens are left alone so a reader is never pulled out of a record
     * they are working on.
     */
    public function shouldAutoRefresh(?string $route): bool
    {
        if (! is_string($route) || $route === '' || $this->keysForRoute($route) === []) {
            return false;
        }

        if (str_ends_with($route, '.index') || str_ends_with($route, '.dashboard')) {
            return true;
        }

        return in_array($route, [
            'cashier.daily-sales-overview',
            'super-admin.daily-sales',
            'customer-care.customers.loyal',
            'stock-manager.stock-transfers.incoming',
            'stock-manager.stock-transfers.returns',
        ], true);
    }

    private function forSuperAdmin(array $counts): array
    {
        $counts['orders'] = $this->count('orders', [
            'status' => 'in.(pending,awaiting_care)',
        ]);

        $counts['sales'] = $this->countToday('sales');

        $counts['approvals'] = $this->count('users', [
            'role' => 'in.(cashier,branch_admin,stock_manager,customer_care,seller,graphic_designer)',
            'status' => 'eq.pending',
        ]);

        $counts['notifications'] = $this->count('admin_notifications', [
            'is_read' => 'eq.false',
        ]);

        $counts['mails'] = $this->unreadMails();
        $counts['returns'] = $this->pendingDamageReports();

        return $counts;
    }

    private function forCustomerCare(array $counts, User $user): array
    {
        $branchId = (int) ($user->branch_id ?? 0);

        $counts['orders'] = $this->count('orders', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.'.OrderWorkflowService::CARE_STATUS,
        ]);

        $counts['customers'] = $this->countToday('customers');
        $counts['sales'] = $this->countToday('sales', ['branch_id' => 'eq.'.$branchId]);

        $isHq = (new CustomerCareScope($this->supabase))->isHqCustomerCare();

        if ($isHq) {
            $counts['inquiries'] = $this->count('inquiries', [
                'branch_id' => 'eq.'.$branchId,
                'is_read' => 'eq.false',
            ]);

            $counts['mails'] = $this->unreadMails();
            $counts['news'] = $this->pendingNews();
        }

        return $counts;
    }

    private function forSeller(array $counts, User $user): array
    {
        $branchId = (int) ($user->branch_id ?? 0);
        $userId = $user->supabase_id ?: $user->id;

        $counts['orders'] = $this->count('orders', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.pending',
        ]);

        $counts['sales'] = $this->countToday('sales', ['cashier_id' => 'eq.'.$userId]);

        return $counts;
    }

    private function forCashier(array $counts, User $user): array
    {
        $branchId = $this->cashierBranchId($user);

        $counts['orders'] = $this->count('orders', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.pending',
        ]);

        $counts['sales'] = $this->countToday('sales', ['branch_id' => 'eq.'.$branchId]);
        $counts['expenses'] = $this->countToday('expenses', ['branch_id' => 'eq.'.$branchId]);

        return $counts;
    }

    private function forBranchAdmin(array $counts, User $user): array
    {
        $branchId = (int) ($user->branch_id ?? 0);

        $counts['orders'] = $this->count('orders', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.pending',
        ]);

        $counts['sales'] = $this->countToday('sales', ['branch_id' => 'eq.'.$branchId]);
        $counts['expenses'] = $this->countToday('expenses', ['branch_id' => 'eq.'.$branchId]);

        $counts['staff'] = $this->count('users', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.pending',
        ]);

        return $counts;
    }

    private function forStockManager(array $counts, User $user): array
    {
        $branchId = $this->stockBranchId($user);
        $scope = new StockManagerScope($this->supabase);

        $counts['orders'] = $this->count('orders', [
            'branch_id' => 'eq.'.$branchId,
            'status' => 'eq.pending',
        ]);

        $counts['sales'] = $this->countToday('sales', [
            'branch_id' => 'eq.'.$branchId,
            'cashier_id' => 'eq.'.($user->supabase_id ?: $user->id),
        ]);

        $incoming = [
            'to_branch_id' => 'eq.'.$branchId,
            'status' => 'eq.in_transit',
        ];

        // A products-only branch only receives Product Stock; mirror the
        // incoming page filter so the badge cannot count hidden bottle rows.
        if ($scope->isProductsOnlyStockManager()) {
            $incoming['stock_type'] = 'eq.product';
        }

        $counts['transfers'] = $this->count('stock_transfers', $incoming);

        if ($scope->isKinondoniStockManager()) {
            $counts['returns'] = $this->pendingDamageReports($branchId);
        }

        return $counts;
    }

    private function forGraphicDesigner(array $counts, User $user): array
    {
        $userId = $user->supabase_id ?: $user->id;
        $counts['news'] = $this->pendingNews($userId);

        return $counts;
    }

    private function cashierBranchId(User $user): int
    {
        $monitoring = (int) session('cashier_cross_branch_id', 0);

        return $monitoring > 0 ? $monitoring : (int) ($user->branch_id ?? 0);
    }

    private function stockBranchId(User $user): int
    {
        $monitoring = (int) session('cross_branch_id', 0);

        return $monitoring > 0 ? $monitoring : (int) ($user->branch_id ?? 0);
    }

    private function unreadMails(): int
    {
        try {
            return app(InfoMailService::class)->unreadCount();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Rows created during the shop's current business day. The date boundary
     * is Dar es Salaam time, the same one every "today" panel uses.
     */
    private function countToday(string $table, array $conditions = []): int
    {
        try {
            $rows = $this->supabase->query($table, array_merge($conditions, [
                'select' => 'id,created_at',
                'limit' => 2000,
            ]));

            return collect($rows)
                ->filter(fn ($row) => BusinessDay::withinDay($row['created_at'] ?? null))
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function pendingNews(?int $authorId = null): int
    {
        try {
            $conditions = [];

            if ($this->supabase->tableHasColumn('news_posts', 'status')) {
                $conditions['status'] = 'eq.pending';
            } else {
                $conditions['is_published'] = 'eq.false';
            }

            if ($authorId !== null) {
                $conditions['author_id'] = 'eq.'.$authorId;
            }

            return $this->count('news_posts', $conditions);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Returned items still waiting for the mandatory lost / broken report.
     * The Super Admin sees every branch; a stock manager sees their own.
     */
    private function pendingDamageReports(?int $fromBranchId = null): int
    {
        try {
            $select = 'id,return_status';

            if ($this->supabase->tableHasColumn('stock_transfer_items', 'damage_reported_at')) {
                $select .= ',damage_reported_at';
            }

            $params = [
                'select' => $select,
                'status' => 'eq.returned',
                'limit' => 1000,
            ];

            if ($fromBranchId !== null) {
                $transfers = $this->supabase->query('stock_transfers', [
                    'select' => 'id',
                    'from_branch_id' => 'eq.'.$fromBranchId,
                    'limit' => 1000,
                ]);

                $ids = array_values(array_map(
                    fn ($transfer) => (int) ($transfer['id'] ?? 0),
                    $transfers
                ));

                $ids = array_values(array_filter($ids, fn ($id) => $id > 0));

                if ($ids === []) {
                    return 0;
                }

                $params['transfer_id'] = 'in.('.implode(',', $ids).')';
            }

            return collect($this->supabase->query('stock_transfer_items', $params))
                ->filter(function ($item) {
                    $status = $item['return_status'] ?? 'pending';

                    return ! in_array($status, ['reported', 'resent'], true)
                        && empty($item['damage_reported_at']);
                })
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function count(string $table, array $conditions = []): int
    {
        try {
            return (int) $this->supabase->count($table, $conditions);
        } catch (Throwable $e) {
            return 0;
        }
    }
}
