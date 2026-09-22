@extends('layouts.app')
@section('title', 'Branch Admin Dashboard')

@section('header', 'Branch Dashboard')
@section('content')

@php
    $daily = $dailySummary ?? [];
    $fin = $financials ?? [];
    $actual = (float) ($daily['actual_sales'] ?? 0);
    $revenue = (float) ($fin['revenue'] ?? 0);
    $expenses = (float) ($fin['expenses'] ?? 0);
    $marginPct = $revenue > 0 ? max(0, min(100, round((($revenue - $expenses) / $revenue) * 100))) : 0;
    $branchName = auth()->user()->branch->name ?? null;
@endphp

{{-- Welcome banner --}}
<div class="mb-6 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 rounded-2xl px-6 py-6 flex flex-wrap items-center justify-between gap-4 shadow-lg">
    <div>
        <p class="text-[11px] uppercase tracking-[0.2em] text-amber-400 font-semibold mb-1">{{ now()->format('l, M j Y') }}</p>
        <h2 class="text-xl sm:text-2xl font-bold text-white">Welcome back, {{ explode(' ', trim(auth()->user()->name ?? ''))[0] }} 👋</h2>
        <p class="text-sm text-gray-400 mt-1">
            Here's how <span class="text-white font-medium">{{ $branchName ?? 'your branch' }}</span> is doing today.
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('branch-admin.sales.create') }}"
           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow transition">
            <i class="fas fa-plus"></i> New Sale
        </a>
        <a href="{{ route('branch-admin.orders.index', ['status' => 'pending']) }}"
           class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <i class="fas fa-clipboard-list"></i> Orders
        </a>
    </div>
</div>

@if(($pendingOrders ?? 0) > 0)
    <a href="{{ route('branch-admin.orders.index', ['status' => 'pending']) }}"
       class="block mb-6 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-xl px-5 py-4 hover:from-amber-100 hover:to-orange-100 transition flex items-center justify-between gap-3 shadow-sm">
        <span class="flex items-center gap-3">
            <span class="w-10 h-10 bg-amber-500 text-white rounded-lg flex items-center justify-center shadow"><i class="fas fa-clock"></i></span>
            <span>
                <span class="block font-semibold text-gray-800">Pending Orders</span>
                <span class="block text-sm text-gray-500">{{ $pendingOrders }} order{{ $pendingOrders === 1 ? '' : 's' }} waiting — go to orders to process them</span>
            </span>
        </span>
        <span class="flex items-center gap-2 text-amber-700 font-bold">{{ $pendingOrders }} <i class="fas fa-arrow-right"></i></span>
    </a>
@endif

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-green-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-green-600"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2 py-1 rounded-full">Today</span>
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Daily Sales (Paid)</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5 truncate">TZS {{ number_format($daily['daily_sales'] ?? 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $todayTransactions ?? 0 }} transaction{{ ($todayTransactions ?? 0) === 1 ? '' : 's' }} processed</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center">
                <i class="fas fa-file-invoice-dollar text-red-500"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded-full">Today</span>
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Daily Expenses</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5 truncate">TZS {{ number_format($daily['daily_expenses'] ?? 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">Recorded today at this branch</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl {{ $actual >= 0 ? 'bg-amber-100' : 'bg-red-100' }} flex items-center justify-center">
                <i class="fas fa-balance-scale {{ $actual >= 0 ? 'text-amber-600' : 'text-red-500' }}"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider {{ $actual >= 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-red-600 bg-red-50 border-red-200' }} border px-2 py-1 rounded-full">
                {{ $actual >= 0 ? 'Healthy' : 'Deficit' }}
            </span>
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Actual Sales (Sales &minus; Expenses)</p>
        <p class="text-2xl font-bold {{ $actual >= 0 ? 'text-gray-800' : 'text-red-600' }} mt-0.5 truncate">TZS {{ number_format($actual) }}</p>
        <p class="text-xs text-gray-400 mt-1">Net position for today</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fas fa-receipt text-blue-600"></i>
            </div>
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Transactions Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $todayTransactions ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Paid sales recorded so far</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-orange-100 flex items-center justify-center">
                <i class="fas fa-shopping-cart text-orange-500"></i>
            </div>
            @if(($pendingOrders ?? 0) > 0)
                <span class="text-[10px] font-bold uppercase tracking-wider text-orange-700 bg-orange-50 border border-orange-200 px-2 py-1 rounded-full animate-pulse">Action needed</span>
            @endif
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Pending Orders</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $pendingOrders ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Waiting to be processed</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-200">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-purple-100 flex items-center justify-center">
                <i class="fas fa-coins text-purple-600"></i>
            </div>
        </div>
        <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Stock Value</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5 truncate">TZS {{ number_format($totalStockValue ?? 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">At current selling prices</p>
    </div>
</div>

{{-- Monthly performance --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line text-emerald-600"></i>
            </span>
            <div>
                <h3 class="font-semibold text-gray-800">Monthly Performance</h3>
                <p class="text-xs text-gray-400">{{ now()->format('F Y') }} · revenue, expenses &amp; activity</p>
            </div>
        </div>
        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
            <i class="fas fa-box mr-1 text-gray-400"></i>{{ $lowStock ?? 0 }} low-stock item{{ ($lowStock ?? 0) === 1 ? '' : 's' }}
        </span>
    </div>

    <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-arrow-up text-green-600 text-xs"></i>
                <p class="text-[10px] font-bold uppercase tracking-wider text-green-700">Revenue</p>
            </div>
            <p class="text-xl font-bold text-green-700 truncate">TZS {{ number_format($revenue) }}</p>
        </div>
        <div class="p-4 bg-gradient-to-br from-red-50 to-orange-50 border border-red-100 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-arrow-down text-red-500 text-xs"></i>
                <p class="text-[10px] font-bold uppercase tracking-wider text-red-600">Expenses</p>
            </div>
            <p class="text-xl font-bold text-red-600 truncate">TZS {{ number_format($expenses) }}</p>
        </div>
        <div class="p-4 bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-100 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-balance-scale text-amber-600 text-xs"></i>
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Actual</p>
            </div>
            <p class="text-xl font-bold {{ ($revenue - $expenses) >= 0 ? 'text-amber-700' : 'text-red-600' }} truncate">TZS {{ number_format($revenue - $expenses) }}</p>
        </div>
        <div class="p-4 bg-gradient-to-br from-gray-50 to-slate-50 border border-gray-200 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-receipt text-gray-600 text-xs"></i>
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600">Transactions</p>
            </div>
            <p class="text-xl font-bold text-gray-700">{{ $fin['transaction_count'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Margin bar --}}
    <div class="px-5 pb-5">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
            <span class="font-medium">Profit margin</span>
            <span class="font-bold {{ $marginPct >= 30 ? 'text-emerald-600' : ($marginPct >= 10 ? 'text-amber-600' : 'text-red-500') }}">{{ $marginPct }}%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full {{ $marginPct >= 30 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : ($marginPct >= 10 ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-gradient-to-r from-red-400 to-red-600') }}"
                 style="width: {{ max($marginPct, 2) }}%"></div>
        </div>
    </div>
</div>

@if(($lowStock ?? 0) > 0)
<div class="bg-yellow-50 border border-yellow-300 rounded-xl p-4 mb-6 flex flex-wrap items-center gap-3">
    <span class="w-9 h-9 rounded-lg bg-yellow-200 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-exclamation-triangle text-yellow-700"></i>
    </span>
    <p class="text-sm text-yellow-800 flex-1 min-w-[200px]">
        <strong>{{ $lowStock }}</strong> product{{ $lowStock === 1 ? ' has' : 's have' }} low stock (5 or fewer units).
    </p>
    <a href="{{ route('branch-admin.orders.index') }}"
       class="text-sm font-semibold bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition">
        Review orders
    </a>
</div>
@endif

@endsection
