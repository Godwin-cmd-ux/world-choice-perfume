{{-- Sale: searchable product checkbox picker + empty bottles card (wholesale only). Params: $products, $bottleStock, $bottleVariants, $productVarieties, $accent --}}
@php
    // A product stocked in with bottling records is listed as one row per
    // variety — "Reef 33 - 50ml With Box · With Logo · Yellow" — each with
    // its own price and quantity, exactly like a plain product. Products
    // stocked in before variety tracking have no buckets and stay as they are.
    $saleRows = [];
    foreach ($products as $stock) {
        $name = (string) ($stock->product->name ?? '');
        $category = (string) ($stock->product->category ?? '');
        $buckets = $productVarieties[$stock->product_id] ?? [];

        if ($category === 'Oil Fragrance' && !empty($buckets)) {
            foreach ($buckets as $vol) {
                foreach ($vol['variants'] as $v) {
                    $price = (float) ($v['price'] ?? 0);
                    $saleRows[] = [
                        'key' => $stock->product_id . '|' . $vol['volume'] . '|' . $v['key'],
                        'name' => $name . ' - ' . $vol['label'] . ' ' . $v['label'],
                        'product_id' => $stock->product_id,
                        'volume' => $vol['volume'],
                        'variant' => $v['key'],
                        'price' => $price > 0 ? $price : (float) $stock->selling_price,
                        'stock' => (int) $v['available'],
                        'is_variety' => true,
                    ];
                }
            }
            continue;
        }

        $saleRows[] = [
            'key' => $stock->product_id . '||',
            'name' => $name,
            'product_id' => $stock->product_id,
            'volume' => '',
            'variant' => '',
            'price' => (float) $stock->selling_price,
            'stock' => (int) $stock->quantity,
            'is_variety' => false,
        ];
    }
@endphp
<div class="bg-white rounded-xl shadow p-6" id="products-card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold"><i class="fas fa-box mr-1"></i> Products</h3>
        <span class="text-[10px] text-gray-400">Search &amp; tick items to sell</span>
    </div>

    <input type="text" id="productSearch" placeholder="Search products..." autocomplete="off"
        class="w-full px-3 py-2 border rounded-lg text-sm mb-3 focus:ring-2 focus:ring-{{ $accent }}-500">

    <div id="product-list" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach($saleRows as $row)
            <label class="product-pick flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-{{ $accent }}-50/40 transition">
                <input type="checkbox" class="product-check rounded text-{{ $accent }}-600 focus:ring-{{ $accent }}-500"
                    value="{{ $row['product_id'] }}"
                    data-row-key="{{ $row['key'] }}"
                    data-price="{{ $row['price'] }}"
                    data-stock="{{ $row['stock'] }}"
                    data-name="{{ $row['name'] }}"
                    data-volume="{{ $row['volume'] }}"
                    data-variant="{{ $row['variant'] }}">
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-medium truncate">{{ $row['name'] }}</span>
                    <span class="block text-xs text-gray-500">TZS {{ number_format($row['price']) }} &middot; {{ $row['stock'] }} in stock</span>
                </span>
                @if($row['is_variety'])
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-gray-50 border border-gray-200 text-gray-500 flex-shrink-0">
                        <i class="fas fa-wine-bottle"></i> Variety
                    </span>
                @endif
            </label>
        @endforeach
    </div>
    <p id="product-no-match" class="hidden text-xs text-gray-400 mb-3"><i class="fas fa-info-circle mr-1"></i> No products match your search.</p>

    <h4 class="text-sm font-semibold mb-2 text-gray-600"><i class="fas fa-shopping-cart mr-1"></i> Selected Items</h4>
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Product</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Price (TZS)</th>
                    <th class="text-center px-3 py-2 font-medium text-gray-500">Qty</th>
                    <th class="text-right px-3 py-2 font-medium text-gray-500">Total</th>
                    <th class="w-8"></th>
                </tr>
            </thead>
            <tbody id="cart-rows" class="divide-y divide-gray-100"></tbody>
        </table>
    </div>
    <p id="cart-empty" class="text-xs text-gray-400 py-2 text-center"><i class="fas fa-info-circle mr-1"></i> Check products above to add them to the sale.</p>

    <div class="mt-4 pt-4 border-t flex justify-between items-center">
        <span class="text-lg font-semibold">Total:</span>
        <span id="grand-total" class="text-2xl font-bold text-{{ $accent }}-700">TZS 0</span>
    </div>
</div>

<!-- Empty Bottles (wholesale only) -->
<div class="bg-white rounded-xl shadow p-6" id="empty-bottles-card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold"><i class="fas fa-wine-bottle mr-1"></i> Empty Bottles</h3>
        <span class="text-[10px] text-gray-400">Wholesale only &mdash; auto-outstocks bottle stock</span>
    </div>

    <div id="bottle-items-container" class="space-y-3">
        {{-- Each line wraps instead of pushing past the card. The fixed
             widths that were here (w-32 + w-52 + w-20 + w-28) added up to more
             than the column is wide, so a filled line spilled out of the card.
             The selects are now flexible with a readable minimum, and only
             the small controls hold their size. --}}
        <div class="bottle-item-row flex flex-wrap gap-2 items-start">
            <select name="empty_bottles[0][volume]" class="flex-1 min-w-[9rem] max-w-full px-3 py-2 border rounded-lg text-sm bottle-volume-select">
                <option value="">-- Volume --</option>
                @foreach(\App\Services\BottleStockService::VOLUMES as $v)
                    <option value="{{ $v }}" data-stock="{{ $bottleStock[$v] ?? 0 }}">{{ $v }}ml ({{ $bottleStock[$v] ?? 0 }} in stock)</option>
                @endforeach
            </select>
            <select name="empty_bottles[0][variant]" class="flex-1 min-w-[11rem] max-w-full px-3 py-2 border rounded-lg text-sm bottle-variant-select hidden"></select>
            <input type="number" name="empty_bottles[0][quantity]" value="1" min="1" class="w-20 shrink-0 px-3 py-2 border rounded-lg text-sm text-center bottle-qty-input">
            <input type="number" name="empty_bottles[0][price]" step="0.01" min="0" placeholder="Price (TZS)" class="flex-1 min-w-[7rem] max-w-full px-3 py-2 border rounded-lg text-sm bottle-price-input">
            <span class="bottle-line-total font-medium text-sm w-24 shrink-0 text-right pt-2">TZS 0</span>
            <button type="button" onclick="removeBottleRow(this)" class="text-red-500 hover:text-red-700 px-2 pt-2 shrink-0"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <button type="button" onclick="addBottleRow()" class="mt-3 text-{{ $accent }}-700 hover:underline text-sm">
        <i class="fas fa-plus mr-1"></i> Add Empty Bottle Line
    </button>
</div>