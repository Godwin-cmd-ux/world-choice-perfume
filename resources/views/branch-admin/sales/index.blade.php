@extends('layouts.app')
@section('title', 'Sales History')
@section('header', 'Sales History')
@section('header-actions')
    <a href="{{ route('branch-admin.sales.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> New Sale</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border rounded-lg text-sm">
        <select name="cashier_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Cashiers</option>
            @foreach($cashiers as $c)
                <option value="{{ $c->id }}" {{ request('cashier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6">
    <p class="text-sm text-gray-500">Total Sales: <strong class="text-lg text-green-700">TZS {{ number_format($totalSales) }}</strong></p>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Sale #</th>
                <th class="text-left px-4">Cashier</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-left px-4">Payment</th>
                <th class="text-right px-4">Total</th>
                <th class="text-left px-4">Date</th>
                <th class="text-center px-4">Action</th>
            </tr></thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $sale->sale_number }}</td>
                        <td class="px-4">{{ $sale->cashier->name }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 text-xs text-gray-600">{{ $sale->payment_summary ?? $sale->payment_method ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 text-center"><a href="{{ route('branch-admin.sales.show', $sale->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No sales found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
@endsection
