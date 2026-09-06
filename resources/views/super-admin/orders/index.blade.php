@extends('layouts.app')
@section('title', 'Orders Monitor')
@section('header', 'Orders Monitor')

@section('content')
{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-yellow-600">{{ $pending }}</p>
        <p class="text-xs text-gray-500 mt-1">Pending</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $assigned }}</p>
        <p class="text-xs text-gray-500 mt-1">Assigned</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ $ready }}</p>
        <p class="text-xs text-gray-500 mt-1">Ready</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-600">{{ $completed }}</p>
        <p class="text-xs text-gray-500 mt-1">Completed/Served</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
            <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Ready</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="served" {{ request('status') === 'served' ? 'selected' : '' }}>Served</option>
        </select>
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

{{-- Orders Table --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Order #</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-left px-4">Customer</th>
                    <th class="text-left px-4">Cashier</th>
                    <th class="text-center px-4">Status</th>
                    <th class="text-right px-4">Total</th>
                    <th class="text-center px-4">Duration</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'assigned' => 'bg-blue-100 text-blue-800',
                            'ready' => 'bg-emerald-100 text-emerald-800',
                            'completed' => 'bg-purple-100 text-purple-800',
                            'served' => 'bg-gray-100 text-gray-800',
                        ];
                        $dur = $order->minutes_ago;
                        $durColor = $dur > 1440 ? 'text-red-600 font-bold' : ($dur > 60 ? 'text-amber-600 font-bold' : 'text-gray-500');
                    @endphp
                    <tr class="border-t hover:bg-gray-50 {{ ($order->status ?? '') === 'pending' && $dur > 60 ? 'bg-red-50' : '' }}">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number ?? 'N/A' }}</td>
                        <td class="px-4 text-gray-500">{{ $order->branch?->name ?? '—' }}</td>
                        <td class="px-4">{{ $order->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 text-gray-500">{{ $order->cashier?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status ?? ''] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($order->status ?? 'unknown') }}
                            </span>
                        </td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total ?? 0) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold border {{ $dur > 1440 ? 'bg-red-100 text-red-700 border-red-300' : ($dur > 60 ? 'bg-amber-100 text-amber-700 border-amber-300' : 'bg-gray-100 text-gray-600 border-gray-200') }}">
                                {{ $order->duration_label }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('super-admin.orders.show', $order->id) }}" class="text-amber-600 hover:text-amber-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-400">
                            <i class="fas fa-shopping-bag text-3xl mb-2 block"></i>
                            No orders found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
