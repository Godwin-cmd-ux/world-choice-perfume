@extends('stock-manager.layouts.app')
@section('title', 'Add Product Stock')
@section('header', 'Add Product Stock')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.product-stock-entry.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select name="category" id="category-select" onchange="onCategoryChange()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Category First</option>
                    <option value="Oil Fragrance" {{ old('category') == 'Oil Fragrance' ? 'selected' : '' }}>Oil Fragrance</option>
                    <option value="Brand Perfume" {{ old('category') == 'Brand Perfume' ? 'selected' : '' }}>Brand Perfume</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Choose a category first — only its products will be listed below.
                </p>
                @error('category')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select name="product_id" id="product_id" required onchange="onProductChange()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    @if(old('category'))
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            @if($product->category === old('category'))
                                <option value="{{ $product->id }}" data-category="{{ $product->category }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} — {{ $product->brand }}
                                </option>
                            @endif
                        @endforeach
                    @else
                        <option value="">Select a Category First</option>
                    @endif
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" required min="1"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Received *</label>
                    <input type="date" name="date_received" required value="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (TZS) *</label>
                <input type="number" name="selling_price" required min="0" step="0.01"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>


            <div id="bottle-volume-field" class="mb-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Volume (ml) *</label>
                <select name="bottle_volume" id="bottle_volume"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Bottle Volume</option>
                    <option value="6" {{ old('bottle_volume') == '6' ? 'selected' : '' }}>6ml</option>
                    <option value="12" {{ old('bottle_volume') == '12' ? 'selected' : '' }}>12ml</option>
                    <option value="30" {{ old('bottle_volume') == '30' ? 'selected' : '' }}>30ml</option>
                    <option value="50" {{ old('bottle_volume') == '50' ? 'selected' : '' }}>50ml</option>
                    <option value="100" {{ old('bottle_volume') == '100' ? 'selected' : '' }}>100ml</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Selected volume bottles will be auto-outstocked by the quantity you enter above.
                </p>
            </div>

            <div id="bottle-variant-field" class="mb-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Variety *</label>
                <select name="bottle_variant" id="bottle_variant" data-preselect="{{ old('bottle_variant') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Variety</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>The quantity is deducted from the exact variety you pick (box / logo / color).
                </p>
                @if(isset($bottleVariantsBranchName))
                    <p class="mt-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-700">
                        <i class="fas fa-truck mr-1"></i>Bottle availability shown here is from the <strong>{{ $bottleVariantsBranchName }}</strong> bottle stock — this entry will be out-stocked from that stock.
                    </p>
                @endif
                @error('bottle_variant')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
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

@push('scripts')
<script>
const allProducts = @json($products ?? []);
const bottleVariants = @json($bottleVariants ?? []);
const VARIANT_LABELS = {
    box_logo_yellow: 'With Box · With Logo · Yellow',
    box_logo_black: 'With Box · With Logo · Black',
    box_nologo_black: 'With Box · No Logo · Black',
    box_nologo_white: 'With Box · No Logo · White',
    no_box: 'Without Box',
    plain: 'Plain (no details)'
};
const DETAIL_VOLUMES = ['30', '50', '100'];

function onCategoryChange() {
    const productSelect = document.getElementById('product_id');
    productSelect.value = '';
    filterProducts();
    toggleBottleVolume();
}

function filterProducts() {
    const category = document.getElementById('category-select').value;
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
    toggleBottleVolume();
}

function toggleBottleVolume() {
    const category = document.getElementById('category-select').value;
    const field = document.getElementById('bottle-volume-field');
    const volumeInput = document.getElementById('bottle_volume');

    if (category === 'Oil Fragrance') {
        field.classList.remove('hidden');
        volumeInput.setAttribute('required', 'required');
    } else {
        field.classList.add('hidden');
        volumeInput.removeAttribute('required');
        volumeInput.value = '';
        hideVariantField();
    }
    refreshVariantOptions();
}

function refreshVariantOptions() {
    const category = document.getElementById('category-select').value;
    const volume = document.getElementById('bottle_volume').value;
    const variantField = document.getElementById('bottle-variant-field');
    const variantSelect = document.getElementById('bottle_variant');

    if (category !== 'Oil Fragrance' || !volume) {
        hideVariantField();
        return;
    }

    variantSelect.innerHTML = '<option value="">Select Variety</option>';

    if (!DETAIL_VOLUMES.includes(volume)) {
        // 6ml / 12ml bottles have no box/logo details — recorded as plain.
        const opt = document.createElement('option');
        opt.value = 'plain';
        opt.textContent = 'Plain (no details)';
        opt.selected = true;
        variantSelect.appendChild(opt);
        hideVariantField();
        return;
    }

    const buckets = (bottleVariants[volume]) || {};
    const keys = ['box_logo_yellow', 'box_logo_black', 'box_nologo_black', 'box_nologo_white', 'no_box', 'plain'];
    keys.forEach(k => {
        const q = buckets[k] || 0;
        const opt = document.createElement('option');
        opt.value = k;
        opt.textContent = (VARIANT_LABELS[k] || k) + ' — ' + q + ' in stock';
        if (k === variantSelect.dataset.preselect) opt.selected = true;
        variantSelect.appendChild(opt);
    });

    variantField.classList.remove('hidden');
    variantSelect.setAttribute('required', 'required');
}

function hideVariantField() {
    const variantField = document.getElementById('bottle-variant-field');
    const variantSelect = document.getElementById('bottle_variant');
    variantField.classList.add('hidden');
    variantSelect.removeAttribute('required');
}

document.getElementById('bottle_volume').addEventListener('change', refreshVariantOptions);

// Rebuild the product list from the chosen category, then restore the
// previously submitted product after a validation error.
(function init () {
    filterProducts();

    const productId = @json(old('product_id')) || '';
    if (productId) {
        document.getElementById('product_id').value = productId;
    }

    toggleBottleVolume();
})();
</script>
@endpush
@endsection
