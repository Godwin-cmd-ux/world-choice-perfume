@extends('layouts.app')
@section('title', 'My Sales')
@section('header', 'My Sales')
@section('header-actions')
    @unless($inCrossBranch)
        <a href="{{ route('cashier.sales.create') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> New Sale</a>
    @endunless
@include('partials.sale-drafts-panel', [
    'drafts' => $drafts ?? [],
    'draftCreateRoute' => 'cashier.sales.create',
    'draftDeleteRoute' => 'cashier.sales.draft.destroy',
    'accent' => 'amber',
])
@endsection

@section('content')
@include('cashier.partials.cross-branch-banner')

@php
    $presetClass = fn($active) => $active
        ? 'px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200'
        : 'px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 border border-gray-200';
@endphp
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <p class="text-sm text-gray-500">Total Sales <span class="text-xs text-gray-400">({{ $rangeLabel }})</span>: <strong class="text-lg text-green-700">TZS {{ number_format($totalSales) }}</strong></p>
    <form method="GET" class="flex gap-3 items-end flex-wrap mt-4">
        <div>
            <label for="sale-date-from" class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">From</label>
            <input type="date" id="sale-date-from" name="date_from" value="{{ $dateFrom }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label for="sale-date-to" class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">To</label>
            <input type="date" id="sale-date-to" name="date_to" value="{{ $dateTo }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-calendar mr-1"></i> Filter</button>
        @if(!$showAll && ($dateFrom !== $presets['today'] || $dateTo !== $presets['today']))
            <a href="{{ route('cashier.sales.index') }}" class="text-gray-500 text-sm px-3 py-2">Reset</a>
        @endif
    </form>
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <span class="text-xs text-gray-400">Quick ranges:</span>
        <a href="{{ route('cashier.sales.index') }}" class="{{ $presetClass(!$showAll && $dateFrom === $presets['today'] && $dateTo === $presets['today']) }}">Today</a>
        <a href="{{ route('cashier.sales.index', ['date_from' => $presets['last7'], 'date_to' => $presets['today']]) }}" class="{{ $presetClass(!$showAll && $dateFrom === $presets['last7'] && $dateTo === $presets['today']) }}">Last 7 days</a>
        <a href="{{ route('cashier.sales.index', ['date_from' => $presets['month'], 'date_to' => $presets['today']]) }}" class="{{ $presetClass(!$showAll && $dateFrom === $presets['month'] && $dateTo === $presets['today']) }}">This month</a>
        <a href="{{ route('cashier.sales.index', ['all' => 1]) }}" class="{{ $presetClass($showAll) }}">All dates</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Sale #</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-left px-4">Payment</th>
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
                        <td class="px-4 text-xs text-gray-600">{{ $sale->payment_summary ?? $sale->payment_method ?? 'N/A' }}</td>
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
                        <td class="px-4 text-right font-bold text-green-700">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</td>
                        <td class="px-4 text-center"><a href="{{ route('cashier.sales.show', $sale->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No sales for {{ strtolower($rangeLabel) }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
