<?php

namespace App\Http\Controllers\Api\CustomerCare;

use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care Sales screens: the branch's sales
 * listing, the sale-form data, and the full checkout. The checkout itself
 * is the shared SmSalesSupport commit — the same code path the Stock
 * Manager API uses, which is a port of the website's own duplicated store —
 * with the audit labels switched to Customer Care.
 */
class CcSalesController extends CcBaseController
{
    use \App\Http\Controllers\Api\StockManager\SmSalesSupport;

    /** Website CC store: 'sale_created' — not the stock manager's action name. */
    protected function saleAuditAction(): string
    {
        return 'sale_created';
    }

    /** Website CC store audits custom prices under the member's role. */
    protected function saleActorLabel(): string
    {
        return 'Customer Care';
    }

    /** The website's CC store records a Price Customized audit; SM's does not. */
    protected function auditsCustomPrice(): bool
    {
        return true;
    }

    public function index(Request $request)
    {
        $sales = $this->supabase->query('sales', [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name), cashier:users(id,name)',
            'branch_id' => 'eq.'.$this->ownBranchId($request),
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        // The same filters the website's sales pages offer: a date range and
        // the payment status. PostgREST cannot take duplicate keys, so the
        // dates are applied in PHP — exactly what the branch-admin twin
        // (BaSalesController@index) and the Blade controllers do.
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        if ($dateFrom !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) >= $dateFrom);
        }
        if ($dateTo !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) <= $dateTo);
        }

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $sales = array_filter($sales, fn ($s) => ($s['payment_status'] ?? 'pending') === $status);
        }

        $sales = collect(array_values($sales));

        return response()->json([
            'sales' => $sales->values()->all(),
            'totalRevenue' => (float) $sales->sum('total'),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** The sale form's data: branch stock, bottle stock, variety buckets. */
    public function options(Request $request)
    {
        $branchId = $this->ownBranchId($request);

        $rawStock = $this->supabase->query('branch_stock', [
            'select' => '*, product:products(id,name,brand,category,images:product_images(image_url))',
            'branch_id' => "eq.{$branchId}",
            'quantity' => 'gt.0',
            'order' => 'created_at.desc',
        ]);

        $products = collect($rawStock)->map(function ($item) {
            return [
                'id' => $item['id'],
                'branch_id' => $item['branch_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'selling_price' => $item['selling_price'],
                'product' => array_merge($item['product'] ?? [], [
                    'images' => collect($item['product']['images'] ?? [])->values()->all(),
                ]),
            ];
        })->values()->all();

        $productIds = array_map(fn ($p) => (int) $p['product_id'], $products);

        return response()->json([
            'products' => $products,
            'bottleStock' => $this->bottles->stockMap($branchId),
            'bottleVariants' => $this->bottles->variantStock($branchId),
            'productVarieties' => $this->varieties()->bucketsForProducts($branchId, $productIds),
            'is_products_only' => false,
            'scope' => $this->scopePayload($request),
        ]);
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
            'items.*.discount_price' => 'nullable|numeric|min:0',
            'empty_bottles' => 'nullable|array',
            'empty_bottles.*.volume' => 'nullable|integer|in:6,12,30,50,100',
            'empty_bottles.*.quantity' => 'nullable|integer|min:1',
            'empty_bottles.*.price' => 'nullable|numeric|min:0',
            'empty_bottles.*.variant' => 'nullable|string|max:32',
            'items.*.volume' => 'nullable|integer',
            'items.*.variant' => 'nullable|string|max:32',
            'sale_type' => 'required|in:retail,wholesale',
        ]);

        try {
            return $this->commitSale($request, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->fail(['error' => 'Sale failed: '.$e->getMessage()]);
        }
    }

    public function show(Request $request, int $saleId)
    {
        $sale = $this->supabase->find('sales', $saleId, '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(*), cashier:users(id,name), branch:branches(id,name,address)');
        if (! $sale || ($sale['branch_id'] ?? null) != $this->ownBranchId($request)) {
            abort(404, 'Sale not found.');
        }

        return response()->json(['sale' => $sale]);
    }
}
