<?php

namespace App\Services;

use App\Support\BusinessDay;
use Carbon\Carbon;

/**
 * Profit figures for the report modules.
 *
 * A product's unit cost is kept on branch_stock.buying_cost and its selling
 * price on branch_stock.selling_price. Every sale line snapshots the unit cost
 * it was sold at into sale_items.unit_cost, so this service can report both:
 *
 *  - realised profit for a date range (sales minus the cost of the goods sold)
 *  - the profit each product is currently carrying in stock
 */
class ProfitReportService
{
    private SupabaseService $supabase;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    /**
     * Sales, expenses and profit for a branch (or the whole company when
     * $branchId is null) within the given range.
     *
     * Profit is reported twice: gross profit takes only the cost of the goods
     * sold off the revenue, while net profit also subtracts the branch's
     * expenses for the same window.
     */
    public function salesProfit(?int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $salesParams = [
            'select' => 'id,total,items:sale_items(quantity,unit_price,unit_cost,total,product_id,product:products(id,name))',
            'payment_status' => 'eq.paid',
            'created_at' => BusinessDay::rangeFilter($startDate, $endDate),
            'order' => 'created_at.desc',
        ];

        if ($branchId !== null) {
            $salesParams['branch_id'] = "eq.{$branchId}";
        }

        $sales = $this->supabase->query('sales', $salesParams);

        $revenue = 0.0;
        $cogs = 0.0;
        $itemsSold = 0;
        $products = [];

        foreach ($sales as $sale) {
            $revenue += (float) ($sale['total'] ?? 0);

            foreach ($sale['items'] ?? [] as $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = (float) ($item['total'] ?? ($unitPrice * $quantity));
                $lineCost = $unitCost * $quantity;

                $cogs += $lineCost;
                $itemsSold += $quantity;

                $productId = (string) ($item['product_id'] ?? '');
                $productName = is_array($item['product'] ?? null)
                    ? ($item['product']['name'] ?? 'Unknown product')
                    : 'Unknown product';

                if (!isset($products[$productId])) {
                    $products[$productId] = [
                        'name' => $productName,
                        'quantity' => 0,
                        'revenue' => 0.0,
                        'cogs' => 0.0,
                        'profit' => 0.0,
                    ];
                }

                $products[$productId]['quantity'] += $quantity;
                $products[$productId]['revenue'] += $lineTotal;
                $products[$productId]['cogs'] += $lineCost;
                $products[$productId]['profit'] += $lineTotal - $lineCost;
            }
        }

        $expensesParams = [
            'select' => 'id,amount',
            'created_at' => BusinessDay::rangeFilter($startDate, $endDate),
        ];

        if ($branchId !== null) {
            $expensesParams['branch_id'] = "eq.{$branchId}";
        }

        $expenses = $this->supabase->query('expenses', $expensesParams);
        $totalExpenses = (float) array_sum(array_map(fn ($e) => (float) ($e['amount'] ?? 0), $expenses));

        $grossProfit = $revenue - $cogs;

        usort($products, fn ($a, $b) => $b['profit'] <=> $a['profit']);

        return [
            'revenue' => $revenue,
            'expenses' => $totalExpenses,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit - $totalExpenses,
            'transactions' => count($sales),
            'items_sold' => $itemsSold,
            'margin' => $revenue > 0 ? round($grossProfit / $revenue * 100, 1) : 0.0,
            'products' => array_values($products),
        ];
    }

    /**
     * Every product currently in stock with its unit cost, selling price and
     * profit. Each branch's stock is its own row, because the same product can
     * carry a different price and cost in another branch.
     *
     * The summary is the footer for that list: how many distinct products,
     * how many units they hold, what they cost, what they sell for and the
     * profit still sitting in stock.
     */
    public function stockProfit(?int $branchId): array
    {
        $params = [
            'select' => 'id,branch_id,product_id,quantity,buying_cost,selling_price,category,product:products(id,name,brand,category),branch:branches(id,name)',
            'order' => 'created_at.desc',
        ];

        if ($branchId !== null) {
            $params['branch_id'] = "eq.{$branchId}";
        }

        $stocks = $this->supabase->query('branch_stock', $params);

        $rows = [];
        $productIds = [];

        foreach ($stocks as $stock) {
            $product = is_array($stock['product'] ?? null) ? $stock['product'] : null;
            if (!$product) {
                continue;
            }

            $quantity = (int) ($stock['quantity'] ?? 0);
            $unitCost = (float) ($stock['buying_cost'] ?? 0);
            $sellingPrice = (float) ($stock['selling_price'] ?? 0);
            $unitProfit = $sellingPrice - $unitCost;

            $productId = (string) ($stock['product_id'] ?? '');
            $productIds[$productId] = true;

            $rows[] = [
                'product_id' => $productId,
                'product' => $product['name'] ?? 'Unknown product',
                'brand' => $product['brand'] ?? null,
                'category' => $stock['category'] ?? ($product['category'] ?? null),
                'branch' => $stock['branch']['name'] ?? 'Unknown branch',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'selling_price' => $sellingPrice,
                'unit_profit' => $unitProfit,
                'stock_cost' => $quantity * $unitCost,
                'stock_value' => $quantity * $sellingPrice,
                'potential_profit' => $quantity * $unitProfit,
                'margin' => $sellingPrice > 0 ? round($unitProfit / $sellingPrice * 100, 1) : 0.0,
            ];
        }

        usort($rows, fn ($a, $b) => [$a['product'], $a['branch']] <=> [$b['product'], $b['branch']]);

        $stockValue = array_sum(array_column($rows, 'stock_value'));
        $potentialProfit = array_sum(array_column($rows, 'potential_profit'));

        return [
            'rows' => $rows,
            'summary' => [
                'products' => count($productIds),
                'rows' => count($rows),
                'units' => array_sum(array_column($rows, 'quantity')),
                'stock_cost' => array_sum(array_column($rows, 'stock_cost')),
                'stock_value' => $stockValue,
                'potential_profit' => $potentialProfit,
                'margin' => $stockValue > 0 ? round($potentialProfit / $stockValue * 100, 1) : 0.0,
            ],
        ];
    }
}
