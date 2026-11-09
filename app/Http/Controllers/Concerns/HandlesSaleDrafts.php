<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Draft sales, shared by every role that records a sale.
 *
 * The New Sale form is long: a customer, a cart, empty bottles and payment
 * rows. A draft parks all of it without touching stock, revenue or the sales
 * list, and the same form is redisplayed from ?draft=<id> with everything
 * restored. Completing the draft runs the role's ordinary store() path, which
 * deducts stock and records the payment, and then deletes the draft — so a
 * completed sale can never stay a draft.
 *
 * Every controller using this trait keeps its own $supabase and $varieties.
 */
trait HandlesSaleDrafts
{
    /** The role's own sales/create route name, e.g. cashier.sales.create. */
    abstract protected function saleCreateRouteName(): string;

    /**
     * Park the posted sale as a draft, then reopen the same form on it.
     */
    public function saveDraft(Request $request)
    {
        $draftId = $this->persistSaleDraft($request, (int) auth()->user()->branch_id);

        if ($draftId === null) {
            return back()->withInput()->with(
                'error',
                'Could not save the draft. If this is the first draft, run the sale_drafts migration.'
            );
        }

        return redirect()->route($this->saleCreateRouteName(), ['draft' => $draftId])
            ->with('success', 'Draft saved. Continue it any time from Sales — nothing you entered was lost.');
    }

    /**
     * Delete a parked sale without completing it, from the Drafts list.
     */
    public function deleteDraft($draftId)
    {
        $branchId = (int) auth()->user()->branch_id;
        $draftId = (int) $draftId;

        if ($draftId <= 0 || $this->findSaleDraft($draftId, $branchId) === null) {
            return back()->with('error', 'That draft sale could not be found. It may already have been completed.');
        }

        try {
            $this->supabase->delete('sale_drafts', ['id' => $draftId]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not delete the draft. Please try again.');
        }

        return back()->with('success', 'Draft sale deleted.');
    }

    /**
     * The draft a create page was asked to resume.
     *
     * Null means "open a fresh form". A draft id that no longer exists (or
     * belongs elsewhere) returns the redirect the caller should send instead.
     *
     * @return array|\Illuminate\Http\RedirectResponse|null
     */
    protected function resumeSaleDraft(Request $request, int $branchId, string $indexRoute)
    {
        // Usually the resumed form posts back to its own ?draft= URL, so the
        // referer carries the id. old() covers a stripped referer.
        $draftId = (int) ($request->query('draft') ?: old('draft_id', 0));

        if ($draftId <= 0) {
            return null;
        }

        $draft = $this->findSaleDraft($draftId, $branchId);

        if ($draft === null) {
            return redirect()->route($indexRoute)
                ->with('error', 'That draft sale could not be found. It may already have been completed.');
        }

        return $draft;
    }

    /**
     * A draft for this branch, with its payload decoded. Null when the draft is
     * gone (already completed) or belongs to another branch.
     */
    protected function findSaleDraft(int $draftId, int $branchId): ?array
    {
        try {
            $draft = $this->supabase->find('sale_drafts', $draftId);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (!$draft || (int) ($draft['branch_id'] ?? 0) !== $branchId) {
            return null;
        }

        $payload = $draft['payload'] ?? [];
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        $draft['payload'] = is_array($payload) ? $payload : [];

        return $draft;
    }

    /**
     * Remove the draft a completed sale came from. Called by each role's store()
     * once the real sale has been written.
     */
    protected function completeSaleDraft(Request $request, int $branchId): void
    {
        $draftId = (int) $request->input('draft_id', 0);

        if ($draftId <= 0) {
            return;
        }

        try {
            $draft = $this->supabase->find('sale_drafts', $draftId);

            // Only ever remove a draft from this branch. The sale itself is
            // already written; a cleanup failure must not fail the receipt.
            if ($draft && (int) ($draft['branch_id'] ?? 0) === $branchId) {
                $this->supabase->delete('sale_drafts', ['id' => $draftId]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Validate and write the draft. Returns its id, or null when the write
     * failed (the usual cause being the sale_drafts migration not being run).
     */
    protected function persistSaleDraft(Request $request, int $branchId): ?int
    {
        $validated = $request->validate([
            'draft_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'payment_mode' => 'nullable|in:single,multi',
            'payments' => 'nullable|array',
            'payments.*.method' => 'nullable|string|max:50',
            'payments.*.amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.custom_price' => 'nullable|numeric|min:0',
            'items.*.discount_price' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|integer',
            'items.*.variant' => 'nullable|string|max:32',
            'empty_bottles' => 'nullable|array',
            'empty_bottles.*.volume' => 'nullable|integer',
            'empty_bottles.*.quantity' => 'nullable|integer|min:1',
            'empty_bottles.*.price' => 'nullable|numeric|min:0',
            'empty_bottles.*.variant' => 'nullable|string|max:32',
            'sale_type' => 'nullable|in:retail,wholesale',
        ]);

        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();
        $saleType = $validated['sale_type'] ?? 'retail';

        $items = collect($validated['items'] ?? [])
            ->filter(fn ($i) => !empty($i['product_id'] ?? null))
            ->map(fn ($i) => [
                'product_id' => (int) $i['product_id'],
                'quantity' => (int) ($i['quantity'] ?? 1),
                'custom_price' => $i['custom_price'] ?? null,
                'discount_price' => $i['discount_price'] ?? null,
                'volume' => !empty($i['volume']) ? (int) $i['volume'] : null,
                'variant' => $i['variant'] ?? null,
            ])
            ->values();

        // Empty bottles are a wholesale line only, exactly as on the sale form.
        $bottles = $saleType === 'retail' ? collect() : collect($validated['empty_bottles'] ?? [])
            ->filter(fn ($b) => !empty($b['volume'] ?? null))
            ->map(fn ($b) => [
                'volume' => (int) $b['volume'],
                'variant' => $b['variant'] ?? null,
                'quantity' => (int) ($b['quantity'] ?? 1),
                'price' => $b['price'] ?? null,
            ])
            ->values();

        $payments = collect($validated['payments'] ?? [])
            ->filter(fn ($p) => !empty($p['method'] ?? null))
            ->map(fn ($p) => [
                'method' => (string) $p['method'],
                'amount' => $p['amount'] ?? null,
            ])
            ->values();

        // The customer's name is stored alongside the id so the draft list and
        // the resumed form have something to show without another lookup.
        $customerId = !empty($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $customerName = trim((string) ($validated['customer_name'] ?? ''));
        $customerPhone = trim((string) ($validated['customer_phone'] ?? ''));

        if ($customerId) {
            $customer = $this->supabase->find('customers', $customerId, 'id,name,phone,whatsapp');
            if ($customer) {
                $customerName = (string) ($customer['name'] ?? $customerName);
                $customerPhone = (string) ($customer['phone'] ?? $customerPhone);
            }
        }

        $payload = [
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'sale_type' => $saleType,
            'payment_mode' => $validated['payment_mode'] ?? 'single',
            'payments' => $payments->all(),
            'items' => $items->all(),
            'empty_bottles' => $bottles->all(),
        ];

        [$total, $itemCount] = $this->saleDraftTotals($branchId, $saleType, $items->all(), $bottles->all());

        $data = [
            'branch_id' => $branchId,
            'created_by' => $supabaseUserId,
            'customer_label' => $customerName !== '' ? $customerName : ($customerPhone !== '' ? $customerPhone : 'Walk-in customer'),
            'total' => $total,
            'item_count' => $itemCount,
            'payload' => $payload,
            'updated_at' => now()->toIso8601String(),
        ];

        $draftId = (int) ($validated['draft_id'] ?? 0);

        try {
            if ($draftId > 0 && $this->findSaleDraft($draftId, $branchId)) {
                $this->supabase->update('sale_drafts', $data, ['id' => $draftId]);

                return $draftId;
            }

            $data['created_at'] = now()->toIso8601String();
            $draft = $this->supabase->insert('sale_drafts', $data);

            return $draft ? (int) $draft['id'] : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * The indicative total and line count shown on the draft list. Prices come
     * from the branch's stock and, for a bottled line, its variety price; stock
     * itself is not reserved or deducted until the sale is completed.
     *
     * @return array{0: float, 1: int}
     */
    private function saleDraftTotals(int $branchId, string $saleType, array $items, array $bottles): array
    {
        $total = 0.0;
        $count = 0;

        if ($items !== []) {
            $productIds = array_values(array_unique(array_map(fn ($i) => (int) $i['product_id'], $items)));
            $prices = [];

            foreach ($this->supabase->query('branch_stock', [
                'select' => 'product_id,selling_price',
                'branch_id' => "eq.{$branchId}",
                'product_id' => 'in.(' . implode(',', $productIds) . ')',
            ]) as $row) {
                $prices[(int) $row['product_id']] = (float) ($row['selling_price'] ?? 0);
            }

            foreach ($items as $item) {
                // Mirrors the pricing rules of the sale forms: wholesale uses
                // the custom price, retail the manual discount.
                $unit = 0.0;

                if ($saleType === 'wholesale' && $item['custom_price'] !== null && $item['custom_price'] !== '') {
                    $unit = (float) $item['custom_price'];
                } elseif ($saleType === 'retail' && ($item['discount_price'] ?? null) !== null && ($item['discount_price'] ?? '') !== '') {
                    $unit = (float) $item['discount_price'];
                } elseif ($item['custom_price'] !== null && $item['custom_price'] !== '') {
                    $unit = (float) $item['custom_price'];
                }

                if ($unit <= 0 && !empty($item['volume']) && !empty($item['variant'])) {
                    $unit = $this->varieties->priceFor(
                        $branchId,
                        (int) $item['product_id'],
                        (int) $item['volume'],
                        (string) $item['variant']
                    );
                }

                if ($unit <= 0) {
                    $unit = $prices[(int) $item['product_id']] ?? 0.0;
                }

                $total += $unit * (int) $item['quantity'];
                $count++;
            }
        }

        foreach ($bottles as $bottle) {
            $total += (float) ($bottle['price'] ?? 0) * (int) ($bottle['quantity'] ?? 0);
            $count++;
        }

        return [$total, $count];
    }
}
