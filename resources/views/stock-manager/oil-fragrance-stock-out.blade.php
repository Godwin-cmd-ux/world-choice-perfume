@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance Stock Out')
@section('header', 'Oil Fragrance Stock Out')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                <i class="fas fa-arrow-right text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Use Oil Fragrance</h3>
                <p class="text-sm text-gray-500">Record oil fragrance used for production</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.oil-fragrance-stock-out') }}" id="oil-fragrance-form">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Fragrance *</label>
                <input type="text" id="fragrance-search" placeholder="Type to search fragrances..."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                    autocomplete="off">
            </div>

            <div class="mb-4" id="fragrance-results">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Fragrance *</label>
                <select name="product_id" id="product_id" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    @if($errors->has('product_id')) aria-invalid="true" @endif
                    {{ old('product_id') ? 'disabled' : '' }}>
                    <option value="">Select Fragrance</option>
                    @foreach($oilProducts as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}
                            data-name="{{ strtolower($product->name) }}"
                            data-brand="{{ strtolower($product->brand ?? '') }}">
                            {{ $product->name }} ({{ $product->brand ?? 'No Brand' }}) - {{ $stockByProduct[$product->name] ?? 0 }} in stock
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (bottles) *</label>
                <input type="number" name="quantity" required min="1"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Enter number of bottles used"
                    value="{{ old('quantity') }}">
                @error('quantity')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. Used for perfume batch #123"
                    value="{{ old('reason') }}">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Volume *</label>
                <select name="bottle_volume" id="bottle_volume" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Volume</option>
                    <option value="500" {{ old('bottle_volume') == '500' ? 'selected' : '' }}>500ml</option>
                    <option value="1000" {{ old('bottle_volume') == '1000' ? 'selected' : '' }}>1000ml</option>
                </select>
                @error('bottle_volume')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Record Stock Out
                </button>
                <a href="{{ route('stock-manager.oil-fragrance') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const searchInput = document.getElementById('fragrance-search');
    const resultsSelect = document.getElementById('product_id');
    const form = document.getElementById('oil-fragrance-form');
    const allOptions = Array.from(resultsSelect.options).filter(o => o.value !== '');

    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        if (!q) {
            allOptions.forEach(o => o.style.display = '');
            resultsSelect.disabled = false;
            return;
        }
        let matched = false;
        allOptions.forEach(o => {
            const name = o.dataset.name || '';
            const brand = o.dataset.brand || '';
            const match = name.includes(q) || brand.includes(q);
            o.style.display = match ? '' : 'none';
            if (match) matched = true;
        });
        resultsSelect.disabled = !matched;
        // If currently selected option is hidden by the filter, clear it
        if (resultsSelect.value) {
            const selectedOption = resultsSelect.options[resultsSelect.selectedIndex];
            if (selectedOption.style.display === 'none') {
                resultsSelect.value = '';
            }
        }
    });

    // On load, if old(product_id) exists, select it but still allow search filtering.
    const savedId = '{{ old("product_id") }}';
    if (savedId) {
        const target = allOptions.find(o => o.value === savedId);
        if (target) {
            // Set search text to match selected product name for filtering
            const name = target.dataset.name || '';
            if (name) {
                searchInput.value = name;
            }
        }
    }
})();
</script>
@endpush
@endsection