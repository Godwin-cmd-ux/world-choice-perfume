@extends('stock-manager.layouts.app')
@section('title', 'Products')
@section('header', 'Products')
@section('header-actions')
    <a href="{{ route('stock-manager.products.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> Add Product
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-700">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Image</th>
                    <th class="text-left px-4">Name</th>
                    <th class="text-left px-4">Brand</th>
                    <th class="text-left px-4">Category</th>
                    <th class="text-center px-4">Status</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($product->images->first())
                                <img src="{{ $product->images->first()->image_url }}" class="w-10 h-10 rounded object-cover">
                            @else
                                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>
                            @endif
                        </td>
                        <td class="px-4 font-medium text-gray-800">{{ $product->name }}</td>
                        <td class="px-4 text-gray-500">{{ $product->brand ?? '-' }}</td>
                        <td class="px-4">
                            @if($product->category === 'Oil Fragrance')
                                <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">Oil Fragrance</span>
                            @elseif($product->category === 'Brand Perfume')
                                <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Brand Perfume</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('stock-manager.products.edit', $product->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No products yet. Add your first product!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
