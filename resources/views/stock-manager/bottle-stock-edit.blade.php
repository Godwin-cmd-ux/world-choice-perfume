@extends('stock-manager.layouts.app')
@section('title', 'Edit Bottle Stock')
@section('header', 'Edit Bottle Stock')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center">
                <i class="fas fa-edit text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Edit {{ $record->volume }}</h3>
                <p class="text-sm text-gray-500">{{ $activeBranchName ?? 'Branch' }} · Update available stock and bottle details</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.bottle-stock.update', $record->id) }}">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Volume</label>
                <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{ $record->volume }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Available Quantity *</label>
                <input type="number" name="quantity" required min="0" value="{{ old('quantity', $record->quantity) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Number of bottles">
                @error('quantity')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Has Logo</label>
                    <select name="has_logo"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <option value="">Select</option>
                        <option value="yes" {{ old('has_logo', $record->has_logo) === 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('has_logo', $record->has_logo) === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo Color</label>
                    <select name="logo_color"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <option value="">Select</option>
                        <option value="yellow" {{ old('logo_color', $record->logo_color) === 'yellow' ? 'selected' : '' }}>Yellow</option>
                        <option value="black" {{ old('logo_color', $record->logo_color) === 'black' ? 'selected' : '' }}>Black</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Has Box</label>
                    <select name="has_box"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <option value="">Select</option>
                        <option value="yes" {{ old('has_box', $record->has_box) === 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ old('has_box', $record->has_box) === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Box Color</label>
                    <select name="box_color"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <option value="">Select</option>
                        <option value="black" {{ old('box_color', $record->box_color) === 'black' ? 'selected' : '' }}>Black</option>
                        <option value="white" {{ old('box_color', $record->box_color) === 'white' ? 'selected' : '' }}>White</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
                <a href="{{ route('stock-manager.bottle-stock') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection