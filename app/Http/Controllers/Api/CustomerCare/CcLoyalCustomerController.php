<?php

namespace App\Http\Controllers\Api\CustomerCare;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * JSON twin of the Loyal Customers tab of the Clients screen
 * (CustomerCare\LoyalCustomerController): the loyal list, the picker's
 * multi-assign, and revoking the flag.
 *
 * Any customer-care member may assign — the flag is a note about the client,
 * not a branch-scoped record — and each membership records who assigned it
 * (performingUserId: the Supabase users row the session middleware resolved).
 *
 * The tab lives in its own table (loyal_customers): UNIQUE(customer_id)
 * makes a second assignment a no-op, the row carries who and when, and the
 * customers table — read by checkout, sales and orders — stays untouched.
 */
class CcLoyalCustomerController extends CcBaseController
{
    /** Rows listed per call (the codebase does not paginate anywhere). */
    private const LIST_LIMIT = 200;

    public function index(Request $request): JsonResponse
    {
        $memberships = $this->supabase->query('loyal_customers', [
            'select' => '*',
            'order' => 'assigned_at.desc',
            'limit' => self::LIST_LIMIT,
        ]);

        $rows = $this->decorate($memberships);

        return response()->json([
            'loyal' => $rows,
            'total' => count($rows),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Assign one or more customers as loyal (the picker's "Add selected").
     *
     * Ids that are already loyal or unknown are skipped rather than failing
     * the batch: the picker shows several people at once and a stale row
     * must not block the rest. The response names both counts so the app
     * can say exactly what happened.
     */
    public function store(Request $request): JsonResponse
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
            $this->fail(['customer_ids' => 'Select at least one customer to add as loyal.']);
        }

        $existing = collect($this->supabase->query('loyal_customers', [
            'select' => 'customer_id',
            'customer_id' => 'in.(' . $ids->implode(',') . ')',
        ]))->pluck('customer_id')->map(fn ($id) => (int) $id)->all();

        $known = collect($this->supabase->query('customers', [
            'select' => 'id',
            'id' => 'in.(' . $ids->implode(',') . ')',
        ]))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $toInsert = $ids->filter(
            fn ($id) => ! in_array($id, $existing, true) && in_array($id, $known, true)
        )->values();

        $added = $toInsert->count();
        $skipped = $ids->count() - $added;

        if ($added > 0) {
            $stamp = now()->toIso8601String();

            $inserted = $this->supabase->insertMany('loyal_customers', $toInsert->map(fn ($id) => [
                'customer_id' => $id,
                'assigned_by' => $this->performingUserId($request),
                'assigned_at' => $stamp,
                'created_at' => $stamp,
            ])->all());

            if (! $inserted) {
                // insertMany answers null when PostgREST refuses the write —
                // by far the usual cause is the migration not having run yet,
                // so name the fix instead of the symptom.
                $this->fail(['error' => 'Could not add the loyal customers. If this is the first time, run the loyal_customers migration in Supabase.']);
            }
        }

        return response()->json([
            'message' => "Added {$added} " . Str::plural('customer', $added) . ' as loyal.'
                . ($skipped > 0 ? " {$skipped} skipped (already loyal or unknown)." : ''),
            'added' => $added,
            'skipped' => $skipped,
            'ids' => $toInsert->all(),
        ]);
    }

    /** Revoke the flag from one client (the row's Remove button). */
    public function destroy($customer): JsonResponse
    {
        // findOne() builds the eq. filter itself — pass the raw id.
        $membership = $this->supabase->findOne('loyal_customers', [
            'customer_id' => (int) $customer,
        ]);

        if (! $membership) {
            return response()->json(['message' => 'This customer is not marked as loyal.'], 404);
        }

        $deleted = $this->supabase->delete('loyal_customers', [
            'customer_id' => 'eq.' . (int) $customer,
        ]);

        if (! $deleted) {
            return response()->json(['message' => 'Could not remove this loyal customer. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Removed from loyal customers.',
            'customer_id' => (int) $customer,
        ]);
    }

    /**
     * Memberships joined to their customer record and the assigner's name —
     * PHP-side joins, as everywhere in this project (PostgREST expansions
     * return 0 rows). Rows whose customer no longer exists are dropped.
     *
     * @param  array<int, array>  $memberships
     * @return array<int, array>
     */
    private function decorate(array $memberships): array
    {
        if ($memberships === []) {
            return [];
        }

        $customerIds = array_values(array_unique(array_map(
            fn ($m) => (int) ($m['customer_id'] ?? 0),
            $memberships
        )));
        $customers = [];
        foreach ($this->supabase->query('customers', [
            'select' => '*',
            'id' => 'in.(' . implode(',', $customerIds) . ')',
        ]) as $row) {
            $customers[(int) $row['id']] = $row;
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
            ]) as $row) {
                $assigners[(int) $row['id']] = $row['name'] ?? null;
            }
        }

        $rows = [];
        foreach ($memberships as $m) {
            $customerId = (int) ($m['customer_id'] ?? 0);
            if (! isset($customers[$customerId])) {
                continue;
            }

            $rows[] = [
                'id' => $m['id'] ?? null,
                'customer_id' => $customerId,
                'assigned_at' => $m['assigned_at'] ?? null,
                'assigned_by' => $m['assigned_by'] ?? null,
                'assigned_by_name' => $assigners[(int) ($m['assigned_by'] ?? 0)] ?? null,
                'customer' => $customers[$customerId],
            ];
        }

        return $rows;
    }
}
