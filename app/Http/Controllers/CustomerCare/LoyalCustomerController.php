<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerCare\Concerns\ResolvesClientRecords;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

/**
 * The Loyal tab of the client records screen: every client customer care has
 * flagged as loyal, plus the picker that assigns and revokes the flag.
 *
 * Membership lives in its own table (loyal_customers) instead of a column on
 * customers: a row records who assigned it and when, the UNIQUE(customer_id)
 * constraint makes a second assignment a no-op, and the customers table —
 * read by checkout, sales and orders — stays untouched.
 */
class LoyalCustomerController extends Controller
{
    use ResolvesClientRecords;

    // Constructor-injected (like DashboardController), so tests can swap the
    // service for a fake instead of reaching the network.
    public function __construct(private SupabaseService $supabase)
    {
    }

    /**
     * The tab itself: the loyal list, the branch tab strip shared with the
     * Clients tab, and — when ?create=1 — the picker that searches the
     * customers who are not loyal yet and lets several be assigned at once.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $cq = trim((string) $request->query('cq'));
        $create = $request->query('create') === '1';

        // Same strip as the Clients tab, so the two read as one screen.
        // Branch links return to the Clients tab; the counters stay there.
        $branches = $this->supabase->query('branches', [
            'select' => 'id,name,address,is_active',
            'order' => 'name.asc',
        ]);
        // One bounded scan feeds every tab's counter; dropped wholesale when the
        // history no longer fits, exactly as the Clients tab decides it.
        $counts = $this->customerCountsByBranch();
        $tabs = collect($branches)
            ->filter(fn ($b) => ($b['is_active'] ?? true) || ($counts[(int) $b['id']] ?? 0) > 0)
            ->map(fn ($b) => (object) [
                'id' => (int) $b['id'],
                'name' => $b['name'],
                'count' => $counts[(int) $b['id']] ?? 0,
            ])
            ->values();
        $totalCustomers = $this->supabase->count('customers', []);

        // Loyal rows, joined to their customer record (PHP-side join; PostgREST
        // expansions return 0 rows in this project).
        $memberships = $this->loyalMemberships();
        $loyal = $this->decorateLoyal($memberships);

        // The main search filters the loyal list itself — it is what the page
        // is showing. Filtering in memory is fine: the list is capped at 200.
        $filtered = $q !== '' ? $loyal->filter(function ($row) use ($q) {
            $customer = $row->customer;
            $haystack = mb_strtolower(implode(' ', array_filter([
                $customer->name ?? '', $customer->phone ?? '', $customer->whatsapp ?? '',
            ])));

            return str_contains($haystack, mb_strtolower($q));
        })->values() : $loyal;

        // The picker: customers who are not loyal yet, searched by name/phone
        // or simply listed newest first so a regular can be spotted at once.
        $candidates = collect();
        if ($create) {
            $loyalIds = $loyal->pluck('customer.id')->map(fn ($id) => (int) $id)->all();
            $candidates = ($cq !== '' && mb_strlen($cq) >= 2
                ? $this->searchCustomers($cq, 20)
                : collect($this->supabase->query('customers', [
                    'select' => '*',
                    'order' => 'created_at.desc',
                    'limit' => 20,
                ]))->map(fn ($c) => (object) $c))
                ->filter(fn ($c) => !in_array((int) $c->id, $loyalIds, true))
                ->values();
        }

        return view('customer-care.customers.loyal', [
            'rows' => $filtered,
            'q' => $q,
            'cq' => $cq,
            'create' => $create,
            'candidates' => $candidates,
            'tabs' => $tabs,
            'counts' => $counts,
            'totalCustomers' => $totalCustomers,
            'loyalCount' => $this->loyalCount(),
            'view' => 'loyal',
            // The strip is branch-aware: the Loyal tab itself is not, so it
            // always reads as "All Customers" behind the active Loyal link.
            'tab' => 0,
        ]);
    }

    /**
     * Assign one or more customers as loyal from the picker's checkboxes.
     *
     * Ids that are already loyal or unknown are skipped rather than failing
     * the whole batch: the picker shows several people at once and a stale
     * row must not block the rest.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer',
        ]);

        $ids = collect($validated['customer_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('customer-care.customers.loyal', ['create' => 1])
                ->with('error', 'Select at least one customer to add as loyal.');
        }

        $existing = collect($this->supabase->query('loyal_customers', [
            'select' => 'customer_id',
            'customer_id' => 'in.(' . $ids->implode(',') . ')',
        ]))->pluck('customer_id')->map(fn ($id) => (int) $id)->all();

        $knownIds = $this->customersByIds($ids->all())->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Only ids that exist in customers and are not loyal yet.
        $toInsert = $ids->filter(fn ($id) => !in_array($id, $existing, true) && in_array($id, $knownIds, true))->values();

        if ($toInsert->isEmpty()) {
            return redirect()
                ->route('customer-care.customers.loyal', ['create' => 1])
                ->with('success', 'Everyone selected is already marked as loyal.');
        }

        $staff = $request->user();
        $assignedBy = $staff->supabase_id ?? $staff->id ?? null;
        $stamp = now()->toIso8601String();

        $inserted = $this->supabase->insertMany('loyal_customers', $toInsert->map(fn ($id) => [
            'customer_id' => $id,
            'assigned_by' => $assignedBy,
            'assigned_at' => $stamp,
            'created_at' => $stamp,
        ])->all());

        if (! $inserted) {
            // The usual cause is the missing table: insertMany fails against a
            // 404 and returns null, so name the fix rather than the symptom.
            return redirect()
                ->route('customer-care.customers.loyal')
                ->with('error', 'Could not add the loyal customers. If this is the first time, run the loyal_customers migration in Supabase.');
        }

        $added = count($toInsert);
        $skipped = $ids->count() - $added;

        return redirect()
            ->route('customer-care.customers.loyal')
            ->with('success', "Added {$added} " . \Illuminate\Support\Str::plural('customer', $added) . ' as loyal.'
                . ($skipped > 0 ? " {$skipped} skipped (already loyal or unknown)." : ''));
    }

    /** Revoke the flag from one client. */
    public function remove($customer)
    {
        $deleted = $this->supabase->delete('loyal_customers', [
            'customer_id' => 'eq.' . (int) $customer,
        ]);

        if (! $deleted) {
            return redirect()
                ->route('customer-care.customers.loyal')
                ->with('error', 'Could not remove this loyal customer. Please try again.');
        }

        return redirect()
            ->route('customer-care.customers.loyal')
            ->with('success', 'Removed from loyal customers.');
    }

    /**
     * Memberships decorated for the view: each row carries its customer record
     * and the name of the member who assigned it (best effort — an unreadable
     * users table simply hides who it was, never the row itself).
     *
     * @param  array<int, array>  $memberships
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function decorateLoyal(array $memberships)
    {
        if ($memberships === []) {
            return collect();
        }

        $customerIds = array_map(fn ($m) => (int) ($m['customer_id'] ?? 0), $memberships);
        $customers = [];
        foreach ($this->customersByIds($customerIds) as $customer) {
            $customers[(int) $customer->id] = $customer;
        }

        $assignerIds = array_values(array_unique(array_filter(array_map(
            fn ($m) => (int) ($m['assigned_by'] ?? 0),
            $memberships
        ))));
        $assigners = [];
        if ($assignerIds !== []) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $assignerIds) . ')',
            ]) as $user) {
                $assigners[(int) $user['id']] = $user['name'] ?? null;
            }
        }

        return collect($memberships)->map(function ($m) use ($customers, $assigners) {
            $customerId = (int) ($m['customer_id'] ?? 0);

            return (object) [
                'membership_id' => $m['id'] ?? null,
                'customer_id' => $customerId,
                'customer' => $customers[$customerId] ?? null,
                'assigned_at' => $m['assigned_at'] ?? null,
                'assigned_by_name' => $assigners[(int) ($m['assigned_by'] ?? 0)] ?? null,
            ];
        })->filter(fn ($row) => $row->customer !== null)->values();
    }
}
