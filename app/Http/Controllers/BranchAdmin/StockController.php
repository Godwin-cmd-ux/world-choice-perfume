<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
        ];

        $stocks = $this->supabase->query('branch_stock', $params);

        // Apply search filter in PHP
        if ($request->search) {
            $search = strtolower($request->search);
            $stocks = array_filter($stocks, function ($s) use ($search) {
                $product = $s['product'] ?? [];
                return str_contains(strtolower($product['name'] ?? ''), $search)
                    || str_contains(strtolower($product['brand'] ?? ''), $search);
            });
        }

        $stocks = array_values($stocks);
        $totalValue = array_sum(array_map(fn($s) => ($s['quantity'] ?? 0) * ($s['buying_cost'] ?? 0), $stocks));

        // Cast for views - nested objects too
        $stocks = collect($stocks)->map(function ($s) {
            if (isset($s['product']) && is_array($s['product'])) {
                if (isset($s['product']['images']) && is_array($s['product']['images'])) {
                    $s['product']['images'] = collect($s['product']['images']);
                }
                $s['product'] = (object) $s['product'];
            }
            return (object) $s;
        });

        return view('branch-admin.stock.index', ['stocks' => $stocks, 'totalValue' => $totalValue]);
    }

    public function entryForm()
    {
        $products = $this->supabase->query('products', [
            'is_active' => 'eq.true',
            'select' => '*',
            'order' => 'name.asc',
        ]);

        return view('branch-admin.stock.entry', ['products' => collect($products)->map(fn($p) => (object) $p)]);
    }

    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
            'buying_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'category' => 'nullable|in:Oil Fragrance,Brand Perfume',
            'date_received' => 'required|date',
        ]);

        $branchId = auth()->user()->branch_id;

        // Check if stock entry exists for this branch+product
        $existing = $this->supabase->findOne('branch_stock', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
        ]);

        if ($existing) {
            $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
            $this->supabase->update('branch_stock', [
                'quantity' => $newQty,
                'buying_cost' => $validated['buying_cost'],
                'selling_price' => $validated['selling_price'],
                'supplier' => $validated['supplier'] ?? null,
                'category' => $validated['category'] ?? null,
                'date_received' => $validated['date_received'],
                'entered_by' => auth()->id(),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);
        } else {
            $this->supabase->insert('branch_stock', [
                'branch_id' => $branchId,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'buying_cost' => $validated['buying_cost'],
                'selling_price' => $validated['selling_price'],
                'supplier' => $validated['supplier'] ?? null,
                'category' => $validated['category'] ?? null,
                'date_received' => $validated['date_received'],
                'entered_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        // Record stock movement
        $this->supabase->insert('stock_movements', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'type' => 'entry',
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['buying_cost'],
            'unit_price' => $validated['selling_price'],
            'performed_by' => auth()->id(),
            'notes' => "Stock entry from supplier: {$validated['supplier']}",
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('branch-admin.stock.index')->with('success', 'Stock entry recorded successfully.');
    }

    public function movements(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, product:products(id,name,brand), performedBy:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->product_id) {
            $params['product_id'] = "eq.{$request->product_id}";
        }

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $movements = $this->supabase->query('stock_movements', $params);
        $movements = collect($movements)->map(function ($m) {
            if (isset($m['product']) && is_array($m['product'])) $m['product'] = (object) $m['product'];
            if (isset($m['performedBy']) && is_array($m['performedBy'])) $m['performedBy'] = (object) $m['performedBy'];
            return (object) $m;
        });

        $products = $this->supabase->query('products', [
            'is_active' => 'eq.true',
            'select' => 'id,name,brand',
            'order' => 'name.asc',
        ]);

        $productObjects = [];
        foreach ($products as $p) {
            $productObjects[] = (object) $p;
        }

        return view('branch-admin.stock.movements', ['movements' => $movements, 'products' => $productObjects]);
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required',
            'type' => 'required|in:adjustment,damage,missing,return',
            'quantity' => 'required|integer|min:1',
            'notes' => 'required|string',
        ]);

        $branchId = auth()->user()->branch_id;

        $stock = $this->supabase->findOne('branch_stock', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
        ]);

        if (!$stock) {
            return back()->withErrors(['product_id' => 'Stock not found for this product.']);
        }

        $quantityChange = in_array($validated['type'], ['return']) ? $validated['quantity'] : -$validated['quantity'];
        $newQty = ($stock['quantity'] ?? 0) + $quantityChange;

        if ($newQty < 0) {
            return back()->withErrors(['quantity' => 'Insufficient stock for this adjustment.']);
        }

        $this->supabase->update('branch_stock', [
            'quantity' => $newQty,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $stock['id']]);

        // Record stock movement
        $this->supabase->insert('stock_movements', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
            'type' => $validated['type'],
            'quantity' => $quantityChange,
            'performed_by' => auth()->id(),
            'notes' => $validated['notes'],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('branch-admin.stock.movements')->with('success', 'Stock adjustment recorded.');
    }
}
