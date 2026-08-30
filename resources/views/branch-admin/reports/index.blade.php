@extends('layouts.app')
@section('title', 'Reports')
@section('header', 'Reports')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="{{ route('branch-admin.reports.sales') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-200">
            <i class="fas fa-chart-line text-green-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Sales Report</h3>
        <p class="text-sm text-gray-500">Daily, weekly, monthly, yearly sales data</p>
    </a>
    <a href="{{ route('branch-admin.reports.profit') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-200">
            <i class="fas fa-coins text-blue-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Profit Report</h3>
        <p class="text-sm text-gray-500">Revenue, COGS, gross & net profit</p>
    </a>
    <a href="{{ route('branch-admin.reports.expenses') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-red-200">
            <i class="fas fa-file-invoice-dollar text-red-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Expense Report</h3>
        <p class="text-sm text-gray-500">All expenses by category</p>
    </a>
    <a href="{{ route('branch-admin.reports.stock') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-purple-200">
            <i class="fas fa-boxes text-purple-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Stock Report</h3>
        <p class="text-sm text-gray-500">Current stock levels and values</p>
    </a>
    <a href="{{ route('branch-admin.reports.cashier-performance') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-amber-200">
            <i class="fas fa-user-tie text-amber-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Cashier Performance</h3>
        <p class="text-sm text-gray-500">Sales & accountability by cashier</p>
    </a>
    <a href="{{ route('branch-admin.reports.product-performance') }}" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition group">
        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-3 group-hover:bg-indigo-200">
            <i class="fas fa-star text-indigo-700 text-xl"></i>
        </div>
        <h3 class="font-semibold text-lg">Product Performance</h3>
        <p class="text-sm text-gray-500">Top sellers, slow movers, margins</p>
    </a>
</div>
@endsection
