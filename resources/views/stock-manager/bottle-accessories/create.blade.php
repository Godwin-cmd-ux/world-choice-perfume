@extends('stock-manager.layouts.app')
@section('title', 'Add Bottle Accessories')
@section('header', 'Add Bottle Accessories')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.bottle-accessories.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Accessory Type *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all {{ old('type') === 'straws' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="type" value="straws" class="hidden" {{ old('type') === 'straws' ? 'checked' : '' }} required onchange="this.closest('label').className = this.checked ? 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-emerald-500 bg-emerald-50 rounded-lg cursor-pointer transition-all' : this.closest('label').className = 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all'">
                        <i class="fas fa-minus-circle text-emerald-500"></i>
                        <span class="text-sm font-medium">Straws</span>
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all {{ old('type') === 'bottlenecks' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="type" value="bottlenecks" class="hidden" {{ old('type') === 'bottlenecks' ? 'checked' : '' }} onchange="this.closest('label').className = this.checked ? 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-emerald-500 bg-emerald-50 rounded-lg cursor-pointer transition-all' : this.closest('label').className = 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all'">
                        <i class="fas fa-circle-notch text-emerald-500"></i>
                        <span class="text-sm font-medium">Bottle Necks</span>
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all {{ old('type') === 'bottle_tops' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="type" value="bottle_tops" class="hidden" {{ old('type') === 'bottle_tops' ? 'checked' : '' }} onchange="this.closest('label').className = this.checked ? 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-emerald-500 bg-emerald-50 rounded-lg cursor-pointer transition-all' : this.closest('label').className = 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all'">
                        <i class="fas fa-cap-check text-emerald-500"></i>
                        <span class="text-sm font-medium">Bottle Tops</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Color *</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all {{ old('color') === 'silver' ? 'border-gray-400 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="color" value="silver" class="hidden" {{ old('color') === 'silver' ? 'checked' : '' }} required onchange="this.closest('label').className = this.checked ? 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-400 bg-gray-50 rounded-lg cursor-pointer transition-all' : this.closest('label').className = 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all'">
                        <span class="w-4 h-4 rounded-full bg-gray-300 border border-gray-400"></span>
                        <span class="text-sm font-medium">Silver</span>
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg cursor-pointer transition-all {{ old('color') === 'gold' ? 'border-amber-400 bg-amber-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="color" value="gold" class="hidden" {{ old('color') === 'gold' ? 'checked' : '' }} onchange="this.closest('label').className = this.checked ? 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-amber-400 bg-amber-50 rounded-lg cursor-pointer transition-all' : this.closest('label').className = 'flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all'">
                        <span class="w-4 h-4 rounded-full bg-amber-400 border border-amber-500"></span>
                        <span class="text-sm font-medium">Gold</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (Packets) *</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Counted in packets</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason" value="{{ old('reason') }}" placeholder="e.g. New shipment"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Stock In
                </button>
                <a href="{{ route('stock-manager.bottle-accessories.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
