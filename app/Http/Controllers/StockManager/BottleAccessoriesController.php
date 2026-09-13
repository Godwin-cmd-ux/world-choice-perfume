<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class BottleAccessoriesController extends Controller
{
    private SupabaseService $supabase;
    private StockManagerScope $scope;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->scope = new StockManagerScope($this->supabase);
    }

    public function index()
    {
        $branchId = $this->scope->activeBranchId();

        $accessories = $this->supabase->query('bottle_accessories', $this->scope->branchParams([
            'select' => '*',
            'order' => 'type.asc,color.asc',
        ]));

        // Guard: if PostgREST returns empty (RLS/filter issue), fall back to all rows
        if (empty($accessories)) {
            $accessories = $this->supabase->query('bottle_accessories', [
                'select' => '*',
                'order' => 'type.asc,color.asc',
            ]);
        }

        // Group by type
        $grouped = [
            'straws' => [],
            'bottlenecks' => [],
            'bottle_tops' => [],
        ];

        foreach ($accessories as $a) {
            $type = $a['type'] ?? 'straws';
            if (!isset($grouped[$type])) $grouped[$type] = [];
            $grouped[$type][] = (object) $a;
        }

        $totalPackets = array_sum(array_map(fn($a) => $a['quantity'] ?? 0, $accessories));

        return view('stock-manager.bottle-accessories.index', [
            'grouped' => $grouped,
            'totalPackets' => $totalPackets,
            'activeBranchName' => $this->scope->activeBranchName(),
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
        ]);
    }

    public function create()
    {
        return view('stock-manager.bottle-accessories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:straws,bottlenecks,bottle_tops',
            'color' => 'required|in:silver,gold',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $branchId = auth()->user()->branch_id;

        // Check if an entry with same type+color exists at this branch
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

        // Record movement
        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $branchId,
            'type' => $validated['type'],
            'color' => $validated['color'],
            'movement_type' => 'stock_in',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? 'Stock in',
            'performed_by' => $this->performingUserId(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('stock-manager.bottle-accessories.index')->with('success', ucfirst(str_replace('_', ' ', $validated['type'])) . ' (' . ucfirst($validated['color']) . ') stock added successfully.');
    }

    public function edit($accessoryId)
    {
        $branchId = auth()->user()->branch_id;

        $item = $this->supabase->findOne('bottle_accessories', [
            'id' => $accessoryId,
            'branch_id' => $branchId,
        ]);

        if (!$item) {
            return back()->withErrors(['error' => 'Accessory stock record not found.']);
        }

        return view('stock-manager.bottle-accessories.edit', [
            'item' => (object) $item,
            'activeBranchName' => $this->scope->activeBranchName(),
        ]);
    }

    public function update(Request $request, $accessoryId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $branchId = auth()->user()->branch_id;

        $item = $this->supabase->findOne('bottle_accessories', [
            'id' => $accessoryId,
            'branch_id' => $branchId,
        ]);

        if (!$item) {
            return back()->withErrors(['error' => 'Accessory stock record not found.'])->withInput();
        }

        $this->supabase->update('bottle_accessories', [
            'quantity' => $validated['quantity'],
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $accessoryId]);

        return redirect()->route('stock-manager.bottle-accessories.index')->with('success', 'Accessory stock updated.');
    }

    public function destroy($accessoryId)
    {
        $branchId = auth()->user()->branch_id;

        $item = $this->supabase->findOne('bottle_accessories', [
            'id' => $accessoryId,
            'branch_id' => $branchId,
        ]);

        if (!$item) {
            return back()->withErrors(['error' => 'Accessory stock record not found.']);
        }

        $this->supabase->delete('bottle_accessories', ['id' => $accessoryId]);

        (new \App\Services\AuditService())->recordCriticalAction(
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

        return redirect()->route('stock-manager.bottle-accessories.index')->with('success', 'Accessory stock record deleted.');
    }

    public function stockOut(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
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

            if (!$existing || ($existing['quantity'] ?? 0) < $validated['quantity']) {
                return back()->withErrors(['quantity' => 'Insufficient stock. Available: ' . ($existing['quantity'] ?? 0) . ' packets.'])->withInput();
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
                'performed_by' => $this->performingUserId(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.bottle-accessories.index')->with('success', ucfirst(str_replace('_', ' ', $validated['type'])) . ' (' . ucfirst($validated['color']) . ') stock out recorded.');
        }

        $accessories = $this->supabase->query('bottle_accessories', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'type.asc,color.asc',
        ]);

        // Guard: fall back to all rows if branch filter returns empty
        if (empty($accessories)) {
            $accessories = $this->supabase->query('bottle_accessories', [
                'select' => '*',
                'order' => 'type.asc,color.asc',
            ]);
        }

        return view('stock-manager.bottle-accessories.stock-out', ['accessories' => collect($accessories)]);
    }

    private function performingUserId(): int
    {
        return (int) (auth()->user()->supabase_id ?? auth()->id());
    }

    /**
     * Same performed_by resolution as StockManagerController: prefer Supabase
     * users, then map local ids through supabase_id, then local names.
     */
    private function resolvePerformedByNames(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn($id) => $id > 0);
        $map = [];
        if (empty($ids)) {
            return $map;
        }

        $supById = [];
        $supRows = $this->supabase->query('users', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', $ids) . ')',
            'limit' => 200,
        ]);
        foreach ($supRows as $u) {
            $supById[(int) $u['id']] = $u['name'];
        }

        $localUsers = \App\Models\User::whereIn('id', $ids)
            ->get(['id', 'name', 'supabase_id'])
            ->keyBy('id');

        foreach ($ids as $id) {
            $local = $localUsers->get($id);
            if ($local && !empty($local->supabase_id) && (int) $local->supabase_id !== $id && isset($supById[(int) $local->supabase_id])) {
                $map[$id] = $supById[(int) $local->supabase_id];
            } elseif (isset($supById[$id])) {
                $map[$id] = $supById[$id];
            } elseif ($local && $local->name) {
                $map[$id] = $local->name;
            }
        }

        return $map;
    }

    public function movements(Request $request)
    {
        $branchId = $this->scope->activeBranchId();

        $params = $this->scope->branchParams([
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $movements = $this->supabase->query('bottle_accessories_movements', $params);

        // PHP-side join for performedBy (PostgREST expansion returns 0 rows)
        $userIds = [];
        foreach ($movements as $m) {
            if (!empty($m['performed_by'])) {
                $userIds[$m['performed_by']] = true;
            }
        }
        $names = $this->resolvePerformedByNames(array_keys($userIds));

        $movements = collect($movements)->map(function ($m) use ($names) {
            $m['performedBy'] = isset($m['performed_by']) && isset($names[$m['performed_by']])
                ? (object) ['id' => (int) $m['performed_by'], 'name' => $names[$m['performed_by']]]
                : null;
            return (object) $m;
        })->all();

        return view('stock-manager.bottle-accessories.movements', [
            'movements' => $movements,
            'activeBranchName' => $this->scope->activeBranchName(),
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
        ]);
    }
}
