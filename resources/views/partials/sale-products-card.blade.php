{{-- Sale: searchable product checkbox picker + empty bottles card (wholesale only). Params: $products, $bottleStock, $bottleVariants, $accent --}}
<div class="bg-white rounded-xl shadow p-6" id="products-card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold"><i class="fas fa-box mr-1"></i> Products</h3>
        <span class="text-[10px] text-gray-400">Search &amp; check items to add</span>
    </div>

    <input type="text" id="productSearch" placeholder="Search products..." autocomplete="off"
        class="w-full px-3 py-2 border rounded-lg text-sm mb-3 focus:ring-2 focus:ring-{{ $accent }}-500">

    <div id="product-list" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100 mb-3">
        @foreach($products as $stock)
            <label class="product-pick flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-{{ $accent }}-50/40 transition">
                <input type="checkbox" class="product-check rounded text-{{ $accent }}-600 focus:ring-{{ $accent }}-500"
                    value="{{ $stock->product_id }}"
                    data-price="{{ $stock->selling_price }}"
                    data-stock="{{ $stock->quantity }}"
                    data-name="{{ $stock->product->name }}">
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-medium truncate">{{ $stock->product->name }}</span>
                    <span class="block text-xs text-gray-500">TZS {{ number_format($stock->selling_price) }} &middot; {{ $stock->quantity }} in stock</span>
                </span>
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
        <div class="bottle-item-row flex gap-2 items-start">
            <select name="empty_bottles[0][volume]" class="w-32 px-3 py-2 border rounded-lg text-sm bottle-volume-select">
                <option value="">-- Volume --</option>
                @foreach(\App\Services\BottleStockService::VOLUMES as $v)
                    <option value="{{ $v }}" data-stock="{{ $bottleStock[$v] ?? 0 }}">{{ $v }}ml ({{ $bottleStock[$v] ?? 0 }} in stock)</option>
                @endforeach
            </select>
            <select name="empty_bottles[0][variant]" class="w-52 px-3 py-2 border rounded-lg text-sm bottle-variant-select hidden"></select>
            <input type="number" name="empty_bottles[0][quantity]" value="1" min="1" class="w-20 px-3 py-2 border rounded-lg text-sm text-center bottle-qty-input">
            <input type="number" name="empty_bottles[0][price]" step="0.01" min="0" placeholder="Price (TZS)" class="flex-1 px-3 py-2 border rounded-lg text-sm bottle-price-input">
            <span class="bottle-line-total font-medium text-sm w-28 text-right pt-2">TZS 0</span>
            <button type="button" onclick="removeBottleRow(this)" class="text-red-500 hover:text-red-700 px-2 pt-2"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <button type="button" onclick="addBottleRow()" class="mt-3 text-{{ $accent }}-700 hover:underline text-sm">
        <i class="fas fa-plus mr-1"></i> Add Empty Bottle Line
    </button>
</div>