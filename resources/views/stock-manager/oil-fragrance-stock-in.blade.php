@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance Stock In')
@section('header', 'Oil Fragrance Stock In')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                <i class="fas fa-plus text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Import Oil Fragrance</h3>
                <p class="text-sm text-gray-500">Record new oil fragrance bottles received</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.oil-fragrance-stock-in') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fragrance Name *</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. Rose Oil, Vanilla Essence">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (bottles) *</label>
                <input type="number" name="quantity" required min="1"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Enter number of bottles">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. New shipment from supplier">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bottle Volume *</label>
                <select name="bottle_volume" id="bottle_volume" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Volume</option>
                    <option value="500" {{ old('bottle_volume') == '500' ? 'selected' : '' }}>500ml</option>
                    <option value="1000" {{ old('bottle_volume') == '1000' ? 'selected' : '' }}>1000ml</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Record Stock In
                </button>

                @error('bottle_volume')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <a href="{{ route('stock-manager.oil-fragrance') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
