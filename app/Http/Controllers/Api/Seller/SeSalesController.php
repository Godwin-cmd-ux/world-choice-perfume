<?php

namespace App\Http\Controllers\Api\Seller;

use Illuminate\Http\Request;

/**
 * JSON twin of the seller Sales screens: the member's OWN sales listing
 * (cashier_id filter, date range), the sale-form data and the full
 * checkout. Receipts are personal too — the website 403s a sale that was
 * not rang up by the signed-in seller, and so does this controller. The
 * checkout itself is the shared SmSalesSupport commit with the audit labels
 * the website's seller store writes: action sale_created, a Price
 * Customized audit signed "(Seller)".
 */
class SeSalesController extends SeBaseController
{
    use \App\Http\Controllers\Api\StockManager\SmSalesSupport;

    /** Website seller store: 'sale_created'. */
    protected function saleAuditAction(): string
    {
        return 'sale_created';
    }

    /** Role name used in the audit prose. */
    protected function saleActorLabel(): string
    {
        return 'Seller';
    }

    /** The website's seller store records a Price Customized audit. */
    protected function auditsCustomPrice(): bool
    {
        return true;
    }

    public function index(Request $request)
    {
        $userId = $this->performingUserId($request);

        $params = [
            'select' => '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(id,name,phone)',
            'cashier_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ];

        $sales = $this->supabase->query('sales', $params);

        // Date filters in PHP — PostgREST cannot take duplicate keys (the
        // website applies the very same filters the same way).
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        if ($dateFrom !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) >= $dateFrom);
        }
        if ($dateTo !== '') {
            $sales = array_filter($sales, fn ($s) => substr($s['created_at'] ?? '', 0, 10) <= $dateTo);
        }
        $sales = array_values($sales);

        $totalSales = array_sum(array_map(fn ($s) => $s['total'] ?? 0, $sales));

        return response()->json([
            'sales' => $sales,
            'totalSales' => $totalSales,
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

    /**
     * Receipt — personal, exactly like the website: a sale rang up by
     * anyone else (or a missing id) answers 403.
     */
    public function show(Request $request, int $saleId)
    {
        $sale = $this->supabase->find('sales', $saleId, '*, items:sale_items(*, product:products(id,name,brand)), customer:customers(*), cashier:users(id,name), branch:branches(id,name,address)');
        if (! $sale || ($sale['cashier_id'] ?? null) != $this->performingUserId($request)) {
            abort(403, 'You may only view your own sales.');
        }

        return response()->json(['sale' => $sale]);
    }
}
