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
        $branchId = auth()->user()->branch_id;
        $isGlobal = $this->scope->isGlobalStockManager();

        $accessories = $this->supabase->query('bottle_accessories', $this->scope->branchParams([
            'select' => '*',
            'order' => 'type.asc,color.asc',
        ], $branchId));

        // Guard: if PostgREST returns empty (RLS/filter issue), fall back to all rows
        if (!$isGlobal && empty($accessories)) {
            $accessories = $this->supabase->query('bottle_accessories', [
                'select' => '*',
                'order' => 'type.asc,color.asc',
            ]);
        }

        // Group by type + color, aggregating quantities across branches for the
        // global (Kinondoni) stock manager.
        $grouped = [
            'straws' => [],
            'bottlenecks' => [],
            'bottle_tops' => [],
        ];

        $totals = [];
        foreach ($accessories as $a) {
            $type = $a['type'] ?? 'straws';
            $color = $a['color'] ?? 'silver';
            $key = $type . '|' . $color;
            $totals[$key] = ($totals[$key] ?? 0) + (int) ($a['quantity'] ?? 0);
        }

        foreach ($totals as $key => $qty) {
            [$type, $color] = explode('|', $key);
            $grouped[$type][] = (object) ['color' => $color, 'quantity' => $qty];
        }

        $totalPackets = array_sum($totals);

        return view('stock-manager.bottle-accessories.index', [
            'grouped' => $grouped,
            'totalPackets' => $totalPackets,
            'isGlobalScope' => $isGlobal,
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
            'performed_by' => auth()->id(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('stock-manager.bottle-accessories.index')->with('success', ucfirst(str_replace('_', ' ', $validated['type'])) . ' (' . ucfirst($validated['color']) . ') stock added successfully.');
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
                'performed_by' => auth()->id(),
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

    public function movements(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $isGlobal = $this->scope->isGlobalStockManager();

        $params = $this->scope->branchParams([
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 50,
        ], $branchId);

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
        $users = [];
        if (!empty($userIds)) {
            $userRows = $this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.' . implode(',', array_keys($userIds)),
                'limit' => 100,
            ]);
            foreach ($userRows as $u) {
                $users[$u['id']] = $u;
            }
        }

        $branchNames = $isGlobal ? $this->scope->branchNameMap(array_column($movements, 'branch_id')) : [];

        $movements = collect($movements)->map(function ($m) use ($users, $isGlobal, $branchNames) {
            $m['performedBy'] = isset($m['performed_by']) && isset($users[$m['performed_by']])
                ? (object) ['id' => $users[$m['performed_by']]['id'], 'name' => $users[$m['performed_by']]['name']]
                : null;
            if ($isGlobal) {
                $m['branchName'] = $branchNames[(int) ($m['branch_id'] ?? 0)] ?? null;
            }
            return (object) $m;
        })->all();

        return view('stock-manager.bottle-accessories.movements', [
            'movements' => $movements,
            'isGlobalScope' => $isGlobal,
        ]);
    }
}
