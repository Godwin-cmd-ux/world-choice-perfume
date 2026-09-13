@extends('stock-manager.layouts.app')
@section('title', 'Bottle Stock In')
@section('header', 'Bottle Stock In')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                <i class="fas fa-plus text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Add Bottle Stock</h3>
                <p class="text-sm text-gray-500">Record new bottles received</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.bottle-stock-in') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                <select name="volume" id="volume" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Volume</option>
                    @foreach($volumes as $volume)
                        <option value="{{ $volume }}" {{ old('volume') === $volume ? 'selected' : '' }}>{{ $volume }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">30ml, 50ml &amp; 100ml bottles are tracked by box, logo and color details. 6ml &amp; 12ml bottles are recorded without those details.</p>
                @error('volume')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" required min="1" value="{{ old('quantity') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Enter number of bottles">
                @error('quantity')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Details cascade (30ml / 50ml / 100ml) --}}
            <div id="details-section" class="hidden">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bottle Box *</label>
                    <div class="flex gap-3">
                        <label class="radio-option box-option flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="has_box" value="yes" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('has_box') === 'yes' ? 'checked' : '' }}>
                            <i class="fas fa-box text-gray-400 text-base"></i>
                            <span class="text-sm font-medium">With Box</span>
                        </label>
                        <label class="radio-option box-option flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="has_box" value="no" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('has_box') === 'no' ? 'checked' : '' }}>
                            <i class="fas fa-box-open text-gray-400 text-base"></i>
                            <span class="text-sm font-medium">Without Box</span>
                        </label>
                    </div>
                    @error('has_box')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="logo-section" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo *</label>
                    <div class="flex gap-3">
                        <label class="radio-option logo-option flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="has_logo" value="yes" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('has_logo') === 'yes' ? 'checked' : '' }}>
                            <i class="fas fa-tag text-gray-400 text-base"></i>
                            <span class="text-sm font-medium">With Logo</span>
                        </label>
                        <label class="radio-option logo-option flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="has_logo" value="no" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('has_logo') === 'no' ? 'checked' : '' }}>
                            <i class="fas fa-tag text-gray-300 text-base"></i>
                            <span class="text-sm font-medium">No Logo</span>
                        </label>
                    </div>
                    @error('has_logo')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="color-section" class="mb-4 hidden">
                    <label id="color-label" class="block text-sm font-medium text-gray-700 mb-2">Logo Color *</label>
                    <div class="flex gap-3">
                        <label class="radio-option color-option color-yellow flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="logo_color" value="yellow" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('logo_color') === 'yellow' ? 'checked' : '' }}>
                            <span class="w-5 h-5 rounded-full bg-yellow-400 border border-yellow-500"></span>
                            <span class="text-sm font-medium">Yellow</span>
                        </label>
                        <label class="radio-option color-option color-black flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="logo_color" value="black" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('logo_color') === 'black' ? 'checked' : '' }}>
                            <span class="w-5 h-5 rounded-full bg-gray-900 border border-gray-600"></span>
                            <span class="text-sm font-medium">Black</span>
                        </label>
                        <label class="radio-option color-option color-white flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all border-gray-200 hover:border-gray-300">
                            <input type="radio" name="logo_color" value="white" class="absolute opacity-0 w-0 h-0 p-0 m-0 border-0 overflow-hidden clip-rect" {{ old('logo_color') === 'white' ? 'checked' : '' }}>
                            <span class="w-5 h-5 rounded-full bg-white border-2 border-gray-300"></span>
                            <span class="text-sm font-medium">White</span>
                        </label>
                    </div>
                    @error('logo_color')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason" value="{{ old('reason') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. New shipment received">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Record Stock In
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
const volumeSelect = document.getElementById('volume');
const detailsSection = document.getElementById('details-section');
const logoSection = document.getElementById('logo-section');
const colorSection = document.getElementById('color-section');

function syncOption(input) {
    document.querySelectorAll(`input[name="${input.name}"]`).forEach(radio => {
        const el = radio.closest('.radio-option');
        if (!el) return;
        el.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-yellow-400', 'bg-yellow-50', 'border-gray-500');
        if (radio.checked) {
            el.classList.add('border-emerald-500', 'bg-emerald-50');
        } else {
            el.classList.add('border-gray-200');
        }
    });
}

function selectedValue(name) {
    const checked = document.querySelector(`input[name="${name}"]:checked`);
    return checked ? checked.value : null;
}

function clearGroup(name) {
    document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
        r.checked = false;
        syncOption(r);
    });
}

function updateCascade() {
    const showDetails = DETAIL_VOLUMES.includes(volumeSelect.value);
    detailsSection.classList.toggle('hidden', !showDetails);
    if (!showDetails) {
        clearGroup('has_box');
        clearGroup('has_logo');
        clearGroup('logo_color');
        logoSection.classList.add('hidden');
        colorSection.classList.add('hidden');
        return;
    }

    const hasBox = selectedValue('has_box');
    if (hasBox !== 'yes') {
        clearGroup('has_logo');
        clearGroup('logo_color');
        logoSection.classList.add('hidden');
        colorSection.classList.add('hidden');
        return;
    }

    logoSection.classList.remove('hidden');

    const hasLogo = selectedValue('has_logo');
    if (hasLogo !== 'yes' && hasLogo !== 'no') {
        clearGroup('logo_color');
        colorSection.classList.add('hidden');
        return;
    }

    colorSection.classList.remove('hidden');
    document.getElementById('color-label').textContent = hasLogo === 'yes' ? 'Logo Color *' : 'Marking Color *';

    // With logo -> Yellow / Black; No logo -> Black / White.
    document.querySelector('.color-option.color-yellow').classList.toggle('hidden', hasLogo !== 'yes');
    document.querySelector('.color-black').classList.toggle('hidden', false);
    document.querySelector('.color-white').classList.toggle('hidden', hasLogo !== 'no');

    if (hasLogo === 'yes' && selectedValue('logo_color') === 'white') {
        clearGroup('logo_color');
    }
    if (hasLogo === 'no' && selectedValue('logo_color') === 'yellow') {
        clearGroup('logo_color');
    }
}

document.querySelectorAll('.radio-option input[type="radio"]').forEach(input => {
    input.addEventListener('change', () => {
        syncOption(input);
        updateCascade();
    });
});

volumeSelect.addEventListener('change', updateCascade);

// Restore server-side validation state on load.
updateCascade();
document.querySelectorAll('.radio-option input[type="radio"]:checked').forEach(input => {
    syncOption(input);
});
</script>
@endpush
@endsection