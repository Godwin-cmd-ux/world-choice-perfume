@extends('layouts.app')
@section('title', 'Cross-Branch Stock')
@section('header', 'Cross-Branch Stock')
@section('subtitle', 'Stock summary, product-level stock and activity across all branches')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
            <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        @if($selectedBranchId)
            <a href="{{ route('super-admin.stock.cross-branch') }}" class="px-4 py-2 rounded-lg text-sm border border-gray-300 text-gray-600 hover:bg-gray-50"><i class="fas fa-times mr-1"></i> Clear</a>
        @endif
    </form>
</div>

{{-- 1. Per-branch summary --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Branch Stock Summary</h3>
        <span class="text-xs text-gray-400">{{ $summary->count() }} branch(es)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Product Items</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Low Stock</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Stock Value</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Bottles</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Oil Fragrances</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($summary as $branch)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $branch->name }}</p>
                            @if($branch->address)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $branch->address }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-gray-700">{{ number_format($branch->productItems) }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="{{ $branch->lowStock > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">{{ number_format($branch->lowStock) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-gray-700">TZS {{ number_format($branch->totalValue) }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($branch->totalBottles) }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($branch->totalOils) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-warehouse text-3xl mb-2 block"></i>
                            No branches found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 2. Stock activity feed --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Recent Stock Activity</h3>
        <span class="text-xs text-gray-400">{{ $activity->count() }} recent movement(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Qty</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Detail</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($activity as $m)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($m['created_at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $branchMap[$m['branch_id']] ?? 'Branch #' . $m['branch_id'] }}</td>
                        <td class="px-6 py-3">
                            @if($m['kind'] === 'product')
                                <span class="inline-flex items-center gap-1"><i class="fas fa-box text-purple-600"></i> {{ $m['title'] }}</span>
                            @elseif($m['kind'] === 'bottle')
                                <span class="inline-flex items-center gap-1"><i class="fas fa-prescription-bottle-alt text-blue-600"></i> {{ $m['title'] }}</span>
                            @else
                                <span class="inline-flex items-center gap-1"><i class="fas fa-flask text-emerald-600"></i> {{ $m['title'] }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @php
                                $type = strtolower($m['type']);
                                $label = ucwords(str_replace('_', ' ', $m['type']));
                            @endphp
                            @if(in_array($type, ['entry', 'stock_in']))
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">{{ $label }}</span>
                            @elseif(in_array($type, ['sale', 'stock_out']))
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ $label }}</span>
                            @elseif($type === 'broken')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">{{ $label }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ $label ?: '—' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right font-medium {{ $m['quantity'] < 0 ? 'text-blue-600' : 'text-green-600' }}">{{ $m['quantity'] > 0 ? '+' : '' }}{{ number_format($m['quantity']) }}</td>
                        <td class="px-6 py-3 text-gray-500 max-w-xs truncate">{{ $m['detail'] ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m['performed_by'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-history text-3xl mb-2 block"></i>
                            No stock activity found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 3. Product-level stock --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Product-Level Stock</h3>
        <span class="text-xs text-gray-400">{{ count($detail) }} product record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-6">Product</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-right px-4">Selling Price</th>
                    <th class="text-right px-4">Stock Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detail as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-6 font-medium">{{ $item['product'] }}</td>
                        <td class="px-4 text-gray-500">{{ $item['branch'] }}</td>
                        <td class="px-4 text-right {{ $item['quantity'] <= 5 ? 'text-red-600 font-bold' : '' }}">{{ number_format($item['quantity']) }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($item['selling_price']) }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($item['stock_value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No stock data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection