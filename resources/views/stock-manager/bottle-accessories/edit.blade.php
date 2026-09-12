@extends('stock-manager.layouts.app')
@section('title', 'Edit Bottle Accessory')
@section('header', 'Edit Bottle Accessory')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center">
                <i class="fas fa-edit text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 capitalize">{{ str_replace('_', ' ', $item->type) }} · {{ ucfirst($item->color) }}</h3>
                <p class="text-sm text-gray-500">{{ $activeBranchName ?? 'Branch' }} · Update available packets</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.bottle-accessories.update', $item->id) }}">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 capitalize">
                        {{ str_replace('_', ' ', $item->type) }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full {{ $item->color === 'gold' ? 'bg-amber-400' : 'bg-gray-300' }}"></span>
                        {{ ucfirst($item->color) }}
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Available Packets *</label>
                <input type="number" name="quantity" required min="0" value="{{ old('quantity', $item->quantity) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Number of packets">
                @error('quantity')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
                <a href="{{ route('stock-manager.bottle-accessories.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection