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
                    placeholder="Enter number of bottles">
            </div>

            <!-- Bottle Category Mapping -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bottle State *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all bottle-state-option {{ old('has_logo') === 'yes' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="yes" onclick="setBottleState('yes')">
                        <input type="radio" name="has_logo" value="yes" class="hidden" {{ old('has_logo') === 'yes' ? 'checked' : '' }}>
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <span class="text-sm font-medium">Has Logo</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all bottle-state-option {{ old('has_logo') === 'no' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="no" onclick="setBottleState('no')">
                        <input type="radio" name="has_logo" value="no" class="hidden" {{ old('has_logo') === 'no' ? 'checked' : '' }}>
                        <i class="fas fa-times-circle text-gray-400 text-lg"></i>
                        <span class="text-sm font-medium">No Logo</span>
                    </label>
                </div>
            </div>

            <!-- Logo Color (shown if has_logo = yes) -->
            <div id="logo-color-field" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo Color *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all logo-color-option {{ old('logo_color') === 'yellow' ? 'border-yellow-400 bg-yellow-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="yellow" onclick="setLogoColor('yellow')">
                        <input type="radio" name="logo_color" value="yellow" class="hidden" {{ old('logo_color') === 'yellow' ? 'checked' : '' }}>
                        <span class="w-5 h-5 rounded-full bg-yellow-400 border border-yellow-500"></span>
                        <span class="text-sm font-medium">Yellow</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all logo-color-option {{ old('logo_color') === 'black' ? 'border-gray-600 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="black" onclick="setLogoColor('black')">
                        <input type="radio" name="logo_color" value="black" class="hidden" {{ old('logo_color') === 'black' ? 'checked' : '' }}>
                        <span class="w-5 h-5 rounded-full bg-gray-800 border border-gray-900"></span>
                        <span class="text-sm font-medium">Black</span>
                    </label>
                </div>
            </div>

            <!-- Has Box (shown if has_logo = no) -->
            <div id="has-box-field" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Has Box? *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all has-box-option {{ old('has_box') === 'yes' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="yes" onclick="setHasBox('yes')">
                        <input type="radio" name="has_box" value="yes" class="hidden" {{ old('has_box') === 'yes' ? 'checked' : '' }}>
                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                        <span class="text-sm font-medium">Has Box</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all has-box-option {{ old('has_box') === 'no' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="no" onclick="setHasBox('no')">
                        <input type="radio" name="has_box" value="no" class="hidden" {{ old('has_box') === 'no' ? 'checked' : '' }}>
                        <i class="fas fa-times-circle text-gray-400 text-lg"></i>
                        <span class="text-sm font-medium">No Box</span>
                    </label>
                </div>
            </div>

            <!-- Box Color (shown if has_box = yes) -->
            <div id="box-color-field" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Box Color *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all box-color-option {{ old('box_color') === 'black' ? 'border-gray-600 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}" data-value="black" onclick="setBoxColor('black')">
                        <input type="radio" name="box_color" value="black" class="hidden" {{ old('box_color') === 'black' ? 'checked' : '' }}>
                        <span class="w-5 h-5 rounded-full bg-gray-800 border border-gray-900"></span>
                        <span class="text-sm font-medium">Black</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all box-color-option {{ old('box_color') === 'white' ? 'border-gray-300 bg-white' : 'border-gray-200 hover:border-gray-300' }}" data-value="white" onclick="setBoxColor('white')">
                        <input type="radio" name="box_color" value="white" class="hidden" {{ old('box_color') === 'white' ? 'checked' : '' }}>
                        <span class="w-5 h-5 rounded-full bg-white border-2 border-gray-300"></span>
                        <span class="text-sm font-medium">White</span>
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason"
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
let currentState = '{{ old("has_logo") }}';
let currentLogoColor = '{{ old("logo_color") }}';
let currentHasBox = '{{ old("has_box") }}';

function setBottleState(state) {
    currentState = state;
    document.querySelectorAll('.bottle-state-option').forEach(el => {
        const isSelected = el.dataset.value === state;
        el.className = el.className.replace(/border-\w+-\d+|bg-\w+-\d+/, '');
        el.classList.add(isSelected ? 'border-emerald-500' : 'border-gray-200', isSelected ? 'bg-emerald-50' : '');
        el.querySelector('input[type="radio"]').checked = isSelected;
        el.querySelector('i').className = isSelected
            ? 'fas fa-check-circle text-emerald-500 text-lg'
            : (state === 'yes' ? 'fas fa-check-circle text-gray-300 text-lg' : 'fas fa-times-circle text-gray-300 text-lg');
    });

    const logoColorField = document.getElementById('logo-color-field');
    const hasBoxField = document.getElementById('has-box-field');

    if (state === 'yes') {
        logoColorField.classList.remove('hidden');
        hasBoxField.classList.add('hidden');
        document.getElementById('box-color-field').classList.add('hidden');
    } else if (state === 'no') {
        logoColorField.classList.add('hidden');
        hasBoxField.classList.remove('hidden');
    } else {
        logoColorField.classList.add('hidden');
        hasBoxField.classList.add('hidden');
        document.getElementById('box-color-field').classList.add('hidden');
    }
}

function setLogoColor(color) {
    currentLogoColor = color;
    document.querySelectorAll('.logo-color-option').forEach(el => {
        const isSelected = el.dataset.value === color;
        el.className = el.className.replace(/border-\w+-\d+|bg-\w+-\d+/, '');
        el.classList.add(isSelected ? 'border-amber-400' : 'border-gray-200', isSelected ? 'bg-amber-50' : '');
        el.querySelector('input[type="radio"]').checked = isSelected;
    });
}

function setHasBox(val) {
    currentHasBox = val;
    document.querySelectorAll('.has-box-option').forEach(el => {
        const isSelected = el.dataset.value === val;
        el.className = el.className.replace(/border-\w+-\d+|bg-\w+-\d+/, '');
        el.classList.add(isSelected ? 'border-emerald-500' : 'border-gray-200', isSelected ? 'bg-emerald-50' : '');
        el.querySelector('input[type="radio"]').checked = isSelected;
        el.querySelector('i').className = isSelected
            ? 'fas fa-check-circle text-emerald-500 text-lg'
            : (val === 'yes' ? 'fas fa-check-circle text-gray-300 text-lg' : 'fas fa-times-circle text-gray-300 text-lg');
    });

    const boxColorField = document.getElementById('box-color-field');
    if (val === 'yes') {
        boxColorField.classList.remove('hidden');
    } else {
        boxColorField.classList.add('hidden');
    }
}

function setBoxColor(color) {
    document.querySelectorAll('.box-color-option').forEach(el => {
        const isSelected = el.dataset.value === color;
        el.className = el.className.replace(/border-\w+-\d+|bg-\w+-\d+/, '');
        el.classList.add(isSelected ? (color === 'black' ? 'border-gray-600' : 'border-gray-300') : 'border-gray-200', isSelected ? (color === 'white' ? 'bg-white' : 'bg-gray-50') : '');
        el.querySelector('input[type="radio"]').checked = isSelected;
    });
}

// Restore state on page load
if (currentState) setBottleState(currentState);
if (currentLogoColor) setLogoColor(currentLogoColor);
if (currentHasBox) setHasBox(currentHasBox);
</script>
@endpush
@endsection
