<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of the Bottle Stock screens.
 *
 * Every route is gated by assertBottleAccess — the stateless twin of the
 * website's `stock-manager.bottle-access` middleware — so a products-based
 * branch gets the same 403 its website sees.
 */
class SmBottleStockController extends SmBaseController
{
    public function index(Request $request)
    {
        $this->assertBottleAccess($request);

        $params = $this->branchParams($request, [
            'select' => '*',
            'order' => 'volume.asc',
        ]);

        $records = $this->supabase->query('bottle_stock', $params);

        if ($request->query('search')) {
            $search = strtolower((string) $request->query('search'));
            $records = array_filter($records, function ($b) use ($search) {
                return str_contains(strtolower($b['volume'] ?? ''), $search);
            });
        }

        $records = array_values($records);

        // Per-volume totals plus a per-record variant label, exactly like the
        // website's bottleMap / variantLabelMap.
        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        $bottleMap = [];
        $variantLabelMap = [];
        foreach ($records as $b) {
            $labelVol = $this->bottles->parseVolume((string) ($b['volume'] ?? ''));
            $volumeKey = $labelVol !== null ? "{$labelVol}ml" : (string) ($b['volume'] ?? '');
            $bottleMap[$volumeKey] = ($bottleMap[$volumeKey] ?? 0) + (int) ($b['quantity'] ?? 0);
            $variantLabelMap[$b['id']] = $this->bottles->variantLabel((string) ($b['variant'] ?? 'plain'), $labelVol ?? 0);
        }

        return response()->json([
            'volumes' => $volumes,
            'bottleMap' => $bottleMap,
            'variantLabelMap' => $variantLabelMap,
            'bottleRecords' => $records,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Edit-form data (website: editBottleStock). */
    public function show(Request $request, int $id)
    {
        $this->assertBottleAccess($request);

        $stock = $this->supabase->findOne('bottle_stock', [
            'id' => $id,
            'branch_id' => $this->ownBranchId($request),
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Bottle stock record not found.']);
        }

        return response()->json(['record' => $stock]);
    }

    public function update(Request $request, int $id)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'has_logo' => 'nullable|in:yes,no',
            'logo_color' => 'nullable|in:yellow,black,white',
            'has_box' => 'nullable|in:yes,no',
            'box_color' => 'nullable|in:black,white',
        ]);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('bottle_stock', [
            'id' => $id,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Bottle stock record not found.']);
        }

        $volume = $this->bottles->parseVolume((string) ($stock['volume'] ?? ''));
        $variant = (string) ($stock['variant'] ?? 'plain');
        if (($validated['has_box'] ?? null) !== null) {
            $variant = $this->bottles->variantKey($volume ?? 0, $validated['has_box'], $validated['has_logo'], $validated['logo_color']);
        }

        $data = [
            'quantity' => $validated['quantity'],
            'has_logo' => $validated['has_logo'] ?? null,
            'logo_color' => $validated['logo_color'] ?? null,
            'has_box' => $validated['has_box'] ?? null,
            'box_color' => $validated['box_color'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];
        if ($this->supabase->tableHasColumn('bottle_stock', 'variant')) {
            $data['variant'] = $variant;
        }

        if (empty($this->supabase->update('bottle_stock', $data, ['id' => $id]))) {
            $this->fail(['error' => 'Could not update the bottle stock record. Please try again.']);
        }

        return response()->json(['message' => 'Bottle stock updated.']);
    }

    public function destroy(Request $request, int $id)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $stock = $this->supabase->findOne('bottle_stock', [
            'id' => $id,
            'branch_id' => $branchId,
        ]);

        if (! $stock) {
            $this->fail(['error' => 'Bottle stock record not found.']);
        }

        $this->supabase->delete('bottle_stock', ['id' => $id]);

        (new AuditService)->recordCriticalAction(
            'stock_deleted',
            'bottle_stock_deleted',
            'Bottle Stock Deleted',
            "Bottle stock record deleted (volume {$stock['volume']}, {$stock['quantity']} units).",
            ['bottle_stock_id' => $id, 'volume' => $stock['volume'], 'quantity' => $stock['quantity']],
            'bottle_stock',
            (string) $id,
            ['volume' => $stock['volume'], 'quantity' => $stock['quantity']],
            []
        );

        return response()->json(['message' => 'Bottle stock record deleted.']);
    }

    /** Website: POST /bottle-stock/in (the GET just serves a static form). */
    public function storeIn(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'volume' => 'required|in:6ml,12ml,30ml,50ml,100ml',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'has_box' => 'nullable|in:yes,no',
            'has_logo' => 'nullable|in:yes,no',
            'logo_color' => 'nullable|in:yellow,black,white',
        ]);

        $volume = $this->bottles->parseVolume((string) $validated['volume']);
        $hasBox = $hasLogo = $logoColor = null;

        // 30/50/100ml carry the box/logo/color detail flow; 6ml and 12ml do not.
        if ($this->bottles->volumeHasDetails($volume)) {
            $hasBox = $validated['has_box'] ?? null;

            if ($hasBox === 'yes') {
                $hasLogo = $validated['has_logo'] ?? null;
                if ($hasLogo !== 'yes' && $hasLogo !== 'no') {
                    $this->fail(['has_logo' => 'Select whether the bottles have a logo.']);
                }

                $logoColor = $validated['logo_color'] ?? null;
                $allowedColors = $hasLogo === 'yes' ? ['yellow', 'black'] : ['black', 'white'];
                if ($logoColor === null || ! in_array($logoColor, $allowedColors, true)) {
                    $this->fail([
                        'logo_color' => $hasLogo === 'yes'
                            ? 'Select a logo color (Yellow or Black).'
                            : 'Select a color (Black or White).',
                    ]);
                }
            } elseif ($hasBox !== 'no') {
                $this->fail(['has_box' => 'Select whether the bottles have a box.']);
            }
        }

        $variant = $this->bottles->variantKey($volume, $hasBox, $hasLogo, $logoColor);
        $label = $this->bottles->volumeLabel($volume);
        $now = now()->toIso8601String();

        $useVariants = $this->supabase->tableHasColumn('bottle_stock', 'variant');

        $stored = false;

        if ($useVariants) {
            $existing = $this->supabase->findOne('bottle_stock', [
                'branch_id' => $branchId,
                'volume' => $label,
                'variant' => $variant,
            ]);

            if ($existing) {
                $stored = ! empty($this->supabase->update('bottle_stock', [
                    'quantity' => ($existing['quantity'] ?? 0) + $validated['quantity'],
                    'has_logo' => $hasLogo,
                    'logo_color' => $logoColor,
                    'has_box' => $hasBox,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $stored = $this->supabase->insert('bottle_stock', [
                    'branch_id' => $branchId,
                    'volume' => $label,
                    'variant' => $variant,
                    'quantity' => $validated['quantity'],
                    'has_logo' => $hasLogo,
                    'logo_color' => $logoColor,
                    'has_box' => $hasBox,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]) !== null;
            }
        } else {
            // Variant column not yet migrated — one row per volume.
            $existing = $this->supabase->findOne('bottle_stock', [
                'branch_id' => $branchId,
                'volume' => $label,
            ]);

            if ($existing) {
                $stored = ! empty($this->supabase->update('bottle_stock', [
                    'quantity' => ($existing['quantity'] ?? 0) + $validated['quantity'],
                    'has_logo' => $hasLogo,
                    'logo_color' => $logoColor,
                    'has_box' => $hasBox,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $stored = $this->supabase->insert('bottle_stock', [
                    'branch_id' => $branchId,
                    'volume' => $label,
                    'quantity' => $validated['quantity'],
                    'has_logo' => $hasLogo,
                    'logo_color' => $logoColor,
                    'has_box' => $hasBox,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]) !== null;
            }
        }

        if (! $stored) {
            $this->fail(['error' => 'Could not save bottle stock to the database. Please try again.']);
        }

        $movementReason = $validated['reason'] ?? 'Stock in';
        if ($this->bottles->volumeHasDetails($volume)) {
            $movementReason .= ' ['.$this->bottles->variantLabel($variant, $volume).']';
        }

        $movement = [
            'branch_id' => $branchId,
            'volume' => $label,
            'type' => 'stock_in',
            'quantity' => $validated['quantity'],
            'reason' => $movementReason,
            'has_logo' => $hasLogo,
            'logo_color' => $logoColor,
            'has_box' => $hasBox,
            'performed_by' => $this->performingUserId($request),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if ($useVariants) {
            $movement['variant'] = $variant;
        }
        $this->supabase->insert('bottle_stock_movements', $movement);

        return response()->json([
            'message' => 'Bottle stock added successfully.',
            'variant_tracking' => $useVariants,
        ]);
    }

    /** Stock of each bottle variant — the picking data for the broken form. */
    public function brokenOptions(Request $request)
    {
        $this->assertBottleAccess($request);

        $branchId = $this->ownBranchId($request);

        return response()->json([
            'volumes' => ['6ml', '12ml', '30ml', '50ml', '100ml'],
            'bottleVariants' => $this->bottles->variantStock($branchId),
        ]);
    }

    /** Website: POST /bottle-stock/broken. */
    public function storeBroken(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'volume' => 'required|in:6ml,12ml,30ml,50ml,100ml',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'variant' => 'nullable|string|max:32',
        ]);

        $volume = $this->bottles->parseVolume((string) $validated['volume']);
        $label = $this->bottles->volumeLabel($volume);
        $variant = (string) ($validated['variant'] ?? '');

        $rows = $this->supabase->queryFresh('bottle_stock', [
            'branch_id' => "eq.{$branchId}",
            'volume' => "eq.{$label}",
        ]);

        $target = null;
        foreach ($rows as $row) {
            $rowVariant = (string) ($row['variant'] ?? 'plain');
            if ($variant !== '' && $rowVariant !== $variant) {
                continue;
            }
            if ((int) ($row['quantity'] ?? 0) >= $validated['quantity']) {
                $target = $row;

                break;
            }
        }

        if (! $target) {
            $this->fail(['quantity' => 'Insufficient bottle stock.']);
        }

        $newQty = (int) ($target['quantity'] ?? 0) - $validated['quantity'];
        if (empty($this->supabase->update('bottle_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $target['id']]))) {
            $this->fail(['error' => 'Could not update bottle stock. Please try again.']);
        }

        $movement = [
            'branch_id' => $branchId,
            'volume' => $label,
            'type' => 'broken',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Broken bottles',
            'performed_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        if ($this->supabase->tableHasColumn('bottle_stock_movements', 'variant')) {
            $movement['variant'] = (string) ($target['variant'] ?? 'plain');
        }
        $this->supabase->insert('bottle_stock_movements', $movement);

        return response()->json(['message' => 'Broken bottles recorded.']);
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

        if ($request->query('volume')) {
            $params['volume'] = 'eq.'.$request->query('volume');
        }

        $movements = $this->loadMovementsWithUser('bottle_stock_movements', $this->activeBranchId($request), 50, $params);

        return response()->json([
            'movements' => $movements,
            'volumes' => ['6ml', '12ml', '30ml', '50ml', '100ml'],
            'scope' => $this->scopePayload($request),
        ]);
    }
}
