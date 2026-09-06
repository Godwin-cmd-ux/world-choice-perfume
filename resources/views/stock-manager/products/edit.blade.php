@extends('stock-manager.layouts.app')
@section('title', 'Edit Product')
@section('header', 'Edit: ' . $product->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.products.update', $product->id) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $product->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand <span class="text-gray-400 font-normal">(optional — editable)</span></label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand) }}"
                        placeholder="Leave blank if not applicable"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-gray-400 mt-1">Brand is optional and can be updated at any time.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-all {{ old('category', $product->category) === 'Oil Fragrance' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" name="category" value="Oil Fragrance" {{ old('category', $product->category) === 'Oil Fragrance' ? 'checked' : '' }} required
                                class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Oil Fragrance</p>
                                <p class="text-[11px] text-gray-400">Custom oil-based fragrances</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-all {{ old('category', $product->category) === 'Brand Perfume' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" name="category" value="Brand Perfume" {{ old('category', $product->category) === 'Brand Perfume' ? 'checked' : '' }} required
                                class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Brand Perfume</p>
                                <p class="text-[11px] text-gray-400">Branded perfume products</p>
                            </div>
                        </label>
                    </div>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach($product->images as $img)
                            <div class="relative">
                                <img src="{{ $img->image_url }}" class="w-20 h-20 rounded object-cover border border-gray-200">
                                <form action="{{ route('stock-manager.products.remove-image', $img) }}" method="POST" class="absolute -top-1 -right-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-5 h-5 bg-red-500 text-white rounded-full text-xs hover:bg-red-600"><i class="fas fa-times"></i></button>
                                </form>
                            </div>
                        @endforeach
                        @if($product->images->isEmpty())
                            <p class="text-sm text-gray-400">No images uploaded.</p>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add More Images</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-gray-400 mt-1">Max 2MB each.</p>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <a href="{{ route('stock-manager.products.index') }}"
                    class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm font-medium">Cancel</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Update Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
