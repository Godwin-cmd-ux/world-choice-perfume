@extends('layouts.app')
@section('title', 'My Sales')
@section('header', 'My Sales')

@section('header-actions')
    <a href="{{ route('seller.sales.create') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> New Sale
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <span class="text-sm text-gray-500">Total: TZS {{ number_format($totalSales) }}</span>
        <span class="text-sm text-gray-500">{{ $sales->count() }} sales</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Sale #</th>
                    <th class="text-left px-4">Customer</th>
                    <th class="text-left px-4">Items Bought</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-right px-4">Total</th>
                    <th class="text-left px-4">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">
                            <a href="{{ route('seller.sales.show', $sale->id) }}" class="text-cyan-700 hover:underline">{{ $sale->sale_number ?? '—' }}</a>
                        </td>
                        <td class="px-4 text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4">
                            @php
                                $names = collect($sale->items ?? [])->pluck('product.name')->filter()->values();
                                $shown = $names->take(3)->implode(', ');
                                $extra = $names->count() - 3;
                            @endphp
                            @if($shown)
                                {{ $shown }}@if($extra > 0)<span class="text-xs text-gray-400"> +{{ $extra }} more</span>@endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4"><span class="px-2 py-0.5 rounded-full text-xs {{ ($sale->sale_type ?? '') === 'wholesale' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">{{ ucfirst($sale->sale_type ?? 'retail') }}</span></td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale->total ?? 0) }}</td>
                        <td class="px-4 text-gray-500 text-xs">{{ $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-gray-400"><i class="fas fa-receipt text-3xl mb-2 block"></i>No sales found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection