<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\BottleStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;


class StockManagerController extends Controller
{
    private SupabaseService $supabase;
    private BottleStockService $bottles;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
    }

    // ========================
    // DASHBOARD
    // ========================

    public function dashboard()
    {
        $branchId = auth()->user()->branch_id;

        // Product stock stats
        $productStock = $this->supabase->query('branch_stock', [
            'select' => 'quantity,selling_price',
            'branch_id' => "eq.{$branchId}",
        ]);
        $totalProductItems = array_sum(array_map(fn($s) => $s['quantity'] ?? 0, $productStock));
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
        $recentBottleMovements = $this->loadMovementsWithUser('bottle_stock_movements', $branchId, 5);
        $recentOilMovements = $this->loadMovementsWithUser('oil_fragrance_movements', $branchId, 5);

        return view('stock-manager.dashboard', compact(
            'totalProductItems', 'lowStockProducts',
            'totalBottles', 'bottleStock', 'totalOilFragrances', 'oilStock',
            'recentBottleMovements', 'recentOilMovements'
        ));
    }

    /**
     * Load movements with a PHP-side join for performedBy.
     * PostgREST expansions like performedBy:users(id,name) silently return 0 rows,
     * so we fetch the user data separately and join in PHP.
     */
    private function loadMovementsWithUser(string $table, int $branchId, int $limit, array $extraParams = []): array
    {
        $params = array_merge([
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => $limit,
        ], $extraParams);

        $rows = $this->supabase->query($table, $params);

        // Collect user IDs
        $userIds = [];
        foreach ($rows as $r) {
            if (!empty($r['performed_by'])) {
                $userIds[$r['performed_by']] = true;
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

        return collect($rows)->map(function ($r) use ($users) {
            $r['performedBy'] = !empty($r['performed_by']) && isset($users[$r['performed_by']])
                ? (object) ['id' => $users[$r['performed_by']]['id'], 'name' => $users[$r['performed_by']]['name']]
                : null;
            return (object) $r;
        })->all();
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
        $totalValue = array_sum(array_map(fn($s) => ($s['quantity'] ?? 0) * ($s['selling_price'] ?? 0), $stocks));

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
            'select' => 'id,name,brand,category',
            'order' => 'name.asc',
        ]);

        // Build a lookup of category by product id for JS auto-fill.
        $categoryMap = [];
        foreach ($products as $p) {
            $categoryMap[$p['id']] = $p['category'] ?? null;
        }

        return view('stock-manager.product-stock-entry', [
            'products' => collect($products)->map(fn($p) => (object) $p),
            'categoryMap' => $categoryMap,
        ]);
    }

    public function storeProductStockEntry(Request $request)
    {            $validated = $request->validate([
                'product_id' => 'required',
                'quantity' => 'required|integer|min:1',
                'selling_price' => 'required|numeric|min:0',
                'category' => 'required|in:Oil Fragrance,Brand Perfume',
                'bottle_volume' => 'nullable|integer|in:6,12,30,50,100',
                'date_received' => 'required|date',
            ]);

            $branchId = auth()->user()->branch_id;
            $category = $validated['category'];
            $bottleVolume = $validated['bottle_volume'] ?? null;

        // Oil fragrance entries consume empty bottles: require the volume and
        // make sure enough of that volume is in stock before recording the entry.
        if ($category === 'Oil Fragrance') {
            if (empty($bottleVolume)) {
                return back()->withErrors(['bottle_volume' => 'Bottle volume is required for Oil Fragrance entries.'])->withInput();
            }

            $available = $this->bottles->stockMap($branchId);
            $bottleVolume = (int) $bottleVolume;

            if (($available[$bottleVolume] ?? 0) < $validated['quantity']) {
                return back()->withErrors([
                    'bottle_volume' => "Insufficient bottle stock for {$bottleVolume}ml. Available: " . ($available[$bottleVolume] ?? 0) . '.',
                ])->withInput();
            }
        }

        $existing = $this->supabase->findOne('branch_stock', [
            'branch_id' => $branchId,
            'product_id' => $validated['product_id'],
        ]);

        if ($existing) {
            $newQty = ($existing['quantity'] ?? 0) + $validated['quantity'];
            $this->supabase->update('branch_stock', [
                'quantity' => $newQty,
                'selling_price' => $validated['selling_price'],                    'category' => $category,
                    'date_received' => $validated['date_received'],
                    'entered_by' => auth()->id(),
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $existing['id']]);
            } else {
                $this->supabase->insert('branch_stock', [
                    'branch_id' => $branchId,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'selling_price' => $validated['selling_price'],
                    'category' => $category,
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
            'unit_price' => $validated['selling_price'],
            'performed_by' => auth()->id(),
            'notes' => 'Stock entry',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Auto-outstock empty bottles used to bottle the oil fragrance entry.
        if ($category === 'Oil Fragrance' && $bottleVolume) {
            $this->bottles->deduct(
                $branchId,
                $bottleVolume,
                (int) $validated['quantity'],
                'Auto outstock for oil fragrance stock entry',
                (string) auth()->id()
            );
        }

        return redirect()->route('stock-manager.product-stock')->with('success', 'Stock entry recorded successfully.');
    }

    public function updateProductStock(Request $request, $stockId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $branchId = auth()->user()->branch_id;

        $stock = $this->supabase->findOne('branch_stock', [
            'id' => $stockId,
            'branch_id' => $branchId,
        ]);

        if (!$stock) {
            return back()->withErrors(['error' => 'Stock record not found.'])->withInput();
        }

        $oldQty = $stock['quantity'] ?? 0;
        $newQty = $validated['quantity'];
        $oldPrice = $stock['selling_price'] ?? 0;
        $newPrice = $validated['selling_price'];

        $this->supabase->update('branch_stock', [
            'quantity' => $newQty,
            'selling_price' => $newPrice,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $stockId]);

        // Log movement if quantity changed
        if ($newQty != $oldQty) {
            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $stock['product_id'],
                'type' => $newQty > $oldQty ? 'entry' : 'sale',
                'quantity' => $newQty - $oldQty,
                'unit_price' => $oldPrice,
                'performed_by' => auth()->id(),
                'notes' => 'Manual stock adjustment',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->route('stock-manager.product-stock')->with('success', 'Stock updated successfully.');
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
                'has_logo' => 'required|in:yes,no',
                'logo_color' => 'required_if:has_logo,yes|nullable|in:yellow,black',
                'has_box' => 'required_if:has_logo,no|nullable|in:yes,no',
                'box_color' => 'required_if:has_box,yes|nullable|in:black,white',
            ]);

            // Build category info
            $categoryInfo = null;
            if ($validated['has_logo'] === 'yes' && !empty($validated['logo_color'])) {
                $categoryInfo = 'Logo: ' . ucfirst($validated['logo_color']);
            } elseif ($validated['has_logo'] === 'no') {
                if (($validated['has_box'] ?? '') === 'yes' && !empty($validated['box_color'])) {
                    $categoryInfo = 'No Logo, Box: ' . ucfirst($validated['box_color']);
                } else {
                    $categoryInfo = 'No Logo, No Box';
                }
            }

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

            // Record movement with category info
            $movementReason = ($validated['reason'] ?? 'Stock in');
            if ($categoryInfo) {
                $movementReason .= ' [' . $categoryInfo . ']';
            }

            $this->supabase->insert('bottle_stock_movements', [
                'branch_id' => $branchId,
                'volume' => $validated['volume'],
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'reason' => $movementReason,
                'has_logo' => $validated['has_logo'],
                'logo_color' => $validated['logo_color'] ?? null,
                'has_box' => $validated['has_box'] ?? null,
                'box_color' => $validated['box_color'] ?? null,
                'performed_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('stock-manager.bottle-stock')->with('success', 'Bottle stock added successfully.');
        }

        $volumes = ['6ml', '12ml', '30ml', '50ml', '100ml'];
        return view('stock-manager.bottle-stock-in', ['volumes' => $volumes]);
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
            'select' => '*',
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

        $movements = $this->loadMovementsWithUser('bottle_stock_movements', $branchId, 50, $params);

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
                'bottle_volume' => 'required|integer|in:500,1000',
                'reason' => 'nullable|string|max:255',
            ]);

            $bottleVolume = (int) $validated['bottle_volume'];
            $volumeLabel = $bottleVolume === 500 ? '500ml' : '1000ml';

            $existing = $this->supabase->findOne('oil_fragrance_stock', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
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
                    'name' => $validated['name'],
                    'quantity' => $validated['quantity'],
                    'volume' => $bottleVolume,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'volume' => $bottleVolume,
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'reason' => ($validated['reason'] ?? 'Stock in') . " [{$volumeLabel}]",
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
            'select' => '*',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->type) {
            $params['type'] = "eq.{$request->type}";
        }

        $movements = $this->loadMovementsWithUser('oil_fragrance_movements', $branchId, 50, $params);

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
