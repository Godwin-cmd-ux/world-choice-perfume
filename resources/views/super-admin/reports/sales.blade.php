@extends('layouts.app')
@section('title', 'Sales Report')
@section('header', 'Sales Report')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-green-600">TZS {{ number_format($report['total_sales']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Sales</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $report['total_transactions'] }}</p>
        <p class="text-sm text-gray-500 mt-1">Transactions</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-amber-600">{{ $report['total_items_sold'] }}</p>
        <p class="text-sm text-gray-500 mt-1">Items Sold</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Sale #</th>
                    <th class="text-left px-4">Cashier</th>
                    <th class="text-left px-4">Payment</th>
                    <th class="text-right px-4">Total</th>
                    <th class="text-left px-4">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['sales'] as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $sale->sale_number ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->cashier?->name ?? '—' }}</td>
                        <td class="px-4 text-xs">{{ $sale->payment_summary ?? '—' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale->total ?? 0) }}</td>
                        <td class="px-4 text-gray-500 text-xs">{{ $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No sales data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
