<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\BottleStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    private SupabaseService $supabase;
    private BottleStockService $bottles;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
    }

    public function index(Request $request)
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $params = [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name,phone)',
            'cashier_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ];

        $sales = $this->supabase->query('sales', $params);

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

        $totalSales = $sales->sum('total');

        return view('seller.sales.index', ['sales' => $sales, 'totalSales' => $totalSales]);
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
                'product' => (object) array_merge($item['product'] ?? [], [
                    'images' => collect($item['product']['images'] ?? []),
                ]),
            ];
        });

        $bottleStock = $this->bottles->stockMap($branchId);
        $bottleVariants = $this->bottles->variantStock($branchId);

        return view('seller.sales.create', compact('products', 'bottleStock', 'bottleVariants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payment_mode' => 'required|in:single,multi',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,bank_transfer,mobile_payment',
            'payments.*.amount' => 'required_if:payment_mode,multi|nullable|numeric|min:0',
            'items' => 'required_without:empty_bottles|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_price' => 'nullable|numeric|min:0',
            'empty_bottles' => 'nullable|array',
            'empty_bottles.*.volume' => 'nullable|integer|in:6,12,30,50,100',
            'empty_bottles.*.quantity' => 'nullable|integer|min:1',
            'empty_bottles.*.price' => 'nullable|numeric|min:0',
            'empty_bottles.*.variant' => 'nullable|string|max:32',
            'sale_type' => 'required|in:retail,wholesale',
        ]);

        // Normalize product rows and never sell empty bottles on retail sales
        $validated['items'] = array_values(array_filter($validated['items'] ?? [], fn ($i) => !empty($i['product_id'] ?? null)));
        if (($validated['sale_type'] ?? 'retail') === 'retail') {
            $validated['empty_bottles'] = [];
        }

        $branchId = auth()->user()->branch_id;
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        try {
            // 1. Determine customer
            $customerId = null;
            if (!empty($validated['customer_id'])) {
                $customerId = $validated['customer_id'];
            } elseif (!empty($validated['customer_name']) || !empty($validated['customer_phone'])) {
                $typedName = trim((string) ($validated['customer_name'] ?? ''));
                $typedPhone = trim((string) ($validated['customer_phone'] ?? ''));
                $existingCustomer = null;
                if ($typedPhone !== '') {
                    $existingCustomer = $this->supabase->findOne('customers', [
                        'phone' => $typedPhone,
                    ]);
                }
                $nameAgrees = $typedName === ''
                    || (isset($existingCustomer['name']) && mb_strtolower(trim((string) $existingCustomer['name'])) === mb_strtolower($typedName));
                if ($existingCustomer && $nameAgrees) {
                    $customerId = $existingCustomer['id'];
                } else {
                    $customer = $this->supabase->insert('customers', [
                        'name' => $typedName !== '' ? $typedName : ($existingCustomer['name'] ?? null),
                        'phone' => $typedPhone !== '' ? $typedPhone : ($existingCustomer['phone'] ?? null),
                        'whatsapp' => $typedPhone !== '' ? $typedPhone : ($existingCustomer['whatsapp'] ?? null),
                    ]);
                    $customerId = $customer['id'] ?? null;
                }
            }

            $saleNumber = 'SALE-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            // 2. Fetch ALL stock for this branch in ONE query
            $allStock = collect($this->supabase->query('branch_stock', [
                'branch_id' => "eq.{$branchId}",
                'select' => 'id,product_id,quantity,selling_price,buying_cost',
            ]));

            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 3. Validate stock and calculate totals
            $subtotal = 0;
            $saleItems = [];
            $stockUpdates = [];
            $stockMovements = [];
            $priceOverridden = false;

            foreach ($validated['items'] as $item) {
                $stock = $stockMap[$item['product_id']] ?? null;

                if (!$stock || ($stock['quantity'] ?? 0) < $item['quantity']) {
                    return back()->withErrors([
                        "items.{$item['product_id']}" => "Insufficient stock. Available: " . ($stock['quantity'] ?? 0),
                    ])->withInput();
                }

                $unitPrice = ($validated['sale_type'] === 'wholesale' && !empty($item['custom_price']))
                    ? $item['custom_price']
                    : ($stock['selling_price'] ?? 0);
                if ($validated['sale_type'] === 'wholesale' && !empty($item['custom_price'])) {
                    $priceOverridden = true;
                }
                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;
                $newQty = ($stock['quantity'] ?? 0) - $item['quantity'];

                $saleItems[] = [
                    'sale_id' => null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'unit_cost' => (float) ($stock['buying_cost'] ?? 0),
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
                    'unit_price' => $stock['selling_price'],
                    'reference_type' => 'sale',
                    'reference_id' => null,
                    'performed_by' => $supabaseUserId,
                    'notes' => "Sale {$saleNumber}",
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];
            }

            // 4. Empty bottle lines
            $rawBottles = $validated['empty_bottles'] ?? [];
            $bottleItems = array_values(array_filter($rawBottles, fn($b) => !empty($b['volume'] ?? null)));

            foreach ($bottleItems as $btl) {
                if (empty($btl['quantity']) || !isset($btl['price']) || $btl['price'] === '' || $btl['price'] === null) {
                    return back()->withErrors(['empty_bottles' => 'Each empty bottle line requires a quantity and price.'])->withInput();
                }
            }

            $bottleTotal = 0;
            $bottleDeductions = [];

            if (!empty($bottleItems)) {
                $bottleStockRows = $this->supabase->queryFresh('bottle_stock', [
                    'branch_id' => "eq.{$branchId}",
                ]);
                $bottleAvailable = [];
                foreach ($bottleStockRows as $bs) {
                    $bv = $this->bottles->parseVolume((string) ($bs['volume'] ?? ''));
                    if ($bv !== null) {
                        $vk = (string) ($bs['variant'] ?? \App\Services\BottleStockService::VARIANT_PLAIN);
                        $bottleAvailable[$bv][$vk] = (int) ($bs['quantity'] ?? 0);
                    }
                }

                foreach ($bottleItems as $btl) {
                    $volume = (int) $btl['volume'];
                    $bQty = (int) $btl['quantity'];
                    $bPrice = (float) $btl['price'];

                    $variant = \App\Services\BottleStockService::VARIANT_PLAIN;
                    if ($this->bottles->volumeHasDetails($volume)) {
                        $variant = trim((string) ($btl['variant'] ?? ''));
                        if (!in_array($variant, $this->bottles->variantBuckets($volume), true)) {
                            return back()->withErrors([
                                'empty_bottles' => "Select the box/logo/color details (variant) for {$volume}ml empty bottles.",
                            ])->withInput();
                        }
                    }

                    $available = (int) ($bottleAvailable[$volume][$variant] ?? 0);

                    if ($available < $bQty) {
                        $variantText = $this->bottles->volumeHasDetails($volume)
                            ? ' (' . $this->bottles->variantLabel($variant, $volume) . ')'
                            : '';
                        return back()->withErrors([
                            'empty_bottles' => "Insufficient bottle stock for {$volume}ml{$variantText}. Available: {$available}.",
                        ])->withInput();
                    }

                    $bottleProduct = $this->bottles->findOrCreateEmptyBottleProduct($volume);

                    if (!$bottleProduct || empty($bottleProduct['id'])) {
                        return back()->withErrors(['empty_bottles' => 'Could not register the empty bottle product for the receipt.'])->withInput();
                    }

                    $lineTotal = $bPrice * $bQty;
                    $bottleTotal += $lineTotal;

                    $saleItems[] = [
                        'sale_id' => null,
                        'product_id' => $bottleProduct['id'],
                        'quantity' => $bQty,
                        'unit_price' => $bPrice,
                        'unit_cost' => 0,
                        'total' => $lineTotal,
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];

                    $bottleDeductions[] = [
                        'volume' => $volume,
                        'quantity' => $bQty,
                        'reason' => "Sold as empty bottle - Sale {$saleNumber}",
                        'variant' => $variant,
                    ];

                    $bottleAvailable[$volume][$variant] -= $bQty;
                }

                $subtotal += $bottleTotal;
            }

            if (empty($saleItems)) {
                return back()->withErrors(['items' => 'Add at least one product or empty bottle to the sale.'])->withInput();
            }

            // 5. Build payment summary
            $payments = $validated['payments'] ?? [];
            if ($validated['payment_mode'] === 'multi') {
                $paymentTotal = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));
                if (abs($paymentTotal - $subtotal) > 0.01) {
                    return back()->withErrors([
                        'payments' => 'Payment breakdown (' . number_format($paymentTotal) . ') must equal the sale total (' . number_format($subtotal) . ').',
                    ])->withInput();
                }
            }
            $paymentParts = [];
            foreach ($payments as $p) {
                $methodLabel = str_replace('_', ' ', ucfirst($p['method']));
                if ($validated['payment_mode'] === 'multi') {
                    $paymentParts[] = $methodLabel . ' ' . number_format($p['amount'] ?? 0);
                } else {
                    $paymentParts[] = $methodLabel . ' ' . number_format($subtotal);
                }
            }
            $paymentSummary = implode(', ', $paymentParts);
            $primaryMethod = $payments[0]['method'] ?? 'cash';

            // 6. Create sale
            $sale = $this->supabase->insert('sales', [
                'sale_number' => $saleNumber,
                'branch_id' => $branchId,
                'cashier_id' => $supabaseUserId,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'supplier' => null,
                'payment_method' => $primaryMethod,
                'payment_summary' => $paymentSummary,
                'sale_type' => $validated['sale_type'] ?? 'retail',
                'payment_status' => 'paid',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            if (!$sale) {
                return back()->withErrors(['error' => 'Failed to create sale. Please try again.'])->withInput();
            }

            // 7. Batch insert sale items
            foreach ($saleItems as &$si) {
                $si['sale_id'] = $sale['id'];
            }
            unset($si);
            $itemsResult = $this->supabase->insertMany('sale_items', $saleItems);
            if ($itemsResult === null) {
                $this->supabase->delete('sales', ['id' => $sale['id']]);
                return back()->withErrors(['error' => 'Sale items could not be saved. Please try again.'])->withInput();
            }

            // 8. Update stock quantities
            foreach ($stockUpdates as $su) {
                $this->supabase->update('branch_stock', [
                    'quantity' => $su['newQty'],
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $su['id']]);
            }

            // 9. Batch insert stock movements
            foreach ($stockMovements as &$sm) {
                $sm['reference_id'] = $sale['id'];
            }
            unset($sm);
            $movementsResult = $this->supabase->insertMany('stock_movements', $stockMovements);
            if ($movementsResult === null) {
                \Illuminate\Support\Facades\Log::warning("Stock movements not saved for sale {$saleNumber} (sale id {$sale['id']}).");
            }

            // 10. Auto-outstock empty bottles
            foreach ($bottleDeductions as $bd) {
                $this->bottles->deduct($branchId, $bd['volume'], $bd['quantity'], $bd['reason'], $supabaseUserId, $bd['variant'] ?? '');
            }

            // 11. Audit log
            $this->supabase->insert('audit_logs', [
                'user_id' => $supabaseUserId,
                'action' => 'sale_created',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            if ($priceOverridden) {
                (new \App\Services\AuditService())->recordCriticalAction(
                    'price_customization',
                    'custom_price_sale',
                    'Price Customized',
                    "A custom sale price was used in sale {$saleNumber} (Seller).",
                    ['sale_id' => $sale['id'], 'sale_number' => $saleNumber],
                    'sale',
                    (string) $sale['id'],
                    [],
                    ['custom_price_applied' => true]
                );
            }

            return redirect()->route('seller.sales.show', $sale['id'])
                ->with('success', "Sale {$saleNumber} completed successfully!");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Sale failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function show($saleId)
    {
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

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

        return view('seller.sales.show', ['sale' => (object) $sale]);
    }
}