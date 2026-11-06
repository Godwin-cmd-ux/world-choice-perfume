<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerOrderCart;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;

    private ProductVarietyStockService $varieties;

    private CustomerOrderCart $cart;

    public function __construct(SupabaseService $supabase, CustomerOrderCart $cart)
    {
        $this->supabase = $supabase;
        $this->varieties = new ProductVarietyStockService($supabase);
        $this->cart = $cart;
    }

    public function create(Request $request)
    {
        $branchId = (int) $request->branch_id;

        if (!$branchId) {
            return redirect()->route('customer.products.index')->with('error', 'Please select a branch first.');
        }

        $branch = $this->supabase->find('branches', $branchId);

        if (!$branch) {
            abort(404);
        }

        $branch = (object) $branch;

        // A cart is packed from one branch — an order has one branch_id. Opening
        // a product at another branch starts a fresh cart instead of mixing two
        // branches into an order that could never be packed.
        if ($this->cart->items() !== [] && $this->cart->branchId() !== $branchId) {
            $this->cart->clear();
            session()->flash('error', 'Your cart was for another branch, so it was emptied. An order can only come from one branch.');
        }

        $cartItems = $this->cart->items();

        // The page is about the ONE perfume the customer tapped in the shop, plus
        // whatever was already added on an earlier "choose more products" trip —
        // never the whole catalogue.
        $line = null;
        $productId = (int) ($request->product_id ?? 0);

        if ($productId > 0) {
            $offer = $this->offer($branchId, $productId);

            if ($offer === null || $offer['available'] < 1) {
                return redirect()
                    ->route('customer.products.index', ['branch_id' => $branchId])
                    ->with('error', 'That product is no longer available at this branch.');
            }

            // Coming from the details page the size is already chosen; without
            // one, the first size in stock is offered.
            $volume = $offer['is_variety'] ? (int) $offer['buckets'][0]['volume'] : 0;
            $requestedVolume = (int) ($request->volume ?? 0);
            if ($offer['is_variety'] && $requestedVolume > 0 && $this->availableFor($offer, $requestedVolume) > 0) {
                $volume = $requestedVolume;
            }

            // Already in the cart? The card edits that line, so it is shown once
            // (with its quantity) instead of twice on the same page.
            $quantity = 1;
            foreach ($cartItems as $index => $item) {
                if ((int) ($item['product_id'] ?? 0) === $productId
                    && (int) ($item['volume'] ?? 0) === $volume) {
                    $quantity = (int) ($item['quantity'] ?? 1);
                    unset($cartItems[$index]);
                    break;
                }
            }
            $cartItems = array_values($cartItems);

            $available = $this->availableFor($offer, $volume);

            $line = [
                'product_id' => $offer['product_id'],
                'name' => $offer['name'],
                'brand' => $offer['brand'],
                'image' => $offer['image'],
                'is_variety' => $offer['is_variety'],
                'buckets' => $offer['buckets'],
                'volume' => $volume,
                'variety_label' => $volume > 0 ? $this->varieties->volumeLabel($volume) : '',
                'unit_price' => $this->unitPriceFor($offer, $volume),
                'available' => $available,
                'quantity' => max(1, min($quantity, $available)),
            ];
        }

        // The card is posted after the cart lines that are already in the form.
        $lineIndex = count($cartItems);

        $cartSubtotal = 0.0;
        foreach ($cartItems as $item) {
            $cartSubtotal += (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
        }

        return view('customer.orders.create', [
            'branch' => $branch,
            'cartItems' => $cartItems,
            'contact' => $this->cart->contact(),
            'line' => $line,
            'lineIndex' => $lineIndex,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    /**
     * "Choose more products": park this selection in the cart, keep the
     * customer details already typed, and go back to the shop.
     */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required',
            'current_index' => 'nullable|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.volume' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email',
            'delivery_notes' => 'nullable|string',
        ]);

        $branchId = (int) $validated['branch_id'];

        $branch = $this->supabase->find('branches', $branchId);

        if (!$branch) {
            abort(404);
        }

        // The form also carries the lines already in the cart; only the product
        // card the customer is looking at is being added here.
        $index = (int) ($validated['current_index'] ?? 0);
        $item = $validated['items'][$index] ?? null;

        if ($item === null) {
            return back()->withInput()->withErrors(['items' => 'Choose a product before adding it to your cart.']);
        }

        $offer = $this->offer($branchId, (int) $item['product_id']);

        if ($offer === null) {
            return back()->withInput()->withErrors(['items' => 'That product is no longer available at this branch.']);
        }

        // A size that sold out since the page loaded falls back to one that is
        // still on the shelf; only a product with nothing left is refused.
        $volume = (int) ($item['volume'] ?? 0);
        if ($offer['is_variety'] && $this->availableFor($offer, $volume) < 1) {
            $volume = (int) $offer['buckets'][0]['volume'];
        }

        $available = $this->availableFor($offer, $volume);

        if ($available < 1) {
            return back()->withInput()->withErrors(['items' => $offer['name'] . ' is out of stock at this branch.']);
        }

        $this->cart->rememberContact([
            'name' => $validated['customer_name'] ?? '',
            'phone' => $validated['customer_phone'] ?? '',
            'email' => $validated['customer_email'] ?? '',
            'notes' => $validated['delivery_notes'] ?? '',
        ]);

        $this->cart->put([
            'key' => CustomerOrderCart::keyFor((int) $offer['product_id'], $volume),
            'product_id' => (int) $offer['product_id'],
            'name' => $offer['name'],
            'brand' => $offer['brand'],
            'image' => $offer['image'],
            'branch_id' => $branchId,
            'branch_name' => (string) ($branch['name'] ?? ''),
            'quantity' => max(1, min((int) $item['quantity'], $available)),
            'unit_price' => $this->unitPriceFor($offer, $volume),
            'volume' => $volume,
            'variety_label' => $volume > 0 ? $this->varieties->volumeLabel($volume) : '',
            'available' => $available,
        ]);

        return redirect()
            ->route('customer.products.index', ['branch_id' => $branchId])
            ->with('success', $offer['name'] . ' is in your cart. Pick another perfume, or check out when you are ready.');
    }

    /** Drop one line from the cart without leaving the order page. */
    public function removeFromCart(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:64',
        ]);

        $this->cart->forget($validated['key']);

        return back()->with('success', 'Item removed from your cart.');
    }

    /**
     * What this branch can sell of one product: its sizes, stock and price.
     * Packaging never reaches the customer — the branch packs whichever of its
     * buckets fits the size that was ordered.
     */
    private function offer(int $branchId, int $productId): ?array
    {
        $rows = $this->supabase->query('branch_stock', [
            'select' => 'id,branch_id,product_id,quantity,selling_price,product:products(id,name,brand,category,images:product_images(*))',
            'branch_id' => "eq.{$branchId}",
            'product_id' => "eq.{$productId}",
            'limit' => 1,
        ]);

        $stock = $rows[0] ?? null;

        if (!$stock) {
            return null;
        }

        $product = $stock['product'] ?? [];
        $buckets = $this->varieties->volumeBucketsForProducts($branchId, [$productId])[(string) $productId] ?? [];
        $isVariety = ($product['category'] ?? '') === 'Oil Fragrance' && $buckets !== [];

        $image = null;
        foreach (($product['images'] ?? []) as $img) {
            if (!empty($img['image_url'])) {
                $image = $img['image_url'];
                break;
            }
        }

        return [
            'product_id' => $productId,
            'name' => (string) ($product['name'] ?? 'Product'),
            'brand' => $product['brand'] ?? null,
            'image' => $image,
            'is_variety' => $isVariety,
            'buckets' => $buckets,
            'base_price' => (float) ($stock['selling_price'] ?? 0),
            // Without size tracking the branch's shelf count is the whole offer.
            'available' => $isVariety ? (int) $buckets[0]['available'] : (int) ($stock['quantity'] ?? 0),
        ];
    }

    /** Units of the chosen size the branch holds. */
    private function availableFor(array $offer, int $volume): int
    {
        if (!$offer['is_variety']) {
            return (int) $offer['available'];
        }

        foreach ($offer['buckets'] as $bucket) {
            if ((int) $bucket['volume'] === $volume) {
                return (int) $bucket['available'];
            }
        }

        return 0;
    }

    /** What one unit of the chosen size costs (0 means "use the branch price"). */
    private function unitPriceFor(array $offer, int $volume): float
    {
        if ($offer['is_variety']) {
            foreach ($offer['buckets'] as $bucket) {
                if ((int) $bucket['volume'] === $volume) {
                    return (float) ($bucket['price'] ?? 0) > 0
                        ? (float) $bucket['price']
                        : (float) $offer['base_price'];
                }
            }
        }

        return (float) $offer['base_price'];
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
            // The customer picks a size; the packaging behind it is optional
            // and resolved server-side.
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

                // Price of the chosen size; 0 means "no size price", which
                // falls back to the product's branch price below.
                $varietyPrice = 0.0;

                // A product bottled in several sizes has to say which one is
                // being ordered, and enough of that size must exist. The
                // packaging behind it is the branch's to choose, so the bucket
                // to pack from is resolved here — a variant posted by an older
                // client or a saved link is honoured when it fits the size.
                // Products stocked in before variety tracking have no buckets
                // and are ordered as plain product lines.
                if (($categoryMap[$productId] ?? '') === 'Oil Fragrance' && !empty($varietyStock[$productId])) {
                    if ($volume <= 0) {
                        return $this->orderProblem(
                            $request,
                            'items',
                            'Please choose the size for ' . ($stock['product']['name'] ?? 'this product') . '.'
                        );
                    }

                    $pick = $this->varieties->resolveVariantForVolume((int) $validated['branch_id'], $productId, $volume);
                    if ($pick === null) {
                        return $this->orderProblem(
                            $request,
                            'items',
                            $this->varieties->volumeLabel($volume) . ' for ' . ($stock['product']['name'] ?? 'this product') . ' is no longer available.'
                        );
                    }

                    $varietyPrice = (float) $pick['price'];
                    $available = (int) $pick['available'];

                    if ($variant !== '' && isset($varietyStock[$productId][$volume][$variant])) {
                        $available = (int) $varietyStock[$productId][$volume][$variant];
                        $varietyPrice = (float) ($varietyPrices[$productId][$volume][$variant] ?? 0) ?: $varietyPrice;
                    } else {
                        $variant = (string) $pick['variant'];
                    }

                    if ($available < $quantity) {
                        return $this->orderProblem(
                            $request,
                            'items',
                            'Only ' . $available . ' left of ' . $this->varieties->volumeLabel($volume) . ' for ' . ($stock['product']['name'] ?? 'this product') . '.'
                        );
                    }
                } else {
                    $volume = 0;
                    $variant = '';
                }

                // The picked size carries its own price (50ml != 30ml);
                // the product's branch price is only the fallback.
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

            // The order now holds every line the cart carried, so the cart is
            // emptied. API clients post without a session, hence the guard.
            if ($request->hasSession()) {
                $this->cart->clear();
            }

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

        // The staff member behind each update is shown with it. One lookup
        // covers every note on every order instead of a query per order.
        $authorIds = [];
        foreach ($rawOrders as $rawOrder) {
            foreach ($rawOrder['notes'] ?? [] as $rawNote) {
                if (!empty($rawNote['created_by'])) {
                    $authorIds[(string) $rawNote['created_by']] = true;
                }
            }
        }

        $authors = [];
        if ($authorIds !== []) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', array_keys($authorIds)) . ')',
            ]) as $author) {
                $authors[(string) $author['id']] = $author['name'] ?? null;
            }
        }

        $orders = collect($rawOrders)->map(function ($o) use ($steps, $authors) {
            $o['items'] = collect($o['items'] ?? [])->map(function ($item) {
                $volume = (int) ($item['volume'] ?? 0);
                $variant = (string) ($item['variant'] ?? '');

                return (object) [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    // The size that was ordered — the customer chose a volume,
                    // so that is what they are shown back, not the branch's
                    // packaging choice.
                    'variety_label' => $volume > 0 ? $this->varieties->volumeLabel($volume) : '',
                    'product' => (object) ($item['product'] ?? []),
                ];
            });
            $notes = collect($o['notes'] ?? [])
                ->sortBy('created_at')
                ->values();

            $o['notes'] = $notes->map(fn($n) => (object) $n)->values();

            // The app's "Order Updates" feed: the same notes the website
            // tracking page renders, with the staff member's name resolved.
            $o['updates'] = $notes
                ->map(fn($n) => (object) [
                    'note' => (string) ($n['note'] ?? ''),
                    'author' => $authors[(string) ($n['created_by'] ?? '')] ?? null,
                    'created_at' => $n['created_at'] ?? null,
                ])
                ->filter(fn($update) => $update->note !== '')
                ->values();

            $latestUpdate = $o['updates']->last();
            $o['last_update_at'] = $latestUpdate?->created_at;

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
