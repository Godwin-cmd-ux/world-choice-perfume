<?php

namespace App\Http\Controllers\Api\CustomerCare;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care Clients screens: the branch-tabbed client
 * list with search, the create form's store action, and the client record
 * with their transactions. Customers themselves are not branch scoped —
 * membership is derived from sales.branch_id, exactly as the website does.
 */
class CcCustomerController extends CcBaseController
{
    /** Rows returned per page (the codebase does not paginate anywhere). */
    private const LIST_LIMIT = 50;

    /** Branch sales scanned to find a branch's most recent visitors. */
    private const VISIT_SCAN = 1000;

    /** Cap on the single scan used for the tab counters. */
    private const COUNT_SCAN = 2000;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address,is_active',
            'order' => 'name.asc',
        ]);
        $branchIds = array_map(fn ($b) => (int) $b['id'], $branches);

        $requestedBranch = (int) $request->query('branch', 0);
        $tab = in_array($requestedBranch, $branchIds, true) ? $requestedBranch : 0;

        $searching = $q !== '' && mb_strlen($q) >= 2;
        $truncated = false;

        if ($tab === 0) {
            $customers = $searching
                ? $this->searchCustomers($q)
                : collect($this->supabase->query('customers', [
                    'select' => '*',
                    'order' => 'created_at.desc',
                    'limit' => self::LIST_LIMIT,
                ]))->map(fn ($c) => (object) $c);
        } else {
            if ($searching) {
                $matches = $this->searchCustomers($q);
                $visitors = $this->visitorsAmong($matches->pluck('id')->all(), $tab);
                $customers = $matches->filter(fn ($c) => isset($visitors[(int) $c->id]))->values();
            } else {
                $ids = $this->recentVisitors($tab, self::LIST_LIMIT, $truncated);
                $customers = $this->customersByIds($ids);
            }
        }

        $totalCustomers = $this->supabase->count('customers', []);
        $counts = $this->customerCountsByBranch();

        // Tabs worth showing: every active branch, plus inactive ones that still have customers.
        $tabs = collect($branches)
            ->filter(fn ($b) => ($b['is_active'] ?? true) || ($counts[(int) $b['id']] ?? 0) > 0)
            ->map(fn ($b) => [
                'id' => (int) $b['id'],
                'name' => $b['name'],
                'count' => $counts[(int) $b['id']] ?? 0,
            ])
            ->values()
            ->all();

        $branchNames = [];
        $tabName = 'All Customers';
        foreach ($branches as $b) {
            $branchNames[(int) $b['id']] = $b['name'];
            if ($tab === (int) $b['id']) {
                $tabName = $b['name'];
            }
        }

        return response()->json([
            'customers' => $customers->values()->all(),
            'q' => $q,
            'tab' => $tab,
            'tabName' => $tabName,
            'tabs' => $tabs,
            'counts' => $counts,
            'totalCustomers' => $totalCustomers,
            'branchNames' => $branchNames,
            'truncated' => $truncated,
            'visits' => $this->visitSummary($customers->pluck('id')->all()),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Create one client. A duplicate phone answers 200 with the existing
     * record — the website redirects to it, the app navigates to it.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if (! empty($validated['phone'])) {
            $existing = $this->supabase->findOne('customers', ['phone' => $validated['phone']]);
            if ($existing) {
                return response()->json([
                    'message' => 'A customer with this phone number already exists. Showing their record instead.',
                    'duplicate' => true,
                    'customer' => $existing,
                ]);
            }
        }

        $customer = $this->supabase->insert('customers', [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? ($validated['phone'] ?? null),
            'email' => $validated['email'] ?? null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $customer) {
            $this->fail(['error' => 'Could not create the customer. Please try again.']);
        }

        return response()->json([
            'message' => 'Customer created successfully.',
            'duplicate' => false,
            'customer' => $customer,
        ]);
    }

    /**
     * Client record with the transactions they made, switchable per branch.
     * ?branch=<id> picks the branch; it defaults to the branch they visited
     * last; "all" keeps every transaction.
     */
    public function show(Request $request, int $customerId)
    {
        $customer = $this->supabase->find('customers', $customerId);
        if (! $customer) {
            abort(404, 'Customer not found.');
        }

        $select = 'id,sale_number,total,branch_id,created_at,payment_status,payment_summary';
        if ($this->supabase->tableHasColumn('sales', 'sale_type')) {
            $select .= ',sale_type';
        }

        $salesRows = $this->supabase->query('sales', [
            'select' => $select,
            'customer_id' => "eq.{$customerId}",
            'order' => 'created_at.asc',
            'limit' => 500,
        ]);

        // Which branches this client transacted at, and how often.
        $visitsByBranch = [];
        foreach ($salesRows as $s) {
            $bid = (int) ($s['branch_id'] ?? 0);
            if (! $bid) {
                continue;
            }
            if (! isset($visitsByBranch[$bid])) {
                $visitsByBranch[$bid] = [
                    'id' => $bid,
                    'count' => 0,
                    'first_visit' => $s['created_at'] ?? null,
                    'last_visit' => null,
                ];
            }
            $visitsByBranch[$bid]['count'] += 1;
            $visitsByBranch[$bid]['last_visit'] = $s['created_at'] ?? null;
        }

        $branchDetails = [];
        if ($visitsByBranch) {
            foreach ($this->supabase->query('branches', [
                'select' => 'id,name,address',
                'id' => 'in.('.implode(',', array_keys($visitsByBranch)).')',
            ]) as $b) {
                $branchDetails[(int) $b['id']] = $b;
            }
        }

        // Branch switcher: newest branch first, so the default is the latest visit.
        $branchTabs = collect($visitsByBranch)
            ->sortByDesc('last_visit')
            ->map(fn ($visit) => [
                'id' => (int) $visit['id'],
                'name' => $branchDetails[$visit['id']]['name'] ?? ('Branch #'.$visit['id']),
                'address' => $branchDetails[$visit['id']]['address'] ?? null,
                'count' => $visit['count'],
                'last_visit' => $visit['last_visit'],
            ])
            ->values()
            ->all();

        $requested = (string) $request->query('branch', '');
        $selected = 'all';
        if ($requested !== 'all') {
            foreach ($branchTabs as $t) {
                if ((string) $t['id'] === $requested) {
                    $selected = $requested;
                    break;
                }
            }
            if ($selected === 'all' && $branchTabs) {
                $selected = (string) $branchTabs[0]['id'];
            }
        }

        $transactions = collect($salesRows)->filter(function ($s) use ($selected) {
            return $selected === 'all' || (string) ($s['branch_id'] ?? '') === $selected;
        })->map(function ($s) use ($branchDetails) {
            return [
                'id' => $s['id'],
                'sale_number' => $s['sale_number'] ?? null,
                'branch_id' => (int) ($s['branch_id'] ?? 0),
                'branch_name' => $branchDetails[(int) ($s['branch_id'] ?? 0)]['name'] ?? null,
                'total' => (float) ($s['total'] ?? 0),
                'payment_status' => $s['payment_status'] ?? null,
                'payment_summary' => $s['payment_summary'] ?? null,
                'sale_type' => $s['sale_type'] ?? null,
                'created_at' => $s['created_at'] ?? null,
            ];
        })->values();

        // Line items for the transactions in view (PHP-side joins).
        $saleIds = $transactions->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $itemsBySale = [];
        $productIds = [];
        if ($saleIds) {
            foreach ($this->supabase->query('sale_items', [
                'select' => 'sale_id,product_id,quantity,unit_price,total',
                'sale_id' => 'in.('.implode(',', $saleIds).')',
            ]) as $it) {
                $itemsBySale[(int) $it['sale_id']][] = $it;
                if (! empty($it['product_id'])) {
                    $productIds[(int) $it['product_id']] = true;
                }
            }
        }

        $productNames = [];
        if ($productIds) {
            foreach ($this->supabase->query('products', [
                'select' => 'id,name,brand',
                'id' => 'in.('.implode(',', array_keys($productIds)).')',
            ]) as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }

        $itemSummary = [];
        foreach ($transactions as $txn) {
            $txn['items'] = collect($itemsBySale[(int) $txn['id']] ?? [])->map(function ($it) use ($productNames) {
                $p = $productNames[(int) ($it['product_id'] ?? 0)] ?? null;

                return [
                    'name' => $p['name'] ?? ('Product #'.($it['product_id'] ?? '?')),
                    'brand' => $p['brand'] ?? null,
                    'quantity' => (int) ($it['quantity'] ?? 0),
                    'unit_price' => (float) ($it['unit_price'] ?? 0),
                    'total' => (float) ($it['total'] ?? 0),
                ];
            })->values()->all();

            foreach ($txn['items'] as $it) {
                $key = $it['name'];
                if (! isset($itemSummary[$key])) {
                    $itemSummary[$key] = [
                        'name' => $it['name'],
                        'brand' => $it['brand'],
                        'times' => 0,
                        'quantity' => 0,
                        'spent' => 0.0,
                        'last_bought' => null,
                    ];
                }
                $itemSummary[$key]['times'] += 1;
                $itemSummary[$key]['quantity'] += $it['quantity'];
                $itemSummary[$key]['spent'] += $it['total'];
                $itemSummary[$key]['last_bought'] = $txn['created_at'];
            }
        }

        $itemSummary = collect(array_values($itemSummary))
            ->sortByDesc('quantity')
            ->values()
            ->all();

        $selectedName = 'All Branches';
        if ($selected !== 'all') {
            foreach ($branchTabs as $t) {
                if ((string) $t['id'] === $selected) {
                    $selectedName = $t['name'];
                }
            }
        }

        $whatsapp = new WhatsAppService();

        return response()->json([
            'customer' => $customer,
            'whatsappLink' => $whatsapp->chatLink($customer['whatsapp'] ?? null),
            'emailLink' => $this->mailtoFor($customer['email'] ?? null),
            'branchTabs' => $branchTabs,
            'selected' => $selected,
            'selectedName' => $selectedName,
            'transactions' => $transactions->all(),
            'itemSummary' => $itemSummary,
            'totalSpent' => (float) $transactions->sum('total'),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** A mailto: link only when what is stored really is an address. */
    private function mailtoFor(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : null;
    }

    /**
     * Search clients by name, phone or whatsapp — PostgREST cannot OR across
     * columns, so each column is queried and the hits are merged.
     */
    private function searchCustomers(string $q, int $limit = self::LIST_LIMIT)
    {
        $like = 'ilike.*'.urlencode($q).'*';

        $hits = array_merge(
            $this->supabase->query('customers', ['select' => '*', 'name' => $like, 'order' => 'name.asc', 'limit' => $limit]),
            $this->supabase->query('customers', ['select' => '*', 'phone' => $like, 'order' => 'name.asc', 'limit' => $limit]),
            $this->supabase->query('customers', ['select' => '*', 'whatsapp' => $like, 'order' => 'name.asc', 'limit' => $limit]),
        );

        $merged = [];
        foreach ($hits as $c) {
            $merged[$c['id']] = $c;
        }

        return collect(array_slice(array_values($merged), 0, $limit))->map(fn ($c) => (object) $c);
    }

    /** Load customer records by id, keeping the order they were requested in. */
    private function customersByIds(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids) {
            return collect();
        }

        $byId = [];
        foreach ($this->supabase->query('customers', [
            'select' => '*',
            'id' => 'in.('.implode(',', $ids).')',
        ]) as $row) {
            $byId[(int) $row['id']] = (object) $row;
        }

        return collect($ids)->map(fn ($id) => $byId[$id] ?? null)->filter()->values();
    }

    /**
     * Customer ids of the most recent visitors to a branch, newest first.
     * $truncated is set when the branch has more sales than the scan window.
     */
    private function recentVisitors(int $branchId, int $limit, bool &$truncated = false): array
    {
        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id',
            'branch_id' => 'eq.'.$branchId,
            'order' => 'created_at.desc',
            'limit' => self::VISIT_SCAN,
        ]);

        if (count($rows) >= self::VISIT_SCAN) {
            $truncated = true;
        }

        $ids = [];
        $seen = [];
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if (! $customerId || isset($seen[$customerId])) {
                continue;
            }
            $seen[$customerId] = true;
            $ids[] = $customerId;
            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
    }

    /** Which of the given customers transacted at a branch — key customer id => true. */
    private function visitorsAmong(array $customerIds, int $branchId): array
    {
        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (! $customerIds) {
            return [];
        }

        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id',
            'customer_id' => 'in.('.implode(',', $customerIds).')',
            'branch_id' => 'eq.'.$branchId,
            'limit' => count($customerIds) * 10,
        ]);

        $visitors = [];
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if ($customerId) {
                $visitors[$customerId] = true;
            }
        }

        return $visitors;
    }

    /**
     * Distinct customers per branch from a single scan of the sales history.
     * Empty map when the history is larger than the scan window, so the app
     * hides the counters rather than shows an undercount.
     */
    private function customerCountsByBranch(): array
    {
        $rows = $this->supabase->query('sales', [
            'select' => 'branch_id,customer_id',
            'limit' => self::COUNT_SCAN,
        ]);

        if (count($rows) >= self::COUNT_SCAN) {
            return [];
        }

        $counts = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            $customerId = (int) ($row['customer_id'] ?? 0);
            if (! $branchId || ! $customerId) {
                continue;
            }
            $counts[$branchId][$customerId] = true;
        }

        return array_map(fn ($set) => count($set), $counts);
    }

    /** last_visit + branch ids for the customers currently on screen. */
    private function visitSummary(array $customerIds): array
    {
        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (! $customerIds) {
            return [];
        }

        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id,branch_id,created_at',
            'customer_id' => 'in.('.implode(',', $customerIds).')',
            'order' => 'created_at.desc',
            'limit' => self::VISIT_SCAN,
        ]);

        $summary = [];
        foreach ($customerIds as $id) {
            $summary[$id] = ['last_visit' => null, 'branches' => []];
        }
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if (! isset($summary[$customerId])) {
                continue;
            }
            if ($summary[$customerId]['last_visit'] === null) {
                $summary[$customerId]['last_visit'] = $row['created_at'] ?? null;
            }
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId) {
                $summary[$customerId]['branches'][$branchId] = true;
            }
        }

        return $summary;
    }
}
