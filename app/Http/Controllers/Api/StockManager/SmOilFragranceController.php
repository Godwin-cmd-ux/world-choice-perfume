<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of the Oil Fragrance Stock screens, gated like the website's
 * `stock-manager.bottle-access` middleware: a products-based branch gets 403.
 *
 * Stock is tracked per name + volume (Reef 33 500ml and Reef 33 1000ml are
 * independent rows), exactly as on the website.
 */
class SmOilFragranceController extends SmBaseController
{
    public function index(Request $request)
    {
        $this->assertBottleAccess($request);

        $params = $this->branchParams($request, [
            'select' => '*',
            'order' => 'name.asc',
        ]);

        $oils = $this->supabase->query('oil_fragrance_stock', $params);

        if ($request->query('search')) {
            $search = strtolower((string) $request->query('search'));
            $oils = array_filter($oils, fn ($o) => str_contains(strtolower($o['name'] ?? ''), $search));
        }

        $oils = array_values($oils);
        $totalQuantity = array_sum(array_map(fn ($o) => $o['quantity'] ?? 0, $oils));

        return response()->json([
            'oils' => $oils,
            'totalQuantity' => $totalQuantity,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /**
     * Form data for stock-in / stock-out: the oil fragrance products, plus
     * the per-name and per-name+volume availability maps the stock-out form
     * validates against.
     */
    public function options(Request $request)
    {
        $this->assertBottleAccess($request);

        $branchId = $this->ownBranchId($request);

        $stockByProduct = [];
        $stockByProductVolume = [];
        $stocks = $this->supabase->query('oil_fragrance_stock', [
            'branch_id' => "eq.{$branchId}",
            'select' => 'name,volume,quantity',
            'order' => 'name.asc',
        ]);
        foreach ($stocks as $s) {
            $stockByProduct[$s['name']] = ($stockByProduct[$s['name']] ?? 0) + (int) ($s['quantity'] ?? 0);
            $stockByProductVolume[$s['name'].'_'.($s['volume'] ?? 0)] = (int) ($s['quantity'] ?? 0);
        }

        $oilProducts = collect($this->supabase->query('products', [
            'is_active' => 'eq.true',
            'category' => 'eq.Oil Fragrance',
            'select' => 'id,name,brand',
            'order' => 'name.asc',
            'limit' => 200,
        ]))->values()->all();

        return response()->json([
            'oilProducts' => $oilProducts,
            'stockByProduct' => $stockByProduct,
            'stockByProductVolume' => $stockByProductVolume,
        ]);
    }

    public function storeIn(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'bottle_volume' => 'required|integer|in:500,1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = $this->supabase->findOne('products', [
            'id' => $validated['product_id'],
        ]);

        if (! $product || ($product['category'] ?? '') !== 'Oil Fragrance') {
            $this->fail(['product_id' => 'Selected product is not available or is not an Oil Fragrance.']);
        }

        $name = $product['name'];
        $bottleVolume = (int) $validated['bottle_volume'];
        $volumeLabel = $bottleVolume === 500 ? '500ml' : '1000ml';

        // One row per name + volume: a 500ml stock-in never touches 1000ml.
        $existing = $this->supabase->findOne('oil_fragrance_stock', [
            'branch_id' => $branchId,
            'name' => $name,
            'volume' => $bottleVolume,
        ]);

        if ($existing) {
            $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
            $this->supabase->update('oil_fragrance_stock', [
                'quantity' => $newQty,
                'volume' => $bottleVolume,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);
        } else {
            $this->supabase->insert('oil_fragrance_stock', [
                'branch_id' => $branchId,
                'name' => $name,
                'volume' => $bottleVolume,
                'quantity' => $validated['quantity'],
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $this->supabase->insert('oil_fragrance_movements', [
            'branch_id' => $branchId,
            'name' => $name,
            'volume' => $bottleVolume,
            'type' => 'stock_in',
            'quantity' => $validated['quantity'],
            'reason' => ($validated['reason'] ?? 'Stock in')." [{$volumeLabel}]",
            'performed_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json(['message' => 'Oil fragrance stock added.']);
    }

    public function storeOut(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'bottle_volume' => 'required|integer|in:500,1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = $this->supabase->findOne('products', [
            'id' => $validated['product_id'],
        ]);

        if (! $product || ($product['category'] ?? '') !== 'Oil Fragrance') {
            $this->fail(['product_id' => 'Selected product is not available or is not an Oil Fragrance.']);
        }

        $name = $product['name'];
        $bottleVolume = (int) $validated['bottle_volume'];
        $volumeLabel = $bottleVolume === 500 ? '500ml' : '1000ml';

        $existing = $this->supabase->findOne('oil_fragrance_stock', [
            'branch_id' => $branchId,
            'name' => $name,
            'volume' => $bottleVolume,
        ]);

        $availableForVolume = (int) ($existing['quantity'] ?? 0);
        if (! $existing || $availableForVolume < $validated['quantity']) {
            $this->fail([
                'quantity' => "Insufficient {$volumeLabel} stock for {$name}. Available: {$availableForVolume} bottle(s).",
            ]);
        }

        $newQty = $availableForVolume - $validated['quantity'];
        $this->supabase->update('oil_fragrance_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $existing['id']]);

        $this->supabase->insert('oil_fragrance_movements', [
            'branch_id' => $branchId,
            'name' => $name,
            'volume' => $bottleVolume,
            'type' => 'stock_out',
            'quantity' => $validated['quantity'],
            'reason' => ($validated['reason'] ?? 'Used for production')." [{$volumeLabel}]",
            'performed_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json(['message' => 'Oil fragrance stock out recorded.']);
    }

    public function update(Request $request, int $id)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('oil_fragrance_stock', [
            'id' => $id,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Oil fragrance stock record not found.']);
        }

        $oldQty = $stock['quantity'] ?? 0;
        $newQty = $validated['quantity'];

        $this->supabase->update('oil_fragrance_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $id]);

        if ($newQty != $oldQty) {
            $type = $newQty > $oldQty ? 'stock_in' : 'stock_out';
            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $stock['name'],
                'volume' => $stock['volume'] ?? null,
                'type' => $type,
                'quantity' => abs($newQty - $oldQty),
                'reason' => 'Manual adjustment',
                'performed_by' => $this->performingUserId($request),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        return response()->json(['message' => 'Oil fragrance stock updated.']);
    }

    public function destroy(Request $request, int $id)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('oil_fragrance_stock', [
            'id' => $id,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Oil fragrance stock record not found.']);
        }

        $this->supabase->delete('oil_fragrance_stock', ['id' => $id]);

        (new AuditService)->recordCriticalAction(
            'stock_deleted',
            'oil_fragrance_stock_deleted',
            'Oil Fragrance Stock Deleted',
            "Oil fragrance stock record deleted (name {$stock['name']}, {$stock['quantity']} units).",
            ['oil_fragrance_stock_id' => $id, 'name' => $stock['name'], 'quantity' => $stock['quantity']],
            'oil_fragrance_stock',
            (string) $id,
            ['name' => $stock['name'], 'quantity' => $stock['quantity']],
            []
        );

        return response()->json(['message' => 'Oil fragrance stock record deleted.']);
    }

    public function movements(Request $request)
    {
        $this->assertBottleAccess($request);

        $params = [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->query('type')) {
            $params['type'] = 'eq.'.$request->query('type');
        }

        $movements = $this->loadMovementsWithUser('oil_fragrance_movements', $this->activeBranchId($request), 50, $params);

        return response()->json([
            'movements' => $movements,
            'scope' => $this->scopePayload($request),
        ]);
    }
}
