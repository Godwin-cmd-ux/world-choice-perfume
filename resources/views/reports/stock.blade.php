@extends('layouts.app')
@section('title', 'Stock Report')
@section('header', 'Stock Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <div class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Branch</label>
            <div class="px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-600">
                <i class="fas fa-store mr-1 text-amber-600"></i>{{ $branchName ?? 'Branch' }}
            </div>
        </div>
        <span class="text-xs text-gray-500 pb-2">{{ count($report) }} stock row{{ count($report) === 1 ? '' : 's' }}</span>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Product</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-right px-4">Unit Cost</th>
                    <th class="text-right px-4">Selling Price</th>
                    <th class="text-right px-4">Stock Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $item['product'] }}</td>
                        <td class="px-4 text-gray-500">{{ $item['branch'] }}</td>
                        <td class="px-4 text-right {{ $item['quantity'] <= 5 ? 'text-red-600 font-bold' : '' }}">{{ $item['quantity'] }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['unit_cost']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['selling_price']) }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($item['stock_value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No stock data</td></tr>
                @endforelse
            </tbody>
            @if(count($report))
                <tfoot class="bg-gray-50 border-t font-semibold">
                    <tr>
                        <td colspan="2" class="py-3 px-4 text-right">Total</td>
                        <td class="px-4 text-right">{{ number_format(array_sum(array_column($report, 'quantity'))) }}</td>
                        <td class="px-4 text-right">—</td>
                        <td class="px-4 text-right">—</td>
                        <td class="px-4 text-right">TZS {{ number_format(array_sum(array_column($report, 'stock_value'))) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
