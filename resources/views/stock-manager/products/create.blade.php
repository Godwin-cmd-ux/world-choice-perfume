@extends('stock-manager.layouts.app')
@section('title', 'Add Product')
@section('header', 'Add Product')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('stock-manager.products.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                        placeholder="Leave blank if not applicable"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-gray-400 mt-1">You can add or update the brand later when editing the product.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-all {{ old('category') === 'Oil Fragrance' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" name="category" value="Oil Fragrance" {{ old('category') === 'Oil Fragrance' ? 'checked' : '' }} required
                                class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Oil Fragrance</p>
                                <p class="text-[11px] text-gray-400">Custom oil-based fragrances</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-all {{ old('category') === 'Brand Perfume' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" name="category" value="Brand Perfume" {{ old('category') === 'Brand Perfume' ? 'checked' : '' }} required
                                class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Brand Perfume</p>
                                <p class="text-[11px] text-gray-400">Branded perfume products</p>
                            </div>
                        </label>
                    </div>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sex Category</label>
                    <select name="sex_category"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 @error('sex_category') border-red-500 @enderror">
                        <option value="">Select a category</option>
                        @foreach(['male' => 'Male', 'female' => 'Female', 'unisex' => 'Unisex', 'accessories' => 'Accessories', 'gift sets' => 'Gift Sets'] as $value => $label)
                            <option value="{{ $value }}" {{ old('sex_category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Helps customers filter products on the website shop.</p>
                    @error('sex_category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Images</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <p class="text-[11px] text-gray-400 mt-1">You can upload multiple images. Max 2MB each.</p>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <a href="{{ route('stock-manager.products.index') }}"
                    class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm font-medium">Cancel</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Create Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
