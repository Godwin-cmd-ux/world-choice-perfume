@extends('layouts.app')
@section('title', 'Branch Admin Dashboard')

@section('header', 'Branch Dashboard')
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
            <div><p class="text-2xl font-bold">{{ $todayTransactions }}</p><p class="text-sm text-gray-500">Transactions Today</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-shopping-cart text-amber-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $pendingOrders }}</p><p class="text-sm text-gray-500">Pending Orders</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-coins text-purple-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($totalStockValue) }}</p><p class="text-sm text-gray-500">Stock Value</p></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 mb-8">
    <h3 class="text-lg font-semibold mb-4">Monthly Financials</h3>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-green-600">TZS {{ number_format($financials['revenue']) }}</p>
            <p class="text-sm text-gray-500">Revenue</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-blue-600">TZS {{ number_format($financials['gross_profit']) }}</p>
            <p class="text-sm text-gray-500">Gross Profit</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-red-600">TZS {{ number_format($financials['expenses']) }}</p>
            <p class="text-sm text-gray-500">Expenses</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-amber-600">TZS {{ number_format($financials['net_profit']) }}</p>
            <p class="text-sm text-gray-500">Net Profit</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-gray-700">{{ $financials['transaction_count'] }}</p>
            <p class="text-sm text-gray-500">Transactions</p>
        </div>
    </div>
</div>

@if($lowStock > 0)
<div class="bg-yellow-50 border border-yellow-300 rounded-xl p-4 mb-8">
    <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-1"></i> <strong>{{ $lowStock }}</strong> products have low stock (5 or fewer units).</p>
</div>
@endif
@endsection
