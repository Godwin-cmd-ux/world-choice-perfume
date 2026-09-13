@extends('stock-manager.layouts.app')
@section('title', 'Record Broken Bottles')
@section('header', 'Record Broken Bottles')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center">
                <i class="fas fa-broken-image text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Record Broken Bottles</h3>
                <p class="text-sm text-gray-500">Bottles broken before use</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.bottle-broken') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                <select name="volume" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Volume</option>
                    @foreach($volumes as $volume)
                        <option value="{{ $volume }}">{{ $volume }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" required min="1"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Enter number of broken bottles">
            </div>

            <div id="variant-field" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Variant Details</label>
                <select name="variant" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">Any available variant</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Choose the box/logo/color bucket these bottles belonged to.</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. Broken during transport">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Record Broken
                </button>
                <a href="{{ route('stock-manager.bottle-stock') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const DETAIL_VOLUMES = ['30ml', '50ml', '100ml'];
const BROKEN_VARIANTS = @json($bottleVariants ?? []);
const BROKEN_LABELS = {
    'box_logo_yellow': 'With Box · With Logo · Yellow',
    'box_logo_black': 'With Box · With Logo · Black',
    'box_nologo_black': 'With Box · No Logo · Black',
    'box_nologo_white': 'With Box · No Logo · White',
    'no_box': 'Without Box',
    'plain': 'No details',
};
let brokenVolume = document.querySelector('select[name="volume"]');
let brokenVariantField = document.getElementById('variant-field');
let brokenVariantSelect = brokenVariantField.querySelector('select[name="variant"]');

function refreshBrokenVariants() {
    const vol = brokenVolume.value;
    const isDetails = DETAIL_VOLUMES.includes(vol);
    brokenVariantField.classList.toggle('hidden', !isDetails);
    if (!isDetails) {
        brokenVariantSelect.value = '';
        brokenVariantSelect.innerHTML = '<option value="">Any available variant</option>';
        return;
    }
    const buckets = BROKEN_VARIANTS[vol.replace('ml', '')] || {};
    let html = '<option value="">Any available variant</option>';
    const keys = ['box_logo_yellow', 'box_logo_black', 'box_nologo_black', 'box_nologo_white', 'no_box', 'plain'];
    keys.forEach(k => {
        const q = buckets[k] || 0;
        if (q > 0) {
            html += `<option value="${k}">${BROKEN_LABELS[k] || k} (${q} in stock)</option>`;
        }
    });
    brokenVariantSelect.innerHTML = html;
}

brokenVolume.addEventListener('change', refreshBrokenVariants);
refreshBrokenVariants();
</script>
@endpush
@endsection
