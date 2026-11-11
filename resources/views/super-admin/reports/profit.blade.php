@extends('layouts.app')
@section('title', 'Profit Report')
@section('header', 'Profit Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Products</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($summary['products']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Units in Stock</p>
        <p class="text-xl font-bold text-gray-800 mt-1">{{ number_format($summary['units']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Stock Cost Value</p>
        <p class="text-xl font-bold text-gray-800 mt-1 truncate">TZS {{ number_format($summary['stock_cost']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Stock Selling Value</p>
        <p class="text-xl font-bold text-gray-800 mt-1 truncate">TZS {{ number_format($summary['stock_value']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Potential Profit</p>
        <p class="text-xl font-bold text-emerald-700 mt-1 truncate">TZS {{ number_format($summary['potential_profit']) }}</p>
        <p class="text-[10px] text-gray-400 mt-1">{{ number_format($summary['margin'], 1) }}% margin</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-800">All Products</h3>
            <p class="text-xs text-gray-400">Unit cost, selling price and profit for every fragrance in stock</p>
        </div>
        <span class="text-[11px] text-gray-400">Profit = selling price − unit cost</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">#</th>
                    <th class="text-left px-4">Product</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-right px-4">Unit Cost</th>
                    <th class="text-right px-4">Selling Price</th>
                    <th class="text-right px-4">Profit / Unit</th>
                    <th class="text-right px-4">Stock Value</th>
                    <th class="text-right px-4">Potential Profit</th>
                    <th class="text-right px-4">Margin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $idx => $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="px-4">
                            <span class="font-medium block">{{ $item['product'] }}</span>
                            @if(!empty($item['brand']) || !empty($item['category']))
                                <span class="text-[11px] text-gray-400">{{ $item['brand'] ?? '' }}{{ !empty($item['brand']) && !empty($item['category']) ? ' · ' : '' }}{{ $item['category'] ?? '' }}</span>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">{{ $item['branch'] }}</td>
                        <td class="px-4 text-right">{{ number_format($item['quantity']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['unit_cost']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['selling_price']) }}</td>
                        <td class="px-4 text-right font-medium {{ $item['unit_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">TZS {{ number_format($item['unit_profit']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['stock_value']) }}</td>
                        <td class="px-4 text-right font-semibold {{ $item['potential_profit'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">TZS {{ number_format($item['potential_profit']) }}</td>
                        <td class="px-4 text-right">{{ number_format($item['margin'], 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-8 text-center text-gray-400">No product stock yet</td></tr>
                @endforelse
            </tbody>
            @if(count($report))
                <tfoot class="bg-gray-50 border-t font-semibold">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right">Total</td>
                        <td class="px-4 text-right">{{ number_format($summary['units']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($summary['stock_cost']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($summary['stock_value']) }}</td>
                        <td class="px-4 text-right">—</td>
                        <td class="px-4 text-right">TZS {{ number_format($summary['stock_value']) }}</td>
                        <td class="px-4 text-right text-emerald-700">TZS {{ number_format($summary['potential_profit']) }}</td>
                        <td class="px-4 text-right">{{ number_format($summary['margin'], 1) }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <div class="px-4 py-3 text-xs text-gray-500 border-t no-print">
        <strong>Profit / Unit</strong> = selling price − unit cost. <strong>Potential Profit</strong> = quantity in stock × profit per unit. The summary above covers every product listed here.
    </div>
</div>
@endsection
