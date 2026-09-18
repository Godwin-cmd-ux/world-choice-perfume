@extends('layouts.app')
@section('title', 'Branch Admin Dashboard')

@section('header', 'Branch Dashboard')
@section('content')
@if(($pendingOrders ?? 0) > 0)
    <a href="{{ route('branch-admin.orders.index', ['status' => 'pending']) }}" class="block mb-8 bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 hover:bg-amber-100 transition flex items-center justify-between gap-3">
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
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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
</div>    <div class="bg-white rounded-xl shadow p-6 mb-8">
    <h3 class="text-lg font-semibold mb-4">Monthly Financials</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-green-600">TZS {{ number_format($financials['revenue']) }}</p>
            <p class="text-sm text-gray-500">Revenue</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold text-red-600">TZS {{ number_format($financials['expenses']) }}</p>
            <p class="text-sm text-gray-500">Expenses</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-xl font-bold {{ ($financials['revenue'] - $financials['expenses']) >= 0 ? 'text-amber-600' : 'text-red-600' }}">TZS {{ number_format($financials['revenue'] - $financials['expenses']) }}</p>
            <p class="text-sm text-gray-500">Actual (Revenue − Expenses)</p>
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
