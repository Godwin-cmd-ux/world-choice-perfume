<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;


class StockManagerController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    // ========================
    // DASHBOARD
    // ========================

    public function dashboard()
    {
        $branchId = auth()->user()->branch_id;

        // Product stock stats
        $productStock = $this->supabase->query('branch_stock', [
            'select' => 'quantity,buying_cost,selling_price',
            'branch_id' => "eq.{$branchId}",
        ]);
        $totalProductItems = array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $productStock));
        $totalProductValue = array_sum(array_map(fn($s) => ($s['quantity'] ?? 0) * ($s['buying_cost'] ?? 0), $productStock));
        $lowStockProducts = count(array_filter($productStock, fn($s) => ($s['quantity'] ?? 0) <= 5));

        // Bottle stock stats
        $bottleStock = $this->supabase->query('bottle_stock', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
        ]);
        $totalBottles = array_sum(array_map(fn($b) => $b['quantity'] ?? 0, $bottleStock));

        // Oil fragrance stats
        $oilStock = $this->supabase->query('oil_fragrance_stock', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
        ]);
        $totalOilFragrances = array_sum(array_map(fn($o) => $o['quantity'] ?? 0, $oilStock));

        // Recent movements
        $recentBottleMovements = $this->supabase->query('bottle_stock_movements', [
            'select' => '*, performedBy:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 5,
        ]);
        $recentOilMovements = $this->supabase->query('oil_fragrance_movements', [
            'select' => '*, performedBy:users(id,name)',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 5,
        ]);

        return view('stock-manager.dashboard', compact(
            'totalProductItems', 'totalProductValue', 'lowStockProducts',
            'totalBottles', 'bottleStock', 'totalOilFragrances', 'oilStock',
            'recentBottleMovements', 'recentOilMovements'
        ));
    }

    // ========================
    // PRODUCT STOCK (same as branch admin)
    // ========================

    public function productStock(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $params = [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
        ];

        $stocks = $this->supabase->query('branch_stock', $params);

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

        $stocks = collect($stocks)->map(function ($s) {
            if (isset($s['product']) && is_array($s['product'])) {
                if (isset($s['product']['images']) && is_array($s['product']['images'])) {
                    $s['product']['images'] = collect($s['product']['images']);
                }
                $s['product'] = (object) $s['product'];
            }
            return (object) $s;
        });

        return view('stock-manager.product-stock', ['stocks' => $stocks, 'totalValue' => $totalValue]);
    }

    public function productStockEntry()
    {
        $products = $this->supabase->query('products', [
            'is_active' => 'eq.true',
            'select' => '*',
            'order' => 'name.asc',
        ]);

        return view('stock-manager.product-stock-entry', ['products' => collect($products)->map(fn($p) => (object) $p)]);
    }

    public function storeProductStockEntry(Request $request)
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

        return redirect()->route('stock-manager.product-stock')->with('success', 'Stock entry recorded successfully.');
    }

    public function productStockMovements(Request $request)
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

        return view('stock-manager.product-stock-movements', ['movements' => $movements, 'products' => $productObjects]);
    }

    // ========================
    // BOTTLE STOCK
    // ========================

    public function bottleStock()
    {
        $branchId = auth()->user()->branch_id;

        $bottles = $this->supabase->query('bottle_stock', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'volume.asc',
        ]);

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        $bottleMap = [];
        foreach ($bottles as $b) {
            $bottleMap[$b['volume']] = $b['quantity'] ?? 0;
        }

        return view('stock-manager.bottle-stock', ['volumes' => $volumes, 'bottleMap' => $bottleMap]);
    }

    public function bottleStockIn(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'volume' => 'required|in:6ml,12ml,30ml,50ml,100ml',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            // Upsert bottle stock
            $existing = $this->supabase->findOne('bottle_stock', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
            ]);

            if ($existing) {
                $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
                $this->supabase->update('bottle_stock', [
                    'quantity' => $newQty,
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $existing['id']]);
            } else {
                $this->supabase->insert('bottle_stock', [
                    'branch_id' => $branchId,
                    'volume' => $validated['volume'],
                    'quantity' => $validated['quantity'],
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            // Record movement
            $this->supabase->insert('bottle_stock_movements', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Stock in',
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.bottle-stock')->with('success', 'Bottle stock added successfully.');
        }

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        return view('stock-manager.bottle-stock-in', ['volumes' => $volumes]);
    }

    public function bottleStockOut(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'volume' => 'required|in:6ml,12ml,30ml,50ml,100ml',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            $existing = $this->supabase->findOne('bottle_stock', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
            ]);

            if (!$existing || ($existing['quantity'] ?? 0) < $validated['quantity']) {
                return back()->withErrors(['quantity' => 'Insufficient bottle stock.']);
            }

            $newQty = ($existing['quantity'] ?? 0) - $validated['quantity'];
            $this->supabase->update('bottle_stock', [
                'quantity' => $newQty,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);

            $this->supabase->insert('bottle_stock_movements', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
                'type' => 'stock_out',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Stock out for production',
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.bottle-stock')->with('success', 'Bottle stock out recorded.');
        }

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        return view('stock-manager.bottle-stock-out', ['volumes' => $volumes]);
    }

    public function bottleBroken(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'volume' => 'required|in:6ml,12ml,30ml,50ml,100ml',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            $existing = $this->supabase->findOne('bottle_stock', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
            ]);

            if (!$existing || ($existing['quantity'] ?? 0) < $validated['quantity']) {
                return back()->withErrors(['quantity' => 'Insufficient bottle stock.']);
            }

            $newQty = ($existing['quantity'] ?? 0) - $validated['quantity'];
            $this->supabase->update('bottle_stock', [
                'quantity' => $newQty,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);

            $this->supabase->insert('bottle_stock_movements', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
                'type' => 'broken',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Broken bottles',
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.bottle-stock')->with('success', 'Broken bottles recorded.');
        }

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        return view('stock-manager.bottle-broken', ['volumes' => $volumes]);
    }

    public function bottleMovements(Request $request)
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

        if ($request->volume) {
            $params['volume'] = "eq.{$request->volume}";
        }

        $movements = $this->supabase->query('bottle_stock_movements', $params);
        $movements = collect($movements)->map(function ($m) {
            if (isset($m['performedBy']) && is_array($m['performedBy'])) $m['performedBy'] = (object) $m['performedBy'];
            return (object) $m;
        });

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        return view('stock-manager.bottle-movements', ['movements' => $movements, 'volumes' => $volumes]);
    }

    // ========================
    // OIL FRAGRANCE STOCK
    // ========================

    public function oilFragranceStock()
    {
        $branchId = auth()->user()->branch_id;

        $oils = $this->supabase->query('oil_fragrance_stock', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'name.asc',
        ]);

        $totalQuantity = array_sum(array_map(fn($o) => $o['quantity'] ?? 0, $oils));

        return view('stock-manager.oil-fragrance-stock', ['oils' => collect($oils), 'totalQuantity' => $totalQuantity]);
    }

    public function oilFragranceStockIn(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            $existing = $this->supabase->findOne('oil_fragrance_stock', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
            ]);

            if ($existing) {
                $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
                $this->supabase->update('oil_fragrance_stock', [
                    'quantity' => $newQty,
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $existing['id']]);
            } else {
                $this->supabase->insert('oil_fragrance_stock', [
                    'branch_id' => $branchId,
                    'name' => $validated['name'],
                    'quantity' => $validated['quantity'],
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Stock in',
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.oil-fragrance')->with('success', 'Oil fragrance stock added.');
        }

        return view('stock-manager.oil-fragrance-stock-in');
    }

    public function oilFragranceStockOut(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            $existing = $this->supabase->findOne('oil_fragrance_stock', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
            ]);

            if (!$existing || ($existing['quantity'] ?? 0) < $validated['quantity']) {
                return back()->withErrors(['quantity' => 'Insufficient oil fragrance stock.']);
            }

            $newQty = ($existing['quantity'] ?? 0) - $validated['quantity'];
            $this->supabase->update('oil_fragrance_stock', [
                'quantity' => $newQty,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);

            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'type' => 'stock_out',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Used for production',
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.oil-fragrance')->with('success', 'Oil fragrance stock out recorded.');
        }

        $branchId = auth()->user()->branch_id;
        $oils = $this->supabase->query('oil_fragrance_stock', [
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'name.asc',
        ]);

        return view('stock-manager.oil-fragrance-stock-out', ['oils' => collect($oils)]);
    }

    public function oilFragranceMovements(Request $request)
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

        $movements = $this->supabase->query('oil_fragrance_movements', $params);
        $movements = collect($movements)->map(function ($m) {
            if (isset($m['performedBy']) && is_array($m['performedBy'])) $m['performedBy'] = (object) $m['performedBy'];
            return (object) $m;
        });

        return view('stock-manager.oil-fragrance-movements', ['movements' => $movements]);
    }

    // ========================
    // QR CODE
    // ========================

    public function qrCode()
    {
        $url = 'https://world-choice-perfume.onrender.com/';
        return view('stock-manager.qr-code', ['url' => $url]);
    }
}
