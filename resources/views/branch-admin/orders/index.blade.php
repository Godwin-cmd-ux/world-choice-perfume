@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
@include('partials.order-tabs', ['tabRoute' => $tabRoute])

<form method="GET" action="{{ route($tabRoute) }}" class="mb-4 flex justify-end">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="relative w-full sm:w-72">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search order number or personal name"
               class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
    </div>
</form>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Personal Name</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-left px-4">Picked By</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        // assigned_to is the current picker column, cashier_id
                        // the legacy one still set on orders placed before it.
                        $me = (string) $userId;
                        $isMine = (string) ($order->assigned_to ?? '') === $me
                            || (string) ($order->cashier_id ?? '') === $me;
                        $canName = $isMine && in_array($order->status, ['picked', 'served'], true);
                        $next = $order->status === 'pending' ? 'picked' : ($order->status === 'picked' && $isMine ? 'served' : null);
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4">
                            @include('partials.order-personal-name', ['order' => $order, 'nameRoute' => $nameRoute, 'canName' => $canName])
                        </td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-600">
                            {{ $order->assigned_to ? ($pickers[(string) $order->assigned_to] ?? 'Staff') : '—' }}
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('branch-admin.orders.show', $order->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @if($next)
                                <form action="{{ route('branch-admin.orders.update-status', $order->id) }}" method="POST" class="inline" onsubmit="return requireNote(this)">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <input type="hidden" name="note">
                                    <button type="submit" class="{{ $next === 'picked' ? 'text-green-600' : 'text-green-700 font-bold' }} hover:underline font-medium">
                                        <i class="fas {{ $next === 'picked' ? 'fa-hand-pointer' : 'fa-hand-holding' }} mr-1"></i>{{ ucfirst($next) }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">
                            @if(request('q'))
                                No orders match "{{ request('q') }}".
                            @elseif($tab === 'pending')
                                No orders waiting to be picked.
                            @elseif($tab === 'progress')
                                You have no orders in progress.
                            @else
                                You have not completed any orders yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
function requireNote(form) {
    var note = form.querySelector('input[name="note"]');
    if (!note || !note.value.trim()) {
        var val = prompt('Enter a note about this order update (what point you have reached):');
        if (val === null) return false;
        note.value = val;
    }
    return note.value.trim() !== '';
}
</script>
@endsection
