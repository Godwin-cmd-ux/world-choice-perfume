@extends('layouts.app')
@section('title', 'Edit Product')
@section('header', 'Edit: ' . $product->name)

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('branch-admin.products.update', $product->id) }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6">
        @csrf @method('PUT')
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" name="category" value="{{ old('category', $product->category) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="rounded">
                <span class="text-sm text-gray-700">Active</span>
            </label>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Images</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach($product->images as $img)
                        <div class="relative">
                            <img src="{{ $img->image_url }}" class="w-20 h-20 rounded object-cover">
                            <form action="{{ route('branch-admin.products.remove-image', $img) }}" method="POST" class="absolute -top-1 -right-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-5 h-5 bg-red-500 text-white rounded-full text-xs"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Add More Images</label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <a href="{{ route('branch-admin.products.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg font-medium">Update Product</button>
        </div>
    </form>
</div>
@endsection
