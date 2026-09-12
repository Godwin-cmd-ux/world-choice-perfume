@extends('stock-manager.layouts.app')
@section('title', 'Edit Oil Fragrance')
@section('header', 'Edit Oil Fragrance')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                <i class="fas fa-edit text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">{{ $record->name }}</h3>
                <p class="text-sm text-gray-500">{{ $activeBranchName ?? 'Branch' }} · Update available oil fragrance stock</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.oil-fragrance.update', $record->id) }}">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fragrance Name</label>
                <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{ $record->name }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Volume</label>
                <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{ $record->volume === 500 ? '500ml' : ($record->volume === 1000 ? '1000ml' : ($record->volume ?? '-')) }}
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Available Quantity (bottles) *</label>
                <input type="number" name="quantity" required min="0" value="{{ old('quantity', $record->quantity) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Number of bottles">
                @error('quantity')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                @if($errors->has('error') && !$errors->has('quantity'))
                    <p class="text-xs text-red-600 mt-1">{{ $errors->first('error') }}</p>
                @endif
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
                <a href="{{ route('stock-manager.oil-fragrance') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection