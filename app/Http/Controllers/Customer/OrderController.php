<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;

    private ProductVarietyStockService $varieties;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
        $this->varieties = new ProductVarietyStockService($supabase);
    }

    public function create(Request $request)
    {
        $branchId = $request->branch_id;

        if (!$branchId) {
            return redirect()->route('customer.products.index')->with('error', 'Please select a branch first.');
        }

        $branch = $this->supabase->find('branches', $branchId);

        if (!$branch) {
            abort(404);
        }

        $branch = (object) $branch;

        // Fetch products with stock at this branch (unit cost excluded — internal only)
        $rawStock = $this->supabase->query('branch_stock', [
            'select' => 'id,branch_id,product_id,quantity,selling_price,product:products(id,name,description,brand,category,sex_category,images:product_images(*))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
        ]);

        $products = collect($rawStock)->map(function ($item) {                return (object) [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'selling_price' => $item['selling_price'],
                'product' => (object) array_merge($item['product'] ?? [], [
                    // Cast each image to an object so views can use ->image_url.
                    'images' => collect($item['product']['images'] ?? [])->map(fn ($img) => (object) $img),
                ]),
            ];
        });

        // Oil fragrance products are bottled in several sizes, each with its
        // own price, so the form has to offer the options in stock at this
        // branch and the customer has to pick one.
        $productVarieties = $this->varieties->bucketsForProducts(
            (int) $branchId,
            $products->map(fn ($p) => (int) $p->product_id)->all()
        );

        // Coming from the details page the choice is already made, so keep it
        // selected instead of making the customer choose it a second time.
        $preselect = [
            'product_id' => (int) ($request->product_id ?? 0),
            'volume' => (int) ($request->volume ?? 0),
            'variant' => (string) ($request->variant ?? ''),
        ];

        return view('customer.orders.create', compact('branch', 'products', 'productVarieties', 'preselect'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'delivery_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.volume' => 'nullable|integer',
            'items.*.variant' => 'nullable|string|max:32',
        ]);

        try {
            // 1. Create or find customer (1 HTTP call)
            $existingCustomer = $this->supabase->findOne('customers', [
                'phone' => $validated['customer_phone'],
            ]);

            if ($existingCustomer) {
                $customerId = $existingCustomer['id'];
            } else {
                $customer = $this->supabase->insert('customers', [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'] ?? null,
                    'whatsapp' => $validated['customer_phone'],
                ]);
                $customerId = $customer['id'];
            }

            // 2. Fetch ALL stock for this branch in ONE query (instead of N)
            $allStock = collect($this->supabase->query('branch_stock', [
                'branch_id' => "eq.{$validated['branch_id']}",
                'select' => 'product_id,quantity,selling_price,product:products(name)',
            ]));
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // Product categories and per-variety stock/prices for the ordered
            // products, so an oil fragrance line can be priced and checked
            // against the exact bottling the customer picked.
            $itemProductIds = array_values(array_unique(array_map(fn ($i) => (int) ($i['product_id'] ?? 0), $validated['items'])));
            $categoryMap = [];
            if ($itemProductIds) {
                foreach ($this->supabase->query('products', [
                    'select' => 'id,category',
                    'id' => 'in.(' . implode(',', $itemProductIds) . ')',
                ]) as $p) {
                    $categoryMap[(int) $p['id']] = $p['category'] ?? '';
                }
            }
            $varietyStock = $this->varieties->stockForProducts((int) $validated['branch_id'], $itemProductIds, fresh: true);
            $varietyPrices = $this->varieties->pricesForProducts((int) $validated['branch_id'], $itemProductIds);

            // 3. Validate stock and calculate totals (no HTTP calls)
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $stock = $stockMap[$item['product_id']] ?? null;

                if (!$stock || ($stock['quantity'] ?? 0) < $item['quantity']) {
                    return $this->orderProblem($request, 'items', 'Some products are no longer available in the requested quantity.');
                }

                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $volume = (int) ($item['volume'] ?? 0);
                $variant = trim((string) ($item['variant'] ?? ''));

                // A product bottled in several sizes has to say which one is
                // being ordered, and enough of that exact bottling must exist.
                // Products stocked in before variety tracking have no buckets
                // and are ordered as plain product lines.
                if (($categoryMap[$productId] ?? '') === 'Oil Fragrance' && !empty($varietyStock[$productId])) {
                    if ($volume <= 0 || $variant === '') {
                        return $this->orderProblem(
                            $request,
                            'items',
                            'Please choose the size and packaging for ' . ($stock['product']['name'] ?? 'this product') . '.'
                        );
                    }
                    $available = (int) ($varietyStock[$productId][$volume][$variant] ?? 0);
                    if ($available < $quantity) {
                        return $this->orderProblem(
                            $request,
                            'items',
                            'Only ' . $available . ' left of ' . $this->varieties->pickLabel($volume, $variant) . ' for ' . ($stock['product']['name'] ?? 'this product') . '.'
                        );
                    }
                } else {
                    $volume = 0;
                    $variant = '';
                }

                // The picked bottling carries its own price (50ml != 30ml);
                // the product's branch price is only the fallback.
                $varietyPrice = ($volume > 0 && $variant !== '')
                    ? (float) ($varietyPrices[$productId][$volume][$variant] ?? 0)
                    : 0.0;
                $unitPrice = $varietyPrice > 0 ? $varietyPrice : (float) ($stock['selling_price'] ?? 0);
                $lineTotal = $unitPrice * $quantity;
                $total += $lineTotal;

                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'volume' => $volume,
                    'variant' => $variant,
                ];
            }

            // 4. Generate order number (timestamp-based, no query)
            $orderNumber = 'ORD-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            // 5. Create order (1 HTTP call)
            $order = $this->supabase->insert('orders', [
                'order_number' => $orderNumber,
                'branch_id' => $validated['branch_id'],
                'customer_id' => $customerId,
                'status' => 'pending',
                'total' => $total,
                'delivery_notes' => $validated['delivery_notes'] ?? null,
                'payment_status' => 'unpaid',
                'payment_method' => 'cash',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            // 6. Batch insert order items (1 HTTP call instead of N)
            // The chosen bottling is written only when the columns exist —
            // until database/supabase_order_item_varieties.sql has been run
            // PostgREST rejects the insert outright.
            $hasVarietyColumns = $this->supabase->tableHasColumn('order_items', 'volume')
                && $this->supabase->tableHasColumn('order_items', 'variant');
            $itemsToInsert = array_map(function ($oi) use ($order, $hasVarietyColumns) {
                $row = [
                    'order_id' => $order['id'],
                    'product_id' => $oi['product_id'],
                    'quantity' => $oi['quantity'],
                    'unit_price' => $oi['unit_price'],
                    'total' => $oi['total'],
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];
                if ($hasVarietyColumns) {
                    $row['volume'] = $oi['volume'] > 0 ? $oi['volume'] : null;
                    $row['variant'] = $oi['variant'] !== '' ? $oi['variant'] : null;
                }
                return $row;
            }, $orderItems);
            $this->supabase->insertMany('order_items', $itemsToInsert);

            // 7. Confirm the order — payment is settled at the branch. The
            // mobile app (Accept: application/json) gets the same confirmation
            // as JSON so it can show the order number it just created.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order placed successfully. Payment will be settled at the branch.',
                    'order' => [
                        'id' => $order['id'] ?? null,
                        'order_number' => $order['order_number'] ?? $orderNumber,
                        'total' => $order['total'] ?? $total,
                        'status' => $order['status'] ?? 'pending',
                    ],
                ], 201);
            }

            return view('customer.orders.success', ['order' => (object) $order]);

        } catch (\Exception $e) {
            return $this->orderProblem($request, 'error', 'Order failed: ' . $e->getMessage());
        }
    }

    /**
     * A rejected order. The website is redirected back with the errors
     * flashed; the mobile app gets a 422 it can render field by field.
     */
    private function orderProblem(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [$field => [$message]],
            ], 422);
        }

        return back()->withErrors([$field => $message])->withInput();
    }

    public function track(Request $request)
    {
        return view('customer.orders.track');
    }

    public function trackByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        // Find customer by phone
        $customer = $this->supabase->findOne('customers', [
            'phone' => $validated['phone'],
        ]);

        if (!$customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'orders' => [],
                    'message' => 'No orders found for this phone number.',
                ]);
            }

            return back()->with('error', 'No orders found for this phone number.');
        }

        // Fetch orders for this customer. Oldest first so the list reads
        // as a history rather than jumping around between visits.
        $rawOrders = $this->supabase->query('orders', [
            'select' => '*, branch:branches(name), items:order_items(*, product:products(name)), notes:order_notes(*)',
            'customer_id' => "eq.{$customer['id']}",
            'order' => 'created_at.asc',
            'limit' => 10,
        ]);

        // Every step the order has actually reached, oldest first. Legacy
        // rows predate served_at, so completed_at is used as the fallback.
        $steps = [
            'created_at' => 'Order placed',
            'assigned_at' => 'Order picked',
            'served_at' => 'Order served',
        ];

        $orders = collect($rawOrders)->map(function ($o) use ($steps) {
            $o['items'] = collect($o['items'] ?? [])->map(function ($item) {
                $volume = (int) ($item['volume'] ?? 0);
                $variant = (string) ($item['variant'] ?? '');

                return (object) [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    // The size and packaging that was ordered, so the customer
                    // can see the same thing the branch is packing.
                    'variety_label' => $volume > 0 && $variant !== '' ? $this->varieties->pickLabel($volume, $variant) : '',
                    'product' => (object) ($item['product'] ?? []),
                ];
            });
            $o['notes'] = collect($o['notes'] ?? [])
                ->sortBy('created_at')
                ->map(fn($n) => (object) $n)
                ->values();
            $o['branch'] = (object) ($o['branch'] ?? []);

            $o['timeline'] = collect([
                ['at' => $o['created_at'] ?? null, 'label' => $steps['created_at']],
                ['at' => $o['assigned_at'] ?? null, 'label' => $steps['assigned_at']],
                ['at' => $o['served_at'] ?? ($o['completed_at'] ?? null), 'label' => $steps['served_at']],
            ])
                ->filter(fn($s) => !empty($s['at']))
                ->sortBy('at')
                ->values();

            return (object) $o;
        });

        if ($request->expectsJson()) {
            return response()->json(['orders' => $orders]);
        }

        return view('customer.orders.tracked', compact('orders'));
    }
}
