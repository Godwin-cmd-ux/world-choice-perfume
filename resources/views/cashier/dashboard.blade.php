@extends('layouts.app')
@section('title', 'Cashier Dashboard')

@section('header', 'Cashier Dashboard')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill text-green-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($todaySales) }}</p><p class="text-sm text-gray-500">Today's Sales</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-receipt text-blue-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $todayTransactions }}</p><p class="text-sm text-gray-500">Transactions</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-clock text-amber-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $myAssignedOrders }}</p><p class="text-sm text-gray-500">My Orders</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <a href="{{ route('cashier.sales.create') }}" class="block w-full h-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl flex items-center justify-center gap-2 font-semibold transition">
            <i class="fas fa-plus"></i> New Sale
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Recent Sales</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b"><th class="text-left py-2">Sale #</th><th class="text-right">Total</th><th class="text-right">Items</th><th class="text-right">Date</th></tr></thead>
            <tbody>
                @forelse($recentSales as $sale)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2">{{ $sale->sale_number }}</td>
                        <td class="text-right font-medium">TZS {{ number_format($sale->total) }}</td>
                        <td class="text-right">{{ $sale->items->sum('quantity') }}</td>
                        <td class="text-right text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales yet today</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
