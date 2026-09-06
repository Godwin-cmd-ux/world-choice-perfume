@extends('layouts.app')
@section('title', 'Stock Report')
@section('header', 'Stock Report')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition"><i class="fas fa-print mr-1"></i> Print</button>
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
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Product</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-right px-4">Quantity</th>
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
