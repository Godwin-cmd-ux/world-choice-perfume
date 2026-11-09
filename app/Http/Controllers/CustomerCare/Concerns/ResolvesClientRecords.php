<?php

namespace App\Http\Controllers\CustomerCare\Concerns;

/**
 * Client-record reads shared by the Clients tab and the Loyal tab of the
 * customer care client records screen (CustomerController and
 * LoyalCustomerController), so both tabs search and join the same way.
 *
 * The methods read $this->supabase — every controller using this trait keeps
 * its own SupabaseService instance.
 */
trait ResolvesClientRecords
{
    /**
     * Search clients by name, phone or whatsapp — PostgREST cannot OR across columns,
     * so each column is queried and the hits are merged.
     */
    private function searchCustomers(string $q, int $limit = 50)
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
     * Distinct customers per branch, from a single scan of the sales history.
     *
     * Clients assigned to a branch on the New Customer form (customers.branch_id)
     * count for that branch too, even before their first sale.
     *
     * Returns an empty map when the history is larger than the scan window so the
     * caller can hide the tab counters rather than show an undercount.
     */
    private function customerCountsByBranch(): array
    {
        $rows = $this->supabase->query('sales', [
            'select' => 'branch_id,customer_id',
            'limit' => 2000,
        ]);

        if (count($rows) >= 2000) {
            return [];
        }

        $counts = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            $customerId = (int) ($row['customer_id'] ?? 0);
            if (!$branchId || !$customerId) continue;
            $counts[$branchId][$customerId] = true;
        }

        if ($this->supabase->tableHasColumn('customers', 'branch_id')) {
            foreach ($this->supabase->query('customers', [
                'select' => 'id,branch_id',
                'branch_id' => 'not.is.null',
                'limit' => 2000,
            ]) as $row) {
                $branchId = (int) ($row['branch_id'] ?? 0);
                $customerId = (int) ($row['id'] ?? 0);
                if (!$branchId || !$customerId) continue;
                $counts[$branchId][$customerId] = true;
            }
        }

        return array_map(fn ($set) => count($set), $counts);
    }

    /**
     * Ids of the clients assigned to a branch on the New Customer form.
     *
     * Empty until the customers.branch_id migration has run, so the branch tabs
     * keep working (from sales alone) on an installation that has not migrated.
     *
     * @return array<int, int>
     */
    private function assignedCustomerIds(int $branchId, int $limit = 200): array
    {
        if (!$this->supabase->tableHasColumn('customers', 'branch_id')) {
            return [];
        }

        $rows = $this->supabase->query('customers', [
            'select' => 'id',
            'branch_id' => 'eq.' . $branchId,
            'order' => 'created_at.desc',
            'limit' => $limit,
        ]);

        return array_values(array_filter(array_map(fn ($row) => (int) ($row['id'] ?? 0), $rows)));
    }

    /**
     * Which of the given clients are flagged as loyal — used for the crown next
     * to their names.
     *
     * @param  array<int, mixed>  $customerIds
     * @return array<int, int>
     */
    private function loyalIdsFor(array $customerIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $customerIds))));

        if ($ids === []) {
            return [];
        }

        // A missing table (migration not run) answers [] — no crowns, no error.
        $rows = $this->supabase->query('loyal_customers', [
            'select' => 'customer_id',
            'customer_id' => 'in.(' . implode(',', $ids) . ')',
            'limit' => count($ids),
        ]);

        return array_values(array_unique(array_filter(
            array_map(fn ($row) => (int) ($row['customer_id'] ?? 0), $rows)
        )));
    }

    /**
     * The loyal memberships, newest first.
     *
     * A missing table (the migration has not been run yet) reads as an empty
     * list — PostgREST answers 404, query() logs it and returns [] — so the
     * tab still renders instead of taking the screen down.
     *
     * @return array<int, array>
     */
    private function loyalMemberships(): array
    {
        return $this->supabase->query('loyal_customers', [
            'select' => '*',
            'order' => 'assigned_at.desc',
            'limit' => 200,
        ]);
    }

    /** How many clients are flagged as loyal (0 until the migration has run). */
    private function loyalCount(): int
    {
        return $this->supabase->count('loyal_customers', []);
    }
}
