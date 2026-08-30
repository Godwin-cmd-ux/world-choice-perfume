@extends('layouts.app')
@section('title', 'Add Stock')
@section('header', 'Add Stock Entry')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('branch-admin.stock.store') }}" class="bg-white rounded-xl shadow p-6">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select name="product_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('product_id') border-red-500 @enderror">
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} {{ $product->brand ? "({$product->brand})" : '' }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('quantity') border-red-500 @enderror">
                    @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Received *</label>
                    <input type="date" name="date_received" value="{{ old('date_received', date('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buying Cost (per unit) *</label>
                    <input type="number" step="0.01" name="buying_cost" value="{{ old('buying_cost') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('buying_cost') border-red-500 @enderror">
                    @error('buying_cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (per unit) *</label>
                    <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('selling_price') border-red-500 @enderror">
                    @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Select --</option>
                        <option value="Oil Fragrance" {{ old('category') === 'Oil Fragrance' ? 'selected' : '' }}>Oil Fragrance</option>
                        <option value="Brand Perfume" {{ old('category') === 'Brand Perfume' ? 'selected' : '' }}>Brand Perfume</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <a href="{{ route('branch-admin.stock.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">Add Stock</button>
        </div>
    </form>
</div>
@endsection
