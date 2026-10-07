<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of BottleAccessoriesController, gated like the website's
 * `stock-manager.bottle-access` middleware: a products-based branch gets 403.
 */
class SmBottleAccessoriesController extends SmBaseController
{
    public function index(Request $request)
    {
        $this->assertBottleAccess($request);

        // Strictly this branch's rows — branches with no accessories stock
        // see zero, never another branch's stock.
        $accessories = $this->supabase->query('bottle_accessories', [
            'select' => '*',
            'branch_id' => 'eq.'.$this->activeBranchId($request),
            'order' => 'type.asc,color.asc',
        ]);

        $grouped = [
            'straws' => [],
            'bottlenecks' => [],
            'bottle_tops' => [],
        ];

        foreach ($accessories as $a) {
            $type = $a['type'] ?? 'straws';
            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $a;
        }

        $totalPackets = array_sum(array_map(fn ($a) => $a['quantity'] ?? 0, $accessories));

        return response()->json([
            'grouped' => $grouped,
            'totalPackets' => $totalPackets,
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $validated = $request->validate([
            'type' => 'required|in:straws,bottlenecks,bottle_tops',
            'color' => 'required|in:silver,gold',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $branchId = $this->ownBranchId($request);

        $existing = $this->supabase->findOne('bottle_accessories', [
            'branch_id' => $branchId,
            'type' => $validated['type'],
            'color' => $validated['color'],
        ]);

        if ($existing) {
            $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
            $this->supabase->update('bottle_accessories', [
                'quantity' => $newQty,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);
        } else {
            $this->supabase->insert('bottle_accessories', [
                'branch_id' => $branchId,
                'type' => $validated['type'],
                'color' => $validated['color'],
                'quantity' => $validated['quantity'],
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $branchId,
            'type' => $validated['type'],
            'color' => $validated['color'],
            'movement_type' => 'stock_in',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Stock in',
            'performed_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => ucfirst(str_replace('_', ' ', $validated['type'])).' ('.ucfirst($validated['color']).') stock added successfully.',
        ]);
    }

    public function update(Request $request, int $accessoryId)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $branchId = $this->ownBranchId($request);

        $item = $this->supabase->findOne('bottle_accessories', [
            'id' => $accessoryId,
            'branch_id' => $branchId,
        ]);

        if (! $item) {
            $this->fail(['error' => 'Accessory stock record not found.']);
        }

        $this->supabase->update('bottle_accessories', [
            'quantity' => $validated['quantity'],
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $accessoryId]);

        return response()->json(['message' => 'Accessory stock updated.']);
    }

    public function destroy(Request $request, int $accessoryId)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $item = $this->supabase->findOne('bottle_accessories', [
            'id' => $accessoryId,
            'branch_id' => $branchId,
        ]);

        if (! $item) {
            $this->fail(['error' => 'Accessory stock record not found.']);
        }

        $this->supabase->delete('bottle_accessories', ['id' => $accessoryId]);

        (new AuditService)->recordCriticalAction(
            'stock_deleted',
            'bottle_accessories_stock_deleted',
            'Bottle Accessories Stock Deleted',
            "Bottle accessories stock record deleted ({$item['type']} {$item['color']}, {$item['quantity']} units).",
            ['bottle_accessories_id' => $accessoryId, 'type' => $item['type'] ?? '', 'color' => $item['color'] ?? '', 'quantity' => $item['quantity'] ?? 0],
            'bottle_accessories',
            (string) $accessoryId,
            ['type' => $item['type'] ?? '', 'color' => $item['color'] ?? '', 'quantity' => $item['quantity'] ?? 0],
            []
        );

        return response()->json(['message' => 'Accessory stock record deleted.']);
    }

    public function stockOut(Request $request)
    {
        $this->assertBottleAccess($request);
        $this->assertWritable($request);

        $branchId = $this->ownBranchId($request);

        $validated = $request->validate([
            'type' => 'required|in:straws,bottlenecks,bottle_tops',
            'color' => 'required|in:silver,gold',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $existing = $this->supabase->findOne('bottle_accessories', [
            'branch_id' => $branchId,
            'type' => $validated['type'],
            'color' => $validated['color'],
        ]);

        if (! $existing || ($existing['quantity'] ?? 0) < $validated['quantity']) {
            $this->fail(['quantity' => 'Insufficient stock. Available: '.($existing['quantity'] ?? 0).' packets.']);
        }

        $newQty = ($existing['quantity'] ?? 0) - $validated['quantity'];
        $this->supabase->update('bottle_accessories', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $existing['id']]);

        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $branchId,
            'type' => $validated['type'],
            'color' => $validated['color'],
            'movement_type' => 'stock_out',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Stock out',
            'performed_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => ucfirst(str_replace('_', ' ', $validated['type'])).' ('.ucfirst($validated['color']).') stock out recorded.',
        ]);
    }

    public function movements(Request $request)
    {
        $this->assertBottleAccess($request);

        $params = $this->branchParams($request, [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        if ($request->query('type')) {
            $params['type'] = 'eq.'.$request->query('type');
        }

        $movements = $this->supabase->query('bottle_accessories_movements', $params);

        $userIds = [];
        foreach ($movements as $m) {
            if (! empty($m['performed_by'])) {
                $userIds[$m['performed_by']] = true;
            }
        }
        $names = $this->resolvePerformedByNames(array_keys($userIds));

        $movements = collect($movements)->map(function ($m) use ($names) {
            $m['performedBy'] = isset($m['performed_by']) && isset($names[$m['performed_by']])
                ? ['id' => (int) $m['performed_by'], 'name' => $names[$m['performed_by']]]
                : null;

            return $m;
        })->values()->all();

        return response()->json([
            'movements' => $movements,
            'scope' => $this->scopePayload($request),
        ]);
    }
}
