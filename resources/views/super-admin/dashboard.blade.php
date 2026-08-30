@extends('layouts.app')
@section('title', 'Super Admin Dashboard')

@section('header', 'Dashboard')
@section('subtitle')
    Welcome back, {{ auth()->user()->name }}
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    {{-- Active Branches --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Active Branches</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeBranches }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-store text-amber-600 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Today's Sales Revenue --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Today's Sales</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">TZS {{ number_format($todayTotalRevenue) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $totalSalesCount }} transactions</p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-emerald-600 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Pending Orders --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $pendingOrders }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Awaiting pickup</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Pending Approvals --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $pendingCashiers + $pendingAdmins }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $pendingAdmins }} admins · {{ $pendingCashiers }} cashiers</p>
            </div>
            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-clock text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Financials --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-base font-semibold text-gray-800">Today's Financial Summary</h3>
        <span class="text-xs text-gray-400">{{ now()->format('l, M d, Y') }}</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-xl">
            <p class="text-2xl font-bold text-emerald-600">TZS {{ number_format($todayFinancials['revenue']) }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Revenue</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-xl">
            <p class="text-2xl font-bold text-blue-600">TZS {{ number_format($todayFinancials['gross_profit']) }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Gross Profit</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-xl">
            <p class="text-2xl font-bold text-red-500">TZS {{ number_format($todayFinancials['expenses']) }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Expenses</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-xl">
            <p class="text-2xl font-bold text-amber-600">TZS {{ number_format($todayFinancials['net_profit']) }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Net Profit</p>
        </div>
    </div>
</div>

{{-- Pending Orders Per Branch --}}
@if($pendingOrders > 0)
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-clock text-red-500 mr-2"></i>Pending Orders — Staff Pickup Monitor</h3>
        <span class="text-xs text-gray-400">Orders awaiting pickup by cashiers</span>
    </div>

    @foreach($branches as $branch)
        @if($branch->pending_count > 0)
            <div class="mb-4 last:mb-0 border border-red-100 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 bg-red-50">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-800">{{ $branch->name }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">{{ $branch->pending_count }} pending</span>
                        @if($branch->in_progress_count > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-500 text-white">{{ $branch->in_progress_count }} in progress</span>
                        @endif
                    </div>                        @if($branch->pending_count > 0)
                        @php
                            $oldestMinutes = $branch->pending_orders->min('minutes_ago') ?? 0;
                        @endphp
                        <span class="text-xs {{ $oldestMinutes > 1440 ? 'text-red-600 font-bold' : ($oldestMinutes > 60 ? 'text-amber-600 font-bold' : 'text-gray-500') }}">
                            Oldest: {{ $oldestMinutes < 60 ? $oldestMinutes . 'm' : ($oldestMinutes < 1440 ? floor($oldestMinutes / 60) . 'h ' . ($oldestMinutes % 60) . 'm' : floor($oldestMinutes / 1440) . 'd ' . floor(($oldestMinutes % 1440) / 60) . 'h') }}
                        </span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($branch->pending_orders as $order)
                        @php
                            $dur = $order->minutes_ago;
                            $colorClass = $dur > 1440 ? 'bg-red-100 text-red-700 border-red-300' : ($dur > 60 ? 'bg-amber-100 text-amber-700 border-amber-300' : 'bg-gray-100 text-gray-600 border-gray-200');
                        @endphp
                        <div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-700">{{ $order->order_number }}</span>
                                <span class="text-xs text-gray-500">{{ $order->customer_name }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold border {{ $colorClass }}">
                                {{ $order->duration_label }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
@endif

{{-- Branches Overview --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800">Branches Overview</h3>
        <a href="{{ route('super-admin.branches.index') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">View All <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Address</th>
                    <th class="text-right py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Cashiers</th>
                    <th class="text-right py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Today Sales</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3.5 px-6">
                            <div class="font-medium text-gray-900">{{ $branch->name }}</div>
                        </td>
                        <td class="py-3.5 px-6 text-gray-500">{{ $branch->address ?? '—' }}</td>
                        <td class="py-3.5 px-6 text-right">
                            <span class="inline-flex items-center justify-center w-7 h-7 bg-gray-100 rounded-full text-xs font-semibold text-gray-700">{{ $branch->cashiers_count }}</span>
                        </td>
                        <td class="py-3.5 px-6 text-right font-medium text-gray-900">TZS {{ number_format($branch->today_sales) }}</td>
                        <td class="py-3.5 px-6 text-center">
                            @if($branch->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-store text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No branches yet</p>
                                <a href="{{ route('super-admin.branches.create') }}" class="mt-2 text-sm text-amber-600 hover:text-amber-700 font-medium">Create your first branch →</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
