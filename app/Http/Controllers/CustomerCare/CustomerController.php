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

        // Recent purchases
        $recentSales = collect($this->supabase->query('sales', [
            'select' => 'id,sale_number,total,branch_id,created_at',
            'customer_id' => "eq.{$customerId}",
            'order' => 'created_at.desc',
            'limit' => 10,
        ]))->map(function ($s) use ($branches) {
            $s['branch_name'] = $branches[$s['branch_id']]['name'] ?? null;
            return (object) $s;
        });

        return view('customer-care.customers.show', [
            'customer' => (object) $customer,
            'branchVisits' => $branchVisits,
            'recentSales' => $recentSales,
        ]);
    }
}