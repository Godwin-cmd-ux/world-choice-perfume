@extends('stock-manager.layouts.app')
@section('title', 'Add Product Stock')
@section('header', 'Add Product Stock')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.product-stock-entry.store') }}">
            @csrf

            @if ($errors->any())
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                    <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select name="category" id="category-select" onchange="onCategoryChange()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Category First</option>
                    {{-- The category is preselected when arriving from the low-stock
                         list, so the product below resolves without a second choice. --}}
                    <option value="Oil Fragrance" {{ old('category', $preselectCategory ?? null) == 'Oil Fragrance' ? 'selected' : '' }}>Oil Fragrance</option>
                    <option value="Brand Perfume" {{ old('category', $preselectCategory ?? null) == 'Brand Perfume' ? 'selected' : '' }}>Brand Perfume</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Choose a category first — only its products will be listed below.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select name="product_id" id="product_id" required onchange="onProductChange()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    @if(old('category', $preselectCategory ?? null))
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            @if($product->category === old('category', $preselectCategory ?? null))
                                <option value="{{ $product->id }}" data-category="{{ $product->category }}" {{ old('product_id', $preselectProductId ?? null) == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} — {{ $product->brand }}
                                </option>
                            @endif
                        @endforeach
                    @else
                        <option value="">Select a Category First</option>
                    @endif
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Received *</label>
                <input type="date" name="date_received" required value="{{ old('date_received', date('Y-m-d')) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex items-start justify-between mb-3" data-lines-header>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Item Lines *</label>
                    <p class="text-[11px] text-gray-400">Record the same product in another volume or quantity — one line per volume.</p>
                </div>
                <button type="button" onclick="addItemLine()"
                    class="border border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i>Add Item Line
                </button>
            </div>

            @if(isset($bottleVariantsBranchName))
                <p class="mb-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-700">
                    <i class="fas fa-truck mr-1"></i>Bottle availability comes from the <strong>{{ $bottleVariantsBranchName }}</strong> bottle stock — every oil fragrance line is out-stocked from that stock.
                </p>
            @endif

            <div id="item-lines"></div>

            <div class="flex gap-3 mt-6">
                <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Entry
                </button>
                <a href="{{ route('stock-manager.product-stock') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

{{-- One item line: the quantity, its bottle volume/variety and its own price.
     Cloned for every "Add Item Line" and for each line restored after a
     validation error. --}}
<template id="item-line-template">
    <div class="item-line border border-gray-200 rounded-lg p-4 mb-4" data-item-line>
        <div class="flex items-center justify-between mb-3" data-line-heading>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Item Line <span data-line-number>1</span></span>
            <button type="button" class="remove-item-line hidden text-xs font-medium text-red-600 hover:text-red-700" onclick="removeItemLine(this)">
                <i class="fas fa-times mr-1"></i>Remove
            </button>
        </div>

        <div class="bottle-volume-field mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Volume (ml) *</label>
            <select name="items[__INDEX__][bottle_volume]" data-field="bottle_volume"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Select Bottle Volume</option>
                <option value="6">6ml</option>
                <option value="12">12ml</option>
                <option value="30">30ml</option>
                <option value="50">50ml</option>
                <option value="100">100ml</option>
            </select>
            <p class="text-[11px] text-gray-400 mt-1">
                <i class="fas fa-info-circle mr-1"></i>Selected volume bottles will be auto-outstocked by the quantity entered on this line.
            </p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input type="number" name="items[__INDEX__][quantity]" data-field="quantity" required min="1"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (TZS) *</label>
            <input type="number" name="items[__INDEX__][unit_cost]" data-field="unit_cost" required min="0" step="0.01"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <p class="text-[11px] text-gray-400 mt-1">
                <i class="fas fa-info-circle mr-1"></i>What this product costs you per unit — profit is calculated from this.
            </p>
        </div>

        <div class="bottle-variant-field mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Variety *</label>
            <select name="items[__INDEX__][bottle_variant]" data-field="bottle_variant"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Select Variety</option>
            </select>
            <p class="text-[11px] text-gray-400 mt-1">
                <i class="fas fa-info-circle mr-1"></i>The quantity is deducted from the exact variety picked (box / logo / color).
            </p>
        </div>

        {{-- Price is asked last on purpose: for oil fragrance it is the
             price OF the volume and variety chosen on this line, not a price
             for the product on its own. --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (TZS) *</label>
            <input type="number" name="items[__INDEX__][selling_price]" data-field="selling_price" required min="0" step="0.01"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <p class="variety-price-hint hidden text-[11px] text-emerald-700 mt-1">
                <i class="fas fa-tag mr-1"></i>This is the selling price of the <strong>specific volume and variety</strong> picked on this line (e.g. 50ml sells differently from 30ml).
            </p>
            <p class="hidden text-[11px] font-medium mt-2" data-profit-preview>
                Profit per unit: <span data-profit-value>TZS 0</span>
            </p>
        </div>
    </div>
</template>

@push('scripts')
<script>
const allProducts = @json($products ?? []);
const bottleVariants = @json($bottleVariants ?? []);
const oldItems = @json(array_values((array) old('items', [])));
const VARIANT_LABELS = {
    box_logo_yellow: 'With Box · With Logo · Yellow',
    box_logo_black: 'With Box · With Logo · Black',
    box_nologo_black: 'With Box · No Logo · Black',
    box_nologo_white: 'With Box · No Logo · White',
    no_box: 'Without Box',
    plain: 'Plain (no details)'
};
const DETAIL_VOLUMES = ['30', '50', '100'];
const VARIANT_KEYS = ['box_logo_yellow', 'box_logo_black', 'box_nologo_black', 'box_nologo_white', 'no_box', 'plain'];

let lineCounter = 0;

function currentCategory() {
    return document.getElementById('category-select').value;
}

// Several item lines are only meaningful for oil fragrance, where the same
// product is bottled in different volumes. Brand perfume stays on one line.
function isOilFragrance() {
    return currentCategory() === 'Oil Fragrance';
}

function onCategoryChange() {
    const productSelect = document.getElementById('product_id');
    productSelect.value = '';
    filterProducts();

    if (!isOilFragrance()) {
        collapseToSingleLine();
    }

    document.querySelectorAll('#item-lines [data-item-line]').forEach(function (line) {
        toggleBottleVolume(line);
    });

    updateItemLineControls();
}

function filterProducts() {
    const category = currentCategory();
    const productSelect = document.getElementById('product_id');
    productSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = category ? 'Select Product' : 'Select a Category First';
    productSelect.appendChild(placeholder);

    if (!category) {
        return;
    }

    (allProducts || []).forEach(p => {
        if ((p.category || '') !== category) {
            return;
        }
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.dataset.category = p.category;
        opt.textContent = p.name + ' — ' + p.brand;
        productSelect.appendChild(opt);
    });
}

function onProductChange() {
    document.querySelectorAll('#item-lines [data-item-line]').forEach(function (line) {
        toggleBottleVolume(line);
    });
}

function addItemLine(values) {
    values = values || {};

    const template = document.getElementById('item-line-template');
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, lineCounter++);
    const line = wrapper.firstElementChild;

    document.getElementById('item-lines').appendChild(line);

    line.querySelector('[data-field="bottle_volume"]').addEventListener('change', function () {
        refreshVariantOptions(line);
    });

    line.querySelector('[data-field="unit_cost"]').addEventListener('input', function () {
        refreshLineProfit(line);
    });
    line.querySelector('[data-field="selling_price"]').addEventListener('input', function () {
        refreshLineProfit(line);
    });

    setLineValue(line, 'quantity', values.quantity);
    setLineValue(line, 'unit_cost', values.unit_cost);
    setLineValue(line, 'bottle_volume', values.bottle_volume);
    setLineValue(line, 'selling_price', values.selling_price);

    toggleBottleVolume(line, values.bottle_variant || '');
    refreshLineProfit(line);
    renumberLines();
    updateItemLineControls();

    return line;
}

function refreshLineProfit(line) {
    const costInput = line.querySelector('[data-field="unit_cost"]');
    const priceInput = line.querySelector('[data-field="selling_price"]');
    const preview = line.querySelector('[data-profit-preview]');
    const value = line.querySelector('[data-profit-value]');

    if (!costInput || !priceInput || !preview || !value) {
        return;
    }

    const hasValue = costInput.value !== '' || priceInput.value !== '';
    const profit = (parseFloat(priceInput.value) || 0) - (parseFloat(costInput.value) || 0);

    preview.classList.toggle('hidden', !hasValue);
    value.textContent = 'TZS ' + profit.toLocaleString('en-US', { maximumFractionDigits: 2 });
    value.classList.toggle('text-emerald-700', profit >= 0);
    value.classList.toggle('text-red-600', profit < 0);
}

function setLineValue(line, field, value) {
    if (value === null || value === undefined) {
        return;
    }
    const input = line.querySelector('[data-field="' + field + '"]');
    if (input) {
        input.value = value;
    }
}

function removeItemLine(button) {
    const line = button.closest('[data-item-line]');
    if (!line) {
        return;
    }
    line.remove();

    if (!document.querySelector('#item-lines [data-item-line]')) {
        addItemLine();
    }

    renumberLines();
}

// The add-line button and the per-line label belong to the multi-line entry,
// so both disappear for brand perfume.
function updateItemLineControls() {
    const linesHeader = document.querySelector('[data-lines-header]');
    if (linesHeader) {
        linesHeader.classList.toggle('hidden', !isOilFragrance());
    }

    document.querySelectorAll('#item-lines [data-item-line]').forEach(function (line) {
        const heading = line.querySelector('[data-line-heading]');
        if (heading) {
            heading.classList.toggle('hidden', !isOilFragrance());
        }
    });
}

function collapseToSingleLine() {
    document.querySelectorAll('#item-lines [data-item-line]').forEach(function (line, i) {
        if (i > 0) {
            line.remove();
        }
    });

    renumberLines();
}

function renumberLines() {
    const lines = document.querySelectorAll('#item-lines [data-item-line]');
    lines.forEach(function (line, i) {
        const label = line.querySelector('[data-line-number]');
        if (label) {
            label.textContent = i + 1;
        }
        const remove = line.querySelector('.remove-item-line');
        if (remove) {
            remove.classList.toggle('hidden', lines.length === 1);
        }
    });
}

function toggleBottleVolume(line, preselectVariant) {
    const volumeField = line.querySelector('.bottle-volume-field');
    const volumeSelect = line.querySelector('[data-field="bottle_volume"]');
    const priceHint = line.querySelector('.variety-price-hint');

    if (currentCategory() === 'Oil Fragrance') {
        volumeField.classList.remove('hidden');
        volumeSelect.setAttribute('required', 'required');
        if (priceHint) priceHint.classList.remove('hidden');
    } else {
        if (priceHint) priceHint.classList.add('hidden');
        volumeField.classList.add('hidden');
        volumeSelect.removeAttribute('required');
        volumeSelect.value = '';
        hideVariantField(line);
    }

    refreshVariantOptions(line, preselectVariant || '');
}

function refreshVariantOptions(line, preselectVariant) {
    const volume = line.querySelector('[data-field="bottle_volume"]').value;
    const variantField = line.querySelector('.bottle-variant-field');
    const variantSelect = line.querySelector('[data-field="bottle_variant"]');

    if (currentCategory() !== 'Oil Fragrance' || !volume) {
        hideVariantField(line);
        return;
    }

    variantSelect.innerHTML = '<option value="">Select Variety</option>';

    if (!DETAIL_VOLUMES.includes(volume)) {
        // 6ml / 12ml bottles have no box/logo details — recorded as plain.
        variantSelect.innerHTML = '<option value="plain">Plain (no details)</option>';
        hideVariantField(line);
        return;
    }

    const buckets = bottleVariants[volume] || {};
    VARIANT_KEYS.forEach(function (key) {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = (VARIANT_LABELS[key] || key) + ' — ' + (buckets[key] || 0) + ' in stock';
        variantSelect.appendChild(opt);
    });

    variantField.classList.remove('hidden');
    variantSelect.setAttribute('required', 'required');

    if (preselectVariant) {
        variantSelect.value = preselectVariant;
    }
}

function hideVariantField(line) {
    const variantField = line.querySelector('.bottle-variant-field');
    const variantSelect = line.querySelector('[data-field="bottle_variant"]');
    if (variantField) variantField.classList.add('hidden');
    if (variantSelect) variantSelect.removeAttribute('required');
}

// Rebuild the product list from the chosen category, then restore the
// previously submitted product and item lines after a validation error.
(function init () {
    filterProducts();

    const productId = @json(old('product_id', $preselectProductId ?? null)) || '';
    if (productId) {
        document.getElementById('product_id').value = productId;
    }

    if (Array.isArray(oldItems) && oldItems.length) {
        oldItems.forEach(function (item) {
            addItemLine(item);
        });
    } else {
        addItemLine();
    }

    if (!isOilFragrance()) {
        collapseToSingleLine();
    }

    updateItemLineControls();
})();
</script>
@endpush
@endsection
