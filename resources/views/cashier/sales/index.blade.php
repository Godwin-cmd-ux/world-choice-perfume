@extends('layouts.app')
@section('title', 'My Sales')
@section('header', 'My Sales')
@section('header-actions')
    <a href="{{ route('cashier.sales.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> New Sale</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Sale #</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-right px-4">Items</th>
                <th class="text-right px-4">Total</th>
                <th class="text-left px-4">Date</th>
                <th class="text-center px-4">Action</th>
            </tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $sale->sale_number }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 text-right">{{ $sale->items->sum('quantity') }}</td>
                        <td class="px-4 text-right font-bold text-green-700">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 text-center"><a href="{{ route('cashier.sales.show', $sale->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No sales yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
