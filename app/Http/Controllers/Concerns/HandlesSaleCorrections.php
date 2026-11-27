<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditService;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;

/**
 * Same-day correction of a completed sale.
 *
 * A sale is a completed transaction once it is paid. Once it is paid, the only
 * window in which its details may still be put right is the business day it
 * was recorded on; after that the receipt is part of the closed day's books.
 *
 * Deleting a sale also undoes the stock it consumed, so the branch is not left
 * short of goods that are no longer sold. Every edit and delete is recorded
 * in the audit trail, the super-admin notification feed and the branch
 * admins' own notification inbox.
 */
trait HandlesSaleCorrections
{
    /** Branch id / seller id limits that mirror the sales list on screen. */
    abstract protected function saleCorrectionScope(): array;

    /** Index, edit, update and destroy route names plus the layout to extend. */
    abstract protected function saleCorrectionRoutes(): array;

    public function editSale($saleId)
    {
        $sale = $this->loadSaleForCorrection($saleId);
        if (!$sale) {
            abort(404);
        }

        $this->assertSaleIsCorrectable($sale);

        $supabase = new SupabaseService();
        $customers = collect($supabase->query('customers', [
            'select' => 'id,name,phone',
            'order' => 'name.asc',
            'limit' => 200,
        ]))->map(fn ($c) => (object) $c);

        $routes = $this->saleCorrectionRoutes();

        return view('partials.sale-edit', [
            'sale' => (object) $sale,
            'customers' => $customers,
            'routes' => $routes,
            'layout' => $routes['layout'] ?? 'layouts.app',
        ]);
    }

    public function updateSale(Request $request, $saleId)
    {
        $sale = $this->loadSaleForCorrection($saleId);
        if (!$sale) {
            abort(404);
        }

        $this->assertSaleIsCorrectable($sale);

        $validated = $request->validate([
            'customer_id' => 'nullable|integer',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_payment',
            'payment_summary' => 'nullable|string|max:500',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $supabase = new SupabaseService();
        $customerId = !empty($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        if ($customerId !== null && !$supabase->find('customers', $customerId)) {
            return back()->withErrors(['customer_id' => 'The selected customer no longer exists.'])->withInput();
        }

        $subtotal = round((float) $validated['subtotal'], 2);
        $discount = round((float) ($validated['discount'] ?? 0), 2);
        if ($discount > $subtotal) {
            return back()->withErrors(['discount' => 'Discount cannot be greater than the sale amount.'])->withInput();
        }

        $customerId = $customerId ?: ($sale['customer_id'] ?? null);
        $paymentSummary = trim((string) ($validated['payment_summary'] ?? ''));
        if ($paymentSummary === '') {
            $paymentSummary = str_replace('_', ' ', ucfirst($validated['payment_method']))
                . ' ' . number_format($subtotal - $discount);
        }

        $changedKeys = ['customer_id', 'payment_method', 'payment_summary', 'subtotal', 'discount', 'total', 'notes'];
        $oldValues = [];
        foreach ($changedKeys as $key) {
            $oldValues[$key] = $sale[$key] ?? null;
        }

        $newValues = [
            'customer_id' => $customerId,
            'payment_method' => $validated['payment_method'],
            'payment_summary' => $paymentSummary,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'notes' => $validated['notes'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];

        $supabase->update('sales', $newValues, ['id' => $sale['id']]);

        $auditValues = array_intersect_key($newValues, array_flip($changedKeys));
        $this->recordCorrection(
            $supabase,
            $sale,
            'sale_edited',
            'Sale edited',
            "Sale {$sale['sale_number']} was edited by " . (auth()->user()->name ?? 'a staff member') . '.',
            $oldValues,
            $auditValues
        );

        $routes = $this->saleCorrectionRoutes();

        return redirect()->route($routes['index'])
            ->with('success', "Sale {$sale['sale_number']} updated. Branch admin and super admin have been notified.");
    }

    public function destroySale($saleId)
    {
        $sale = $this->loadSaleForCorrection($saleId);
        if (!$sale) {
            abort(404);
        }

        $this->assertSaleIsCorrectable($sale);

        $supabase = new SupabaseService();
        $this->restoreSaleStock($supabase, $sale);

        $supabase->delete('sale_items', ['sale_id' => $sale['id']]);
        $supabase->delete('sales', ['id' => $sale['id']]);

        $this->recordCorrection(
            $supabase,
            $sale,
            'sale_deleted',
            'Sale deleted',
            "Sale {$sale['sale_number']} was deleted by " . (auth()->user()->name ?? 'a staff member') . '.',
            $sale,
            ['deleted' => true]
        );

        $routes = $this->saleCorrectionRoutes();

        return redirect()->route($routes['index'])
            ->with('success', "Sale {$sale['sale_number']} deleted. Stock was restored and the admins were notified.");
    }

    /**
     * Whether the list should offer Edit / Delete for this row. Uses the same
     * rules as the routes, so the button never promises an action the backend
     * would refuse.
     */
    public function saleCanBeCorrected(array|object $sale): bool
    {
        $sale = is_object($sale) ? (array) $sale : $sale;
        $scope = $this->saleCorrectionScope();

        if (!empty($scope['read_only'])) {
            return false;
        }
        if (!empty($scope['branch_id']) && (string) ($sale['branch_id'] ?? '') !== (string) $scope['branch_id']) {
            return false;
        }
        if (!empty($scope['cashier_id']) && (string) ($sale['cashier_id'] ?? '') !== (string) $scope['cashier_id']) {
            return false;
        }

        return $this->isCompletedSale($sale) && BusinessDay::withinDay($sale['created_at'] ?? null);
    }

    private function isCompletedSale(array $sale): bool
    {
        return strtolower((string) ($sale['payment_status'] ?? '')) === 'paid';
    }

    private function loadSaleForCorrection($saleId): ?array
    {
        $sale = (new SupabaseService())->find(
            'sales',
            (int) $saleId,
            '*, customer:customers(id,name,phone), items:sale_items(*, product:products(id,name))'
        );

        return $sale ?: null;
    }

    private function assertSaleIsCorrectable(array $sale): void
    {
        $scope = $this->saleCorrectionScope();

        if (!empty($scope['read_only'])) {
            abort(403, 'Sales cannot be changed while monitoring another branch.');
        }
        if (!empty($scope['branch_id']) && (string) ($sale['branch_id'] ?? '') !== (string) $scope['branch_id']) {
            abort(403, 'This sale belongs to another branch.');
        }
        if (!empty($scope['cashier_id']) && (string) ($sale['cashier_id'] ?? '') !== (string) $scope['cashier_id']) {
            abort(403, 'Only the person who recorded a sale may correct it.');
        }
        if (!$this->isCompletedSale($sale)) {
            abort(403, 'Only completed sales can be edited or deleted.');
        }
        if (!BusinessDay::withinDay($sale['created_at'] ?? null)) {
            abort(403, 'A sale can only be edited or deleted on the day it was recorded.');
        }
    }

    private function restoreSaleStock(SupabaseService $supabase, array $sale): void
    {
        $branchId = (int) ($sale['branch_id'] ?? 0);
        if ($branchId <= 0) {
            return;
        }

        $items = $sale['items'] ?? $supabase->query('sale_items', ['sale_id' => 'eq.' . $sale['id']]);
        $varieties = new ProductVarietyStockService($supabase);
        $bottles = new BottleStockService($supabase);
        $actorId = auth()->user()->supabase_id ?? auth()->id();
        $now = now()->toIso8601String();

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $stock = $supabase->queryFresh('branch_stock', [
                'branch_id' => "eq.{$branchId}",
                'product_id' => "eq.{$productId}",
                'limit' => 1,
            ]);
            $stockRow = $stock[0] ?? null;
            if ($stockRow) {
                $supabase->update('branch_stock', [
                    'quantity' => (int) ($stockRow['quantity'] ?? 0) + $quantity,
                    'updated_at' => $now,
                ], ['id' => $stockRow['id']]);
            }

            $volume = (int) ($item['volume'] ?? 0);
            $variant = (string) ($item['variant'] ?? '');
            if ($volume > 0 && $variant !== '') {
                $varieties->adjust($branchId, $productId, $volume, $variant, $quantity);
            }

            // A reversal movement keeps the stock history explainable. The
            // enum has no "sale_deleted", so the valid "adjustment" type is
            // used with a note naming the sale.
            $supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'unit_price' => $item['unit_price'] ?? 0,
                'reference_type' => 'sale',
                'reference_id' => $sale['id'],
                'performed_by' => $actorId,
                'notes' => "Sale {$sale['sale_number']} deleted — stock restored",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Empty bottles are sold from bottle_stock, not branch_stock. Their
            // original deduction carries the sale number in its reason, which
            // is enough to find the bucket to return them to.
            $productName = (string) ($item['product']['name'] ?? '');
            if ($volume <= 0 && str_starts_with($productName, 'Empty Bottle ')) {
                $bottleVolume = (int) filter_var($productName, FILTER_SANITIZE_NUMBER_INT);
                $moves = $supabase->queryFresh('bottle_stock_movements', [
                    'branch_id' => "eq.{$branchId}",
                    'volume' => "eq.{$bottleVolume}ml",
                ]);
                foreach ($moves as $move) {
                    if (!str_contains((string) ($move['reason'] ?? ''), (string) $sale['sale_number'])) {
                        continue;
                    }
                    $bottles->restock(
                        $branchId,
                        $bottleVolume,
                        $quantity,
                        "Sale {$sale['sale_number']} deleted — bottles restored",
                        $actorId,
                        (string) ($move['variant'] ?? '')
                    );
                    break;
                }
            }
        }
    }

    private function recordCorrection(
        SupabaseService $supabase,
        array $sale,
        string $type,
        string $title,
        string $message,
        array $oldValues,
        array $newValues
    ): void {
        $actor = auth()->user();
        $actorName = $actor->name ?? 'a staff member';
        $data = [
            'sale_id' => (int) $sale['id'],
            'sale_number' => $sale['sale_number'] ?? '',
            'branch_id' => (int) ($sale['branch_id'] ?? 0),
            'action' => $type,
            'actor' => $actorName,
        ];

        // audit_logs + admin_notifications (super admin feed).
        (new AuditService($supabase))->recordCriticalAction(
            $type,
            $type,
            $title,
            $message,
            $data,
            'sale',
            (string) $sale['id'],
            $oldValues,
            $newValues
        );

        // The branch admins' inbox is the per-user notifications table.
        try {
            $admins = $supabase->query('users', [
                'select' => 'id,name',
                'role' => 'eq.branch_admin',
                'branch_id' => 'eq.' . (int) ($sale['branch_id'] ?? 0),
                'limit' => 50,
            ]);
            $now = now()->toIso8601String();
            foreach ($admins as $admin) {
                $supabase->insert('notifications', [
                    'user_id' => $admin['id'],
                    'type' => $type,
                    'data' => json_encode($data + ['title' => $title, 'message' => $message]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Sale correction: branch admin notification failed: ' . $e->getMessage());
        }
    }
}
