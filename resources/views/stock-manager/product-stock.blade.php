@extends('stock-manager.layouts.app')
@section('title', 'Product Stock')
@section('header', 'Product Stock')

@section('header-actions')
    <form method="GET" action="{{ route('stock-manager.product-stock') }}" class="flex items-center gap-2 mr-2">
        <input type="text" name="search" value="{{ request('search') }}"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            placeholder="Search by product or brand…">
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-2 rounded-lg text-sm">
            <i class="fas fa-search"></i>
        </button>
        @if(request('search'))
            <a href="{{ route('stock-manager.product-stock') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.product-stock.entry') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-plus mr-1"></i> Add Stock
        </a>
    @endif
    <a href="{{ route('stock-manager.product-stock-movements') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-exchange-alt mr-1"></i> Movements
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex justify-between items-center">
        <span class="text-sm text-gray-500">Total Stock Value:</span>
        <span class="text-lg font-bold text-emerald-700">TZS {{ number_format($totalValue) }}</span>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Product</th>
                <th class="text-right px-4">Quantity</th>
                <th class="text-right px-4">Selling Price</th>
                <th class="text-right px-4">Stock Value</th>
                <th class="text-left px-4">Category</th>
                <th class="text-left px-4">Last Received</th>
                <th class="text-right px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                    @php
                        $stockValue = ($stock->quantity ?? 0) * ($stock->selling_price ?? 0);
                        $lowQty = ($stock->quantity <= 5);
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $stock->product->name }}</td>
                        <td class="px-4 text-right">
                            <span class="{{ $lowQty ? 'text-red-600 font-bold' : '' }}">{{ $stock->quantity }}</span>
                        </td>
                        <td class="px-4 text-right">TZS {{ number_format($stock->selling_price) }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($stockValue) }}</td>
                        <td class="px-4">@if(!empty($stock->category))<span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800">{{ $stock->category }}</span>@else <span class="text-gray-400">—</span> @endif</td>
                        <td class="px-4 text-gray-500">{{ $stock->date_received ? \Carbon\Carbon::parse($stock->date_received)->format('M d, Y') : '-' }}</td>
                        <td class="px-4 text-right">
                            @if(!($inCrossBranch ?? false))
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('stock-manager.product-stock.update', $stock->id) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" value="{{ $stock->quantity }}" class="w-14 px-1 py-1 border border-gray-300 rounded text-right text-xs text-center focus:ring-2 focus:ring-emerald-500" min="0">
                                        <input type="number" name="selling_price" value="{{ $stock->selling_price }}" step="0.01" class="w-16 px-1 py-1 border border-gray-300 rounded text-right text-xs text-right focus:ring-2 focus:ring-emerald-500" min="0">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded text-xs font-medium">
                                            <i class="fas fa-pen mr-0.5"></i> Edit
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('stock-manager.product-stock.destroy', $stock->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs" title="Delete" data-confirm="Delete stock record for {{ $stock->product->name }}?">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No stock records yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
