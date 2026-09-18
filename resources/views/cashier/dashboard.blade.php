@extends('layouts.app')
@section('title', 'Cashier Dashboard')

@section('header', 'Cashier Dashboard')
@section('content')
@include('cashier.partials.cross-branch-banner')
@if(($pendingOrders ?? 0) > 0)
    <a href="{{ route('cashier.orders.index', ['status' => 'pending']) }}" class="block mb-8 bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 hover:bg-amber-100 transition flex items-center justify-between gap-3">
        <span class="flex items-center gap-3">
            <span class="w-10 h-10 bg-amber-500 text-white rounded-lg flex items-center justify-center"><i class="fas fa-clock"></i></span>
            <span>
                <span class="block font-semibold text-gray-800">Pending Orders</span>
                <span class="block text-sm text-gray-500">{{ $pendingOrders }} order(s) waiting — go to orders to process them</span>
            </span>
        </span>
        <span class="flex items-center gap-2 text-amber-700 font-bold">{{ $pendingOrders }} <i class="fas fa-arrow-right"></i></span>
    </a>
@endif
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill text-green-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($dailySummary['daily_sales']) }}</p><p class="text-sm text-gray-500">Daily Sales (Paid)</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill-wave text-red-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($dailySummary['daily_expenses']) }}</p><p class="text-sm text-gray-500">Daily Expenses</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 {{ ($dailySummary['actual_sales'] ?? 0) >= 0 ? 'bg-amber-100' : 'bg-red-100' }} rounded-full flex items-center justify-center"><i class="fas fa-balance-scale {{ ($dailySummary['actual_sales'] ?? 0) >= 0 ? 'text-amber-700' : 'text-red-700' }} text-xl"></i></div>
            <div><p class="text-2xl font-bold {{ ($dailySummary['actual_sales'] ?? 0) >= 0 ? '' : 'text-red-600' }}">TZS {{ number_format($dailySummary['actual_sales']) }}</p><p class="text-sm text-gray-500">Actual Sales (Sales − Expenses)</p></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
    @unless($inCrossBranch)
        <div class="bg-white rounded-xl shadow p-6">
            <a href="{{ route('cashier.sales.create') }}" style="background-color: #F89A1E;" class="block w-full h-full  hover:opacity-90 text-white rounded-xl flex items-center justify-center gap-2 font-semibold transition">
                <i class="fas fa-plus"></i> New Sale
            </a>
        </div>
    @endunless
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
                        <td class="text-right text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales yet today</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
