<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /** Rows rendered per page (the codebase does not paginate anywhere). */
    private const LIST_LIMIT = 50;

    /** Branch sales scanned to find a branch's most recent visitors. */
    private const VISIT_SCAN = 1000;

    /** Cap on the single scan used for the tab counters. */
    private const COUNT_SCAN = 2000;

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Client records grouped by the branches they have transacted at.
     *
     * The tabs are built from the branches table, so a branch added later shows up
     * on its own without touching this code. Customers themselves are not branch
     * scoped, so membership is derived from sales.branch_id.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->q);

        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address,is_active',
            'order' => 'name.asc',
        ]);
        $branchIds = array_map(fn ($b) => (int) $b['id'], $branches);

        // Open tab: 0 for "all customers", otherwise a branch id that still exists
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
                // Keep only the matches that really transacted at this branch
                $matches = $this->searchCustomers($q);
                $visitors = $this->visitorsAmong($matches->pluck('id')->all(), $tab);
                $customers = $matches->filter(fn ($c) => isset($visitors[(int) $c->id]))->values();
            } else {
                // Most recent visitors to this branch, newest first
                $ids = $this->recentVisitors($tab, self::LIST_LIMIT, $truncated);
                $customers = $this->customersByIds($ids);
            }
        }

        $totalCustomers = $this->supabase->count('customers', []);

        // Counter on each tab, from one bounded scan. Dropped when the history no
        // longer fits in a single scan, so a tab never shows a wrong number.
        $counts = $this->customerCountsByBranch();

        // Tabs worth showing: every active branch, plus inactive ones that still have customers
        $tabs = collect($branches)
            ->filter(fn ($b) => ($b['is_active'] ?? true) || ($counts[(int) $b['id']] ?? 0) > 0)
            ->map(fn ($b) => (object) [
                'id' => (int) $b['id'],
                'name' => $b['name'],
                'count' => $counts[(int) $b['id']] ?? 0,
            ])
            ->values();

        $branchNames = [];
        $tabName = 'All Customers';
        foreach ($branches as $b) {
            $branchNames[(int) $b['id']] = $b['name'];
            if ($tab === (int) $b['id']) $tabName = $b['name'];
        }

        return view('customer-care.customers.index', [
            'customers' => $customers,
            'q' => $q,
            'tab' => $tab,
            'tabName' => $tabName,
            'tabs' => $tabs,
            'counts' => $counts,
            'totalCustomers' => $totalCustomers,
            'branchNames' => $branchNames,
            'truncated' => $truncated,
            'visits' => $this->visitSummary($customers->pluck('id')->all()),
        ]);
    }

    public function create()
    {
        return view('customer-care.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        // Avoid duplicate customers with the same phone number
        if (!empty($validated['phone'])) {
            $existing = $this->supabase->findOne('customers', ['phone' => $validated['phone']]);
            if ($existing) {
                return redirect()->route('customer-care.customers.show', $existing['id'])
                    ->with('error', 'A customer with this phone number already exists. Showing their record instead.');
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

        if (!$customer) {
            return back()->with('error', 'Could not create the customer. Please try again.')
                ->withInput();
        }

        return redirect()->route('customer-care.customers.show', $customer['id'])
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Client record with the transactions they made at one branch.
     *
     * ?branch=<id> picks the branch; it defaults to the branch they visited last.
     * Transactions are returned oldest first, so the newest one sits at the bottom.
     */
    public function show(Request $request, $customerId)
    {
        $customer = $this->supabase->find('customers', $customerId);
        if (!$customer) abort(404);

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

        // Which branches this client transacted at, and how often
        $branchIds = [];
        $visitsByBranch = [];
        foreach ($salesRows as $s) {
            $bid = (int) ($s['branch_id'] ?? 0);
            if (!$bid) continue;
            $branchIds[$bid] = true;
            if (!isset($visitsByBranch[$bid])) {
                $visitsByBranch[$bid] = (object) [
                    'id' => $bid,
                    'count' => 0,
                    'first_visit' => $s['created_at'] ?? null,
                    'last_visit' => null,
                ];
            }
            $visitsByBranch[$bid]->count += 1;
            $visitsByBranch[$bid]->last_visit = $s['created_at'] ?? null;
        }

        $branchDetails = [];
        if ($branchIds) {
            $list = $this->supabase->query('branches', [
                'select' => 'id,name,address',
                'id' => 'in.(' . implode(',', array_keys($branchIds)) . ')',
            ]);
            foreach ($list as $b) $branchDetails[(int) $b['id']] = $b;
        }

        // Branch switcher: newest branch first, so the default is the latest visit
        $branchTabs = collect(array_map(function ($bid) use ($branchDetails, $visitsByBranch) {
            $visit = $visitsByBranch[$bid];
            $branch = $branchDetails[$bid] ?? null;
            return (object) [
                'id' => $bid,
                'name' => $branch['name'] ?? 'Branch #' . $bid,
                'address' => $branch['address'] ?? null,
                'count' => $visit->count,
                'last_visit' => $visit->last_visit,
            ];
        }, array_keys($branchIds)))->sortByDesc('last_visit')->values();

        // Requested branch, falling back to the branch they visited last
        $requested = (string) $request->query('branch', '');
        $selected = 'all';
        if ($requested !== 'all') {
            foreach ($branchTabs as $tab) {
                if ((string) $tab->id === $requested) {
                    $selected = $requested;
                    break;
                }
            }
            if ($selected === 'all' && $branchTabs) {
                $selected = (string) $branchTabs[0]->id;
            }
        }

        $transactions = collect($salesRows)->filter(function ($s) use ($selected) {
            return $selected === 'all' || (string) ($s['branch_id'] ?? '') === $selected;
        })->map(function ($s) use ($branchDetails) {
            return (object) [
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

        // Line items for the transactions in view (PHP-side joins; PostgREST expansions return 0 rows)
        $saleIds = $transactions->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $itemsBySale = [];
        $productIds = [];
        if ($saleIds) {
            $saleItems = $this->supabase->query('sale_items', [
                'select' => 'sale_id,product_id,quantity,unit_price,total',
                'sale_id' => 'in.(' . implode(',', $saleIds) . ')',
            ]);
            foreach ($saleItems as $it) {
                $itemsBySale[(int) $it['sale_id']][] = $it;
                if (!empty($it['product_id'])) {
                    $productIds[(int) $it['product_id']] = true;
                }
            }
        }

        $productNames = [];
        if ($productIds) {
            $list = $this->supabase->query('products', [
                'select' => 'id,name,brand',
                'id' => 'in.(' . implode(',', array_keys($productIds)) . ')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }

        $itemSummary = [];
        foreach ($transactions as $txn) {
            $txn->items = collect($itemsBySale[(int) $txn->id] ?? [])->map(function ($it) use ($productNames) {
                $p = $productNames[(int) ($it['product_id'] ?? 0)] ?? null;
                return (object) [
                    'name' => $p['name'] ?? ('Product #' . ($it['product_id'] ?? '?')),
                    'brand' => $p['brand'] ?? null,
                    'quantity' => (int) ($it['quantity'] ?? 0),
                    'unit_price' => (float) ($it['unit_price'] ?? 0),
                    'total' => (float) ($it['total'] ?? 0),
                ];
            });

            foreach ($txn->items as $it) {
                $key = $it->name;
                if (!isset($itemSummary[$key])) {
                    $itemSummary[$key] = (object) [
                        'name' => $it->name,
                        'brand' => $it->brand,
                        'times' => 0,
                        'quantity' => 0,
                        'spent' => 0.0,
                        'last_bought' => null,
                    ];
                }
                $itemSummary[$key]->times += 1;
                $itemSummary[$key]->quantity += $it->quantity;
                $itemSummary[$key]->spent += $it->total;
                $itemSummary[$key]->last_bought = $txn->created_at;
            }
        }

        $itemSummary = collect(array_values($itemSummary))
            ->sortByDesc('quantity')
            ->values();

        $selectedName = 'All Branches';
        if ($selected !== 'all') {
            foreach ($branchTabs as $tab) {
                if ((string) $tab->id === $selected) $selectedName = $tab->name;
            }
        }

        return view('customer-care.customers.show', [
            'customer' => (object) $customer,
            'branchTabs' => $branchTabs,
            'selected' => $selected,
            'selectedName' => $selectedName,
            'transactions' => $transactions,
            'itemSummary' => $itemSummary,
            'totalSpent' => $transactions->sum('total'),
        ]);
    }

    /**
     * Search clients by name, phone or whatsapp — PostgREST cannot OR across columns,
     * so each column is queried and the hits are merged.
     */
    private function searchCustomers(string $q, int $limit = self::LIST_LIMIT)
    {
        $like = 'ilike.*' . urlencode($q) . '*';

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

    /**
     * Load customer records by id, keeping the order they were requested in.
     *
     * @param  array<int>  $ids
     */
    private function customersByIds(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return collect();

        $rows = $this->supabase->query('customers', [
            'select' => '*',
            'id' => 'in.(' . implode(',', $ids) . ')',
        ]);

        $byId = [];
        foreach ($rows as $row) $byId[(int) $row['id']] = (object) $row;

        return collect($ids)->map(fn ($id) => $byId[$id] ?? null)->filter()->values();
    }

    /**
     * Customer ids of the most recent visitors to a branch, newest first.
     *
     * Rows arrive newest first, so the first hit per customer is their latest
     * transaction there. $truncated is set when the branch has more sales than the
     * scan window, meaning the list is the most recent slice rather than everyone.
     */
    private function recentVisitors(int $branchId, int $limit, bool &$truncated = false): array
    {
        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id',
            'branch_id' => 'eq.' . $branchId,
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
            if (!$customerId || isset($seen[$customerId])) continue;
            $seen[$customerId] = true;
            $ids[] = $customerId;
            if (count($ids) >= $limit) break;
        }

        return $ids;
    }

    /**
     * Which of the given customers transacted at a branch — used to narrow a search.
     *
     * @return array<int, true> keyed by customer id
     */
    private function visitorsAmong(array $customerIds, int $branchId): array
    {
        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (!$customerIds) return [];

        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id',
            'customer_id' => 'in.(' . implode(',', $customerIds) . ')',
            'branch_id' => 'eq.' . $branchId,
            'limit' => count($customerIds) * 10,
        ]);

        $visitors = [];
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if ($customerId) $visitors[$customerId] = true;
        }

        return $visitors;
    }

    /**
     * Distinct customers per branch, from a single scan of the sales history.
     *
     * Returns an empty map when the history is larger than the scan window so the
     * caller can hide the tab counters rather than show an undercount.
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
            if (!$branchId || !$customerId) continue;
            $counts[$branchId][$customerId] = true;
        }

        return array_map(fn ($set) => count($set), $counts);
    }

    /**
     * [customer id => ['last_visit' => timestamp, 'branches' => [branch ids]]] for the
     * customers currently on screen, used for the badges in the list.
     */
    private function visitSummary(array $customerIds): array
    {
        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (!$customerIds) return [];

        $rows = $this->supabase->query('sales', [
            'select' => 'customer_id,branch_id,created_at',
            'customer_id' => 'in.(' . implode(',', $customerIds) . ')',
            'order' => 'created_at.desc',
            'limit' => self::VISIT_SCAN,
        ]);

        $summary = [];
        foreach ($customerIds as $id) {
            $summary[$id] = ['last_visit' => null, 'branches' => []];
        }
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if (!isset($summary[$customerId])) continue;
            if ($summary[$customerId]['last_visit'] === null) {
                $summary[$customerId]['last_visit'] = $row['created_at'] ?? null;
            }
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId) $summary[$customerId]['branches'][$branchId] = true;
        }

        return $summary;
    }
}