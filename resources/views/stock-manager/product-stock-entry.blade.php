@extends('stock-manager.layouts.app')
@section('title', 'Add Product Stock')
@section('header', 'Add Product Stock')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.product-stock-entry.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select name="product_id" id="product_id" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    onchange="autoFillCategory()">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-category="{{ $product->category ?? '' }}">{{ $product->name }} — {{ $product->brand }}</option>
                    @endforeach
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

            <div class="mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category-select" onchange="toggleBottleVolume()"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Select Category</option>
                        <option value="Oil Fragrance" {{ old('category') == 'Oil Fragrance' ? 'selected' : '' }}>Oil Fragrance</option>
                        <option value="Brand Perfume" {{ old('category') == 'Brand Perfume' ? 'selected' : '' }}>Brand Perfume</option>
                    </select>
                    @error('category')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
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

            <div class="flex gap-3">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
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
const categoryMap = @json($categoryMap ?? []);

function autoFillCategory() {
    const productId = document.getElementById('product_id').value;
    const categorySelect = document.getElementById('category-select');

    if (productId && categoryMap[productId]) {
        categorySelect.value = categoryMap[productId];
    } else {
        categorySelect.value = '';
    }

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
    }
}

// Run on load in case a value was repopulated after a validation error
autoFillCategory();
</script>
@endpush
@endsection
