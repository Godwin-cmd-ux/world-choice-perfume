@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="bg-white rounded-xl shadow px-4 py-3 flex flex-wrap items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Status</label>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All statuses</option>
            @foreach(['pending', 'assigned', 'ready', 'completed', 'served', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        @if(request('status'))
            <a href="{{ route('customer-care.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-times mr-1"></i>Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number ?? 'N/A' }}</td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('customer-care.orders.show', $order->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @php
                                $allowed = $transitions[$order->status] ?? [];
                                $lockOwner = $order->assigned_to ?? $order->cashier_id ?? null;
                                $isLocked = in_array($order->status, ['assigned', 'ready', 'completed']) && (string)($lockOwner ?? '') !== (string)($userId ?? '');
                            @endphp
                            @if($isLocked)
                                <span class="text-xs text-gray-400"><i class="fas fa-lock mr-1"></i>Other staff</span>
                            @else
                                @php $next = collect($allowed)->first(fn($s) => $s !== 'cancelled'); @endphp
                                @if($next)
                                    <form action="{{ route('customer-care.orders.update-status', $order->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $next }}">
                                        <button type="submit" class="text-amber-600 hover:underline mr-2 font-medium"><i class="fas fa-arrow-right mr-1"></i>{{ ucfirst($next) }}</button>
                                    </form>
                                @endif
                                @if(in_array('cancelled', $allowed))
                                    <form action="{{ route('customer-care.orders.update-status', $order->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="text-red-600 hover:underline font-medium" onclick="return confirm('Cancel order {{ $order->order_number }}?')"><i class="fas fa-times mr-1"></i>Cancel</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No orders available</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection