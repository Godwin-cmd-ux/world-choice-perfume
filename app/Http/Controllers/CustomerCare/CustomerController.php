<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    /**
     * Search client records by name, phone or whatsapp; otherwise list recent ones.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->q);

        if ($q !== '' && mb_strlen($q) >= 2) {
            $byName = $this->supabase->query('customers', [
                'select' => '*',
                'name' => 'ilike.*' . urlencode($q) . '*',
                'order' => 'name.asc',
                'limit' => 50,
            ]);
            $byPhone = $this->supabase->query('customers', [
                'select' => '*',
                'phone' => 'ilike.*' . urlencode($q) . '*',
                'order' => 'name.asc',
                'limit' => 50,
            ]);
            $byWhatsapp = $this->supabase->query('customers', [
                'select' => '*',
                'whatsapp' => 'ilike.*' . urlencode($q) . '*',
                'order' => 'name.asc',
                'limit' => 50,
            ]);

            $merged = [];
            foreach (array_merge($byName, $byPhone, $byWhatsapp) as $c) {
                $merged[$c['id']] = $c;
            }
            $customers = collect(array_slice(array_values($merged), 0, 50))->map(fn ($c) => (object) $c);
        } else {
            $customers = collect($this->supabase->query('customers', [
                'select' => '*',
                'order' => 'created_at.desc',
                'limit' => 50,
            ]))->map(fn ($c) => (object) $c);
        }

        return view('customer-care.customers.index', ['customers' => $customers, 'q' => $q]);
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
     * Client record with contacts and the last date they appeared at each branch.
     */
    public function show($customerId)
    {
        $customer = $this->supabase->find('customers', $customerId);
        if (!$customer) abort(404);

        // Walk-in visits (sales) — latest first, so the first row per branch is the last visit
        $visits = $this->supabase->query('sales', [
            'select' => 'branch_id, created_at',
            'customer_id' => "eq.{$customerId}",
            'order' => 'created_at.desc',
            'limit' => 500,
        ]);

        $branchIds = [];
        $lastVisitByBranch = [];
        foreach ($visits as $v) {
            $bid = $v['branch_id'] ?? null;
            if (!$bid) continue;
            $branchIds[$bid] = true;
            if (!isset($lastVisitByBranch[$bid])) {
                $lastVisitByBranch[$bid] = $v['created_at'] ?? null;
            }
        }

        $branches = [];
        if ($branchIds) {
            $list = $this->supabase->query('branches', [
                'select' => 'id,name,address',
                'id' => 'in.(' . implode(',', array_keys($branchIds)) . ')',
            ]);
            foreach ($list as $b) $branches[$b['id']] = $b;
        }

        $branchVisits = [];
        foreach ($lastVisitByBranch as $bid => $date) {
            $branchVisits[] = (object) [
                'branch' => isset($branches[$bid])
                    ? (object) ['id' => $branches[$bid]['id'], 'name' => $branches[$bid]['name'], 'address' => $branches[$bid]['address'] ?? null]
                    : (object) ['id' => $bid, 'name' => 'Branch #' . $bid, 'address' => null],
                'last_visit' => $date,
            ];
        }
        usort($branchVisits, fn ($a, $b) => strcmp((string) $b->last_visit, (string) $a->last_visit));

        // Purchases with line items (PHP-side joins; PostgREST expansions return 0 rows)
        $salesRows = $this->supabase->query('sales', [
            'select' => 'id,sale_number,total,branch_id,created_at,payment_status,payment_summary,sale_type',
            'customer_id' => "eq.{$customerId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        $saleIds = array_filter(array_map(fn ($s) => (int) ($s['id'] ?? 0), $salesRows));
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

        $recentSales = [];
        $itemSummary = [];
        foreach ($salesRows as $s) {
            $lineItems = collect($itemsBySale[(int) $s['id']] ?? [])->map(function ($it) use ($productNames) {
                $p = $productNames[(int) ($it['product_id'] ?? 0)] ?? null;
                return (object) [
                    'name' => $p['name'] ?? ('Product #' . ($it['product_id'] ?? '?')),
                    'brand' => $p['brand'] ?? null,
                    'quantity' => (int) ($it['quantity'] ?? 0),
                    'unit_price' => (float) ($it['unit_price'] ?? 0),
                    'total' => (float) ($it['total'] ?? 0),
                ];
            });

            $sale = (object) [
                'id' => $s['id'],
                'sale_number' => $s['sale_number'] ?? null,
                'branch_id' => (int) ($s['branch_id'] ?? 0),
                'branch_name' => $branches[$s['branch_id']]['name'] ?? null,
                'total' => $s['total'] ?? 0,
                'payment_status' => $s['payment_status'] ?? null,
                'payment_summary' => $s['payment_summary'] ?? null,
                'sale_type' => $s['sale_type'] ?? null,
                'created_at' => $s['created_at'] ?? null,
                'items' => $lineItems,
            ];
            $recentSales[] = $sale;

            foreach ($lineItems as $it) {
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
                if ($itemSummary[$key]->times === 1) {
                    $itemSummary[$key]->last_bought = $sale->created_at;
                }
            }
        }

        $itemSummary = collect(array_values($itemSummary))
            ->sortByDesc('quantity')
            ->values();

        return view('customer-care.customers.show', [
            'customer' => (object) $customer,
            'branchVisits' => $branchVisits,
            'recentSales' => collect($recentSales),
            'itemSummary' => $itemSummary,
        ]);
    }
}