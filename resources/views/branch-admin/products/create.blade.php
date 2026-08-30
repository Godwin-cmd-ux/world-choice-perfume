@extends('layouts.app')
@section('title', 'Add Product')
@section('header', 'Add Product')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('branch-admin.products.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Images</label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <a href="{{ route('branch-admin.products.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg font-medium">Create Product</button>
        </div>
    </form>
</div>
@endsection
