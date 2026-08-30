<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SalesController extends Controller
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
            'select' => '*, cashier:users(id,name), customer:customers(id,name,phone), items:sale_items(*, product:products(id,name,brand))',
            'branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ];

        if ($request->cashier_id) {
            $params['cashier_id'] = "eq.{$request->cashier_id}";
        }

        $sales = $this->supabase->query('sales', $params);

        // Apply date filters in PHP (PostgREST doesn't support duplicate keys)
        if ($request->date_from) {
            $from = $request->date_from;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) >= $from);
        }
        if ($request->date_to) {
            $to = $request->date_to;
            $sales = array_filter($sales, fn($s) => substr($s['created_at'] ?? '', 0, 10) <= $to);
        }

        $sales = array_values($sales);
        $totalSales = array_sum(array_map(fn($s) => $s['total'] ?? 0, $sales));

        // Get cashiers for this branch
        $cashiers = $this->supabase->query('users', [
            'branch_id' => "eq.{$branchId}",
            'role' => 'eq.cashier',
            'status' => 'eq.approved',
            'select' => 'id,name',
        ]);

        // Cast for views
        $sales = collect($sales)->map(function ($s) {
            if (isset($s['cashier']) && is_array($s['cashier'])) $s['cashier'] = (object) $s['cashier'];
            if (isset($s['customer']) && is_array($s['customer'])) $s['customer'] = (object) $s['customer'];
            if (isset($s['items'])) {
                $s['items'] = collect($s['items'])->map(function ($item) {
                    if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                    return (object) $item;
                });
            }
            return (object) $s;
        });

        return view('branch-admin.sales.index', [
            'sales' => $sales,
            'totalSales' => $totalSales,
            'cashiers' => collect($cashiers),
        ]);
    }

    public function show($saleId)
    {
        $sale = $this->supabase->find('sales', $saleId, '*, cashier:users(id,name), customer:customers(*), items:sale_items(*, product:products(id,name,brand)), branch:branches(id,name,address)');
        if (!$sale) abort(404);

        if (isset($sale['cashier']) && is_array($sale['cashier'])) $sale['cashier'] = (object) $sale['cashier'];
        if (isset($sale['customer']) && is_array($sale['customer'])) $sale['customer'] = (object) $sale['customer'];
        if (isset($sale['branch']) && is_array($sale['branch'])) $sale['branch'] = (object) $sale['branch'];
        if (isset($sale['items'])) {
            $sale['items'] = collect($sale['items'])->map(function ($item) {
                if (isset($item['product']) && is_array($item['product'])) $item['product'] = (object) $item['product'];
                return (object) $item;
            });
        }

        return view('branch-admin.sales.show', ['sale' => (object) $sale]);
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

        return view('branch-admin.sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'supplier' => 'nullable|string|max:255',
            'payment_mode' => 'required|in:single,multi',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,bank_transfer,mobile_payment',
            'payments.*.amount' => 'required_if:payment_mode,multi|nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_price' => 'nullable|numeric|min:0',
            'sale_type' => 'required|in:retail,wholesale',
        ]);

        $branchId = auth()->user()->branch_id;
        $supabaseUserId = auth()->user()->supabase_id ?? auth()->id();

        try {
            // 1. Determine customer
            $customerId = null;
            if (!empty($validated['customer_id'])) {
                $customerId = $validated['customer_id'];
            } elseif (!empty($validated['customer_name']) || !empty($validated['customer_phone'])) {
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
                        'whatsapp' => $validated['customer_phone'] ?? null,
                    ]);
                    $customerId = $customer['id'] ?? null;
                }
            }

            $saleNumber = 'SALE-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            // 2. Fetch ALL stock in ONE query
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
                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;
                $newQty = ($stock['quantity'] ?? 0) - $item['quantity'];

                $saleItems[] = [
                    'sale_id' => null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
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
                    'reference_id' => null,
                    'performed_by' => $supabaseUserId,
                    'notes' => "Sale {$saleNumber} (Branch Admin)",
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ];
            }

            // 4. Build payment summary
            $payments = $validated['payments'] ?? [];
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

            // 5. Create sale
            $sale = $this->supabase->insert('sales', [
                'sale_number' => $saleNumber,
                'branch_id' => $branchId,
                'cashier_id' => $supabaseUserId,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'supplier' => $validated['supplier'] ?? null,
                'payment_method' => $primaryMethod,
                'payment_summary' => $paymentSummary,
                'sale_type' => $validated['sale_type'] ?? 'retail',
                'payment_status' => 'paid',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            if (!$sale) {
                return back()->withErrors(['error' => 'Failed to create sale.'])->withInput();
            }

            // 5. Batch insert sale items
            foreach ($saleItems as &$si) {
                $si['sale_id'] = $sale['id'];
            }
            $this->supabase->insertMany('sale_items', $saleItems);

            // 6. Update stock quantities
            foreach ($stockUpdates as $su) {
                $this->supabase->update('branch_stock', [
                    'quantity' => $su['newQty'],
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => $su['id']]);
            }

            // 7. Batch insert stock movements
            foreach ($stockMovements as &$sm) {
                $sm['reference_id'] = $sale['id'];
            }
            $this->supabase->insertMany('stock_movements', $stockMovements);

            // 8. Audit log
            $this->supabase->insert('audit_logs', [
                'user_id' => $supabaseUserId,
                'action' => 'sale_created_by_admin',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            return redirect()->route('branch-admin.sales.show', $sale['id'])
                ->with('success', "Sale {$saleNumber} completed successfully!");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Sale failed: ' . $e->getMessage()])->withInput();
        }
    }
}
