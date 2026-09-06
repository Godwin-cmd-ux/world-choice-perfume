<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class BottleAccessoriesController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index()
    {
        $branchId = auth()->user()->branch_id;

        $accessories = $this->supabase->query('bottle_accessories', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'type.asc,name.asc',
        ]);

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

        // Summary stats
        $totalPackets = array_sum(array_map(fn($a) => $a['quantity'] ?? 0, $accessories));

        return view('stock-manager.bottle-accessories.index', ['grouped' => $grouped, 'totalPackets' => $totalPackets]);
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

        return view('stock-manager.bottle-accessories.stock-out', ['accessories' => collect($accessories)]);
    }

    public function movements(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, performedBy:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $movements = collect($this->supabase->query('bottle_accessories_movements', $params))
            ->map(function ($m) {
                if (isset($m['performedBy']) && is_array($m['performedBy'])) {
                    $m['performedBy'] = (object) $m['performedBy'];
                }
                return (object) $m;
            })->all();

        return view('stock-manager.bottle-accessories.movements', ['movements' => $movements]);
    }
}
