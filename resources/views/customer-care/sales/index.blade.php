@extends('layouts.app')
@section('title', 'Sales')
@section('header', 'Sales')

@section('header-actions')
<a href="{{ route('customer-care.sales.create') }}"
   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-medium transition">
    <i class="fas fa-plus"></i> New Sale
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
    <p class="text-sm text-gray-500">Total Revenue: <strong class="text-lg text-green-600">TZS {{ number_format($totalRevenue) }}</strong></p>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Customer</th>
                <th class="text-left px-4">Cashier</th>
                <th class="text-left px-4">Items Bought</th>
                <th class="text-right px-4">Total</th>
                <th class="text-left px-4">Status</th>
                <th class="text-left px-4">Branch</th>
                <th class="text-left px-4">Date</th>
            </tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-700 font-medium">{{ $sale->customer?->name ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->cashier?->name ?? '—' }}</td>
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
                        <td class="px-4 text-right font-medium text-green-600">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4">
                            <span class="px-2 py-1 rounded-full text-xs capitalize
                                @if(($sale->payment_status ?? '') === 'paid') bg-green-100 text-green-800
                                @elseif(($sale->payment_status ?? '') === 'cancelled') bg-red-100 text-red-800
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $sale->payment_status ?? 'pending' }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-500">{{ $sale->branch?->name ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No sales recorded</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
