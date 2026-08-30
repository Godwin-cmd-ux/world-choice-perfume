@extends('layouts.app')
@section('title', 'Products')
@section('header', 'Products')
@section('header-actions')
    <a href="{{ route('branch-admin.products.create') }}" class="bg-amber-700 hover:bg-amber-800 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> Add Product
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="flex-1 px-3 py-2 border rounded-lg text-sm">
            <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Image</th>
                <th class="text-left px-4">Name</th>
                <th class="text-left px-4">Brand</th>
                <th class="text-left px-4">Category</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($product->images->first())
                                <img src="{{ $product->images->first()->image_url }}" class="w-10 h-10 rounded object-cover">
                            @else
                                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>
                            @endif
                        </td>
                        <td class="px-4 font-medium">{{ $product->name }}</td>
                        <td class="px-4 text-gray-500">{{ $product->brand ?? '-' }}</td>
                        <td class="px-4 text-gray-500">{{ $product->category ?? '-' }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('branch-admin.products.edit', $product->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-edit"></i></a>
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
