@extends('layouts.app')
@section('title', 'Product Performance Report')
@section('header', 'Product Performance Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" action="{{ route($routePrefix . '.product-performance') }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Branch</label>
            <div class="px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-600">
                <i class="fas fa-store mr-1 text-amber-600"></i>{{ $branchName ?? 'Branch' }}
            </div>
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">#</th>
                    <th class="text-left px-4">Product</th>
                    <th class="text-right px-4">Units Sold</th>
                    <th class="text-right px-4">In Stock</th>
                    <th class="text-right px-4">% Sold</th>
                    <th class="text-right px-4">Revenue (TZS)</th>
                    <th class="text-right px-4">% of Total Sales</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $idx => $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="px-4 font-medium">{{ $item['name'] }}</td>
                        <td class="px-4 text-right">{{ number_format($item['total_sold']) }}</td>
                        <td class="px-4 text-right text-gray-500">{{ number_format($item['remaining']) }}</td>
                        <td class="px-4 text-right">
                            <span class="{{ $item['sell_through'] >= 50 ? 'text-green-700 font-semibold' : ($item['sell_through'] >= 20 ? 'text-amber-600' : 'text-red-600') }}">{{ number_format($item['sell_through'], 1) }}%</span>
                        </td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($item['total_revenue']) }}</td>
                        <td class="px-4 text-right font-semibold">{{ number_format($item['contribution'], 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No product performance data</td></tr>
                @endforelse
            </tbody>
            @if(count($report))
                <tfoot class="bg-gray-50 border-t font-semibold">
                    <tr>
                        <td colspan="5" class="py-3 px-4 text-right">Total</td>
                        <td class="px-4 text-right">TZS {{ number_format($totalRevenue) }}</td>
                        <td class="px-4 text-right">100%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <div class="px-4 py-3 text-xs text-gray-500 border-t no-print">
        <strong>% Sold</strong> = units sold &divide; (units sold + units currently in stock) &times; 100 &mdash; how much of the available stock a product actually moved. <strong>% of Total Sales</strong> = this product's revenue &divide; revenue across all products &times; 100 &mdash; its weight in the business. Stock is a current snapshot, so it is not limited to the selected date range.
    </div>
</div>
@endsection
