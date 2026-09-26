@extends('layouts.app')
@section('title', 'Orders Monitor')
@section('header', 'Orders Monitor')

@section('content')
{{-- Super Admin is not isolated, so these tabs list every order for every
     staff member rather than only the ones this admin picked. --}}
@include('partials.order-tabs', ['tabRoute' => $tabRoute, 'tabLabels' => $tabLabels])

<form method="GET" action="{{ route($tabRoute) }}" class="mb-4 flex flex-wrap items-center justify-end gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="branch_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <option value="">All branches</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === (int) $branch->id)>{{ $branch->name }}</option>
        @endforeach
    </select>
    <div class="relative w-full sm:w-64">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search order number or personal name"
               class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
    </div>
    <button type="submit" style="background-color:#F89A1E" class="px-4 py-2 text-sm text-white font-medium rounded-lg hover:opacity-90">Filter</button>
</form>

<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">Pending</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $counts['pending'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">On progress</p>
        <p class="text-2xl font-bold text-blue-600">{{ $counts['progress'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">Completed</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $counts['completed'] ?? 0 }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Personal Name</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-left px-4">Branch</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-left px-4">Picked By</th>
                <th class="text-left px-4">Age</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4">
                            @include('partials.order-personal-name', ['order' => $order, 'nameRoute' => $nameRoute, 'canName' => true, 'modalId' => 'order-name-' . $order->id])
                        </td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-gray-600">{{ $order->branch?->name ?? '—' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-emerald-100 text-emerald-800 font-bold', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-600">
                            {{ $order->assigned_to ? ($pickers[(string) $order->assigned_to] ?? 'Staff') : '—' }}
                        </td>
                        <td class="px-4 text-gray-500">{{ $order->duration_label }}</td>
                        <td class="px-4 text-center">
                            <a href="{{ route('super-admin.orders.show', $order->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-gray-400">
                            @if(request('q'))
                                No orders match "{{ request('q') }}".
                            @elseif($tab === 'pending')
                                No pending orders{{ $selectedBranchId ? ' at this branch' : '' }}.
                            @elseif($tab === 'progress')
                                No orders are currently on progress.
                            @else
                                No completed orders yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
