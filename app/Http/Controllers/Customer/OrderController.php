<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\PesapalService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
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

        // Fetch products with stock at this branch
        $rawStock = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(*, images:product_images(*))',
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
                    'images' => collect($item['product']['images'] ?? []),
                ]),
            ];
        });

        return view('customer.orders.create', compact('branch', 'products'));
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
                'select' => 'product_id,quantity,selling_price',
            ]));
            $stockMap = [];
            foreach ($allStock as $s) {
                $stockMap[$s['product_id']] = $s;
            }

            // 3. Validate stock and calculate totals (no HTTP calls)
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $stock = $stockMap[$item['product_id']] ?? null;

                if (!$stock || ($stock['quantity'] ?? 0) < $item['quantity']) {
                    return back()->withErrors(['items' => 'Some products are no longer available in the requested quantity.'])->withInput();
                }

                $lineTotal = ($stock['selling_price'] ?? 0) * $item['quantity'];
                $total += $lineTotal;

                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $stock['selling_price'],
                    'total' => $lineTotal,
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
                'payment_method' => 'pesapal',
            ]);

            // 6. Batch insert order items (1 HTTP call instead of N)
            $itemsToInsert = array_map(fn($oi) => [
                'order_id' => $order['id'],
                'product_id' => $oi['product_id'],
                'quantity' => $oi['quantity'],
                'unit_price' => $oi['unit_price'],
                'total' => $oi['total'],
            ], $orderItems);
            $this->supabase->insertMany('order_items', $itemsToInsert);

            // 7. Send the customer to PesaPal to pay
            $payment = $this->initiatePesaPalPayment($order, $customerId, $validated);

            if ($payment['success'] && !empty($payment['redirect_url'])) {
                $this->supabase->update('orders', [
                    'payment_status' => 'pending',
                    'pesapal_tracking_id' => $payment['order_tracking_id'] ?? null,
                    'pesapal_merchant_reference' => $payment['merchant_reference'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ], ['id' => (int) $order['id']]);

                return redirect()->away($payment['redirect_url']);
            }

            $this->supabase->update('orders', [
                'payment_status' => 'failed',
                'updated_at' => now()->toIso8601String(),
            ], ['id' => (int) $order['id']]);

            return view('customer.orders.success', ['order' => (object) array_merge($order, ['payment_status' => 'failed'])]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Order failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Resume payment for an existing unpaid order.
     */
    public function pay(Request $request, $orderId)
    {
        $order = $this->supabase->find('orders', $orderId);

        if (!$order) {
            abort(404);
        }

        if (($order['payment_status'] ?? '') === 'paid') {
            return redirect()->route('customer.orders.track')
                ->with('success', 'This order has already been paid.');
        }

        $customerId = $order['customer_id'] ?? null;
        $customer = $customerId ? $this->supabase->find('customers', $customerId) : null;

        $payment = $this->initiatePesaPalPayment($order, $customerId, [
            'customer_name' => $customer['name'] ?? 'Customer',
            'customer_email' => $customer['email'] ?? null,
            'customer_phone' => $customer['phone'] ?? '',
        ]);

        if ($payment['success'] && !empty($payment['redirect_url'])) {
            $this->supabase->update('orders', [
                'payment_status' => 'pending',
                'pesapal_tracking_id' => $payment['order_tracking_id'] ?? null,
                'pesapal_merchant_reference' => $payment['merchant_reference'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ], ['id' => (int) $order['id']]);

            return redirect()->away($payment['redirect_url']);
        }

        return redirect()->route('customer.orders.track')
            ->with('error', 'Could not start PesaPal payment: ' . ($payment['message'] ?? 'Please try again later.'));
    }

    /**
     * Initiate a PesaPal payment for an order.
     */
    private function initiatePesaPalPayment(array $order, ?int $customerId, array $details): array
    {
        $pesapal = new PesapalService();

        return $pesapal->initiatePayment([
            'id' => $order['id'],
            'amount' => $order['total'],
            'description' => 'Order ' . $order['order_number'],
            'first_name' => $details['customer_name'] ?? 'Customer',
            'email' => $details['customer_email'] ?? null,
            'phone' => $details['customer_phone'] ?? '',
            'callback_url' => route('pesapal.callback'),
        ]);
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
            return back()->with('error', 'No orders found for this phone number.');
        }

        // Fetch orders for this customer
        $rawOrders = $this->supabase->query('orders', [
            'select' => '*, branch:branches(name), items:order_items(*, product:products(name))',
            'customer_id' => "eq.{$customer['id']}",
            'order' => 'created_at.desc',
            'limit' => 10,
        ]);

        $orders = collect($rawOrders)->map(function ($o) {
            $o['items'] = collect($o['items'] ?? [])->map(function ($item) {
                return (object) [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'product' => (object) ($item['product'] ?? []),
                ];
            });
            $o['branch'] = (object) ($o['branch'] ?? []);
            return (object) $o;
        });

        return view('customer.orders.tracked', compact('orders'));
    }
}
