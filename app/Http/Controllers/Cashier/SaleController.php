<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $supabaseUserId = $user->supabase_id ?? $user->id;

        $params = [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name,phone)',
            'cashier_id' => "eq.{$supabaseUserId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        $sales = $this->supabase->query('sales', $params);

        // Apply date filters in PHP
        if ($request->date_from) {
            $from = $request->date_from;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) <= $to);
        }

        $sales = collect(array_values($sales))->map(function ($s) {
            if (isset($s['customer']) && is_array($s['customer'])) $s['customer'] = (object) $s['customer'];
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        return view('cashier.sales.index', compact('sales'));
    }

    public function create()
    {
        $branchId = auth()->user()->branch_id;

        $rawStock = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        $products = collect($rawStock)->map(function ($item) {
            return (object) [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'selling_price' => $item['selling_price'],
                'buying_cost' => $item['buying_cost'],
                'product' => (object) array_merge($item['product'] ?? [], [
                    'images' => collect($item['product']['images'] ?? []),
                ]),
            ];
        });

        return view('cashier.sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_whatsapp' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $branchId = auth()->user()->branch_id;

        try {
            // 1. Create or find customer in Supabase
            $customerId = null;
            if ($validated['customer_phone'] || $validated['customer_name']) {
                $existingCustomer = null;
                if (!empty($validated['customer_phone'])) {
                    $existingCustomer = $this->supabase->findOne('customers', [
                        'phone' => $validated['customer_phone'],
                    ]);
                }

                if ($existingCustomer) {
                    $customerId = $existingCustomer['id'];
                } else {
                    $customer = $this->supabase->insert('customers', [
                        'name' => $validated['customer_name'] ?? null,
                        'phone' => $validated['customer_phone'] ?? null,
                        'email' => $validated['customer_email'] ?? null,
                        'whatsapp' => $validated['customer_whatsapp'] ?? $validated['customer_phone'] ?? null,
                    ]);
                    $customerId = $customer['id'] ?? null;
                }
            }

            // 2. Generate sale number (use timestamp for uniqueness)
            $saleNumber = 'SALE-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            // 3. Fetch ALL stock for this branch in ONE query (instead of N queries)
            $allStock = collect($this->supabase->query('branch_stock', [
                'branch_id' => "eq.{$branchId}",
                'select' => 'id,product_id,quantity,selling_price,buying_cost',
            ]));

            // Build a lookup map by product_id
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 4. Validate stock and calculate totals (no HTTP calls)
            $subtotal = 0;
            $saleItems = [];
            $stockUpdates = [];
            $stockMovements = [];
            $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

            foreach ($validated['items'] as $item) {
                $stock = $stockMap[$item['product_id']] ?? null;

                if (!$stock || ($stock['quantity'] ?? 0) < $item['quantity']) {
                    return back()->withErrors([
                        "items.{$item['product_id']}" => "Insufficient stock. Available: " . ($stock['quantity'] ?? 0),
                    ])->withInput();
                }

                $lineTotal = ($stock['selling_price'] ?? 0) * $item['quantity'];
                $subtotal += $lineTotal;
                $newQty = ($stock['quantity'] ?? 0) - $item['quantity'];

                $saleItems[] = [
                    'sale_id' => null, // will be set after sale creation
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $stock['selling_price'],
                    'unit_cost' => $stock['buying_cost'],
                    'total' => $lineTotal,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];

                $stockUpdates[] = [
                    'id' => $stock['id'],
                    'newQty' => $newQty,
                ];

                $stockMovements[] = [
                    'branch_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'unit_cost' => $stock['buying_cost'],
                    'unit_price' => $stock['selling_price'],
                    'reference_type' => 'sale',
                    'reference_id' => null, // will be set after sale creation
                    'performed_by' => $supabaseUserId,
                    'notes' => "Sale {$saleNumber}",
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];
            }

            // 5. Create sale (1 HTTP call)
            $sale = $this->supabase->insert('sales', [
                'sale_number' => $saleNumber,
                'branch_id' => $branchId,
                'cashier_id' => $supabaseUserId,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'payment_status' => 'paid',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            if (!$sale) {
                return back()->withErrors(['error' => 'Failed to create sale. Please try again.'])->withInput();
            }

            // 6. Batch insert all sale items (1 HTTP call instead of N)
            foreach ($saleItems as &$si) {
                $si['sale_id'] = $sale['id'];
            }
            $this->supabase->insertMany('sale_items', $saleItems);

            // 7. Batch update stock quantities (N HTTP calls, but faster with cache)
            foreach ($stockUpdates as $su) {
                $this->supabase->update('branch_stock', [
                    'quantity' => $su['newQty'],
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $su['id']]);
            }

            // 8. Batch insert stock movements (1 HTTP call instead of N)
            foreach ($stockMovements as &$sm) {
                $sm['reference_id'] = $sale['id'];
            }
            $this->supabase->insertMany('stock_movements', $stockMovements);

            // 9. Audit log (1 HTTP call)
            $this->supabase->insert('audit_logs', [
                'user_id' => $supabaseUserId,
                'action' => 'sale_created',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('cashier.sales.show', $sale['id'])
                ->with('success', "Sale {$saleNumber} completed successfully!");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Sale failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function show($saleId)
    {
        $user = auth()->user();
        $supabaseUserId = $user->supabase_id ?? $user->id;

        $sale = $this->supabase->find('sales', $saleId, '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(*), cashier:users(id,name), branch:branches(id,name,address)');
        if (!$sale || $sale['cashier_id'] != $supabaseUserId) {
            abort(403);
        }

        if (isset($sale['customer']) && is_array($sale['customer'])) $sale['customer'] = (object) $sale['customer'];
        if (isset($sale['cashier']) && is_array($sale['cashier'])) $sale['cashier'] = (object) $sale['cashier'];
        if (isset($sale['branch']) && is_array($sale['branch'])) $sale['branch'] = (object) $sale['branch'];
        if (isset($sale['items'])) {
            $sale['items'] = collect($sale['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('cashier.sales.show', ['sale' => (object) $sale]);
    }
}
