@extends('stock-manager.layouts.app')
@section('title', 'Low Stock Products')
@section('header', 'Low Stock Products')
@section('header-subtitle', 'Products at ' . $threshold . ' units or fewer' . (($activeBranchName ?? null) ? ' — ' . $activeBranchName : ''))

@section('header-actions')
    <a href="{{ route('stock-manager.product-stock') }}" class="border border-gray-300 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-arrow-left mr-1"></i> Product Stock
    </a>
@endsection

@section('content')
@if($inCrossBranch ?? false)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6 text-sm">
        <i class="fas fa-eye mr-2"></i>
        You are monitoring <strong>{{ $activeBranchName }}</strong> — read-only. Stock In is disabled for this branch.
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
        <span class="text-sm text-gray-500">
            <strong class="text-gray-800">{{ count($rows) }}</strong>
            product{{ count($rows) === 1 ? '' : 's' }} need{{ count($rows) === 1 ? 's' : '' }} restocking
        </span>
        <span class="text-xs text-gray-400">
            Lowest first — anything at {{ $threshold }} units or fewer is listed here.
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">#</th>
                    <th class="text-left px-4">Product</th>
                    <th class="text-left px-4">Brand</th>
                    <th class="text-left px-4">Category</th>
                    <th class="text-right px-4">In Stock</th>
                    <th class="text-right px-4">Selling Price</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $idx => $row)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="px-4 font-medium text-gray-800">{{ $row['name'] }}</td>
                        <td class="px-4 text-gray-500">{{ $row['brand'] ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $row['category'] ?? '—' }}</td>
                        <td class="px-4 text-right">
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold {{ $row['quantity'] <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ number_format($row['quantity']) }}
                            </span>
                        </td>
                        <td class="px-4 text-right text-gray-500">TZS {{ number_format($row['selling_price']) }}</td>
                        <td class="px-4 text-center">
                            @if($inCrossBranch ?? false)
                                <span class="text-xs text-gray-300">Read-only</span>
                            @else
                                {{-- The entry form lands with this product already
                                     chosen, so restocking is one click. --}}
                                <a href="{{ route('stock-manager.product-stock.entry', ['product_id' => $row['product_id']]) }}"
                                   style="background-color: #F89A1E;"
                                   class="inline-flex items-center gap-1.5 hover:opacity-90 text-white px-3 py-1.5 rounded-lg text-xs font-medium">
                                    <i class="fas fa-plus text-[10px]"></i> Stock In
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-check text-emerald-500"></i>
                                </div>
                                <p class="text-sm text-gray-500">Nothing is low on stock.</p>
                                <p class="text-xs text-gray-400 mt-1">Every product is above {{ $threshold }} units.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
