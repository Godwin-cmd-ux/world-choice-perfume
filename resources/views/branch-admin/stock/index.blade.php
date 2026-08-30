@extends('layouts.app')
@section('title', 'Stock Management')
@section('header', 'Stock Management')
@section('header-actions')
    <a href="{{ route('branch-admin.stock.entry') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-plus mr-1"></i> Add Stock
    </a>
    <a href="{{ route('branch-admin.stock.movements') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-exchange-alt mr-1"></i> Movements
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex justify-between items-center">
        <span class="text-sm text-gray-500">Total Stock Value:</span>
        <span class="text-lg font-bold text-amber-700">TZS {{ number_format($totalValue) }}</span>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Product</th>
                <th class="text-right px-4">Quantity</th>
                <th class="text-right px-4">Buying Cost</th>
                <th class="text-right px-4">Selling Price</th>
                <th class="text-right px-4">Stock Value</th>
                <th class="text-left px-4">Supplier</th>
                <th class="text-left px-4">Category</th>
                <th class="text-left px-4">Last Received</th>
            </tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $stock->product->name }}</td>
                        <td class="px-4 text-right">
                            <span class="{{ $stock->quantity <= 5 ? 'text-red-600 font-bold' : '' }}">{{ $stock->quantity }}</span>
                        </td>
                        <td class="px-4 text-right">TZS {{ number_format($stock->buying_cost) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($stock->selling_price) }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($stock->quantity * $stock->buying_cost) }}</td>
                        <td class="px-4 text-gray-500">{{ $stock->supplier ?? '-' }}</td>
                        <td class="px-4">@if(!empty($stock->category))<span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">{{ $stock->category }}</span>@else <span class="text-gray-400">—</span> @endif</td>
                        <td class="px-4 text-gray-500">{{ $stock->date_received ? \Carbon\Carbon::parse($stock->date_received)->format('M d, Y') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-gray-400">No stock records yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
@endsection
