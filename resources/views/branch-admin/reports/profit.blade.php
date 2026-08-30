@extends('layouts.app')
@section('title', 'Profit Report')
@section('header', 'Profit Report: ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'))

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition">
        <i class="fas fa-print mr-1"></i> Generate Report
    </button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
        <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-green-600">TZS {{ number_format($financials['revenue']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Revenue</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-red-600">TZS {{ number_format($financials['cogs']) }}</p>
        <p class="text-sm text-gray-500 mt-1">COGS</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-blue-600">TZS {{ number_format($financials['gross_profit']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Gross Profit</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-red-500">TZS {{ number_format($financials['expenses']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Expenses</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-amber-700">TZS {{ number_format($financials['net_profit']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Net Profit</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-xl font-bold">{{ $financials['transaction_count'] }}</p>
        <p class="text-sm text-gray-500">Transactions</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-xl font-bold">{{ $financials['products_sold'] }}</p>
        <p class="text-sm text-gray-500">Products Sold</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-xl font-bold">{{ $financials['stock_remaining'] }} units</p>
        <p class="text-sm text-gray-500">Stock Remaining</p>
    </div>
</div>
<a href="{{ route('branch-admin.reports.index') }}" class="mt-4 inline-block text-amber-700 hover:underline no-print">&larr; Back to Reports</a>
@endsection
