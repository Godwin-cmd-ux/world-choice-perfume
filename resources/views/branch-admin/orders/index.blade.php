@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-2 flex-wrap">
        <a href="{{ route('branch-admin.orders.index') }}" class="px-3 py-1 rounded-full text-sm {{ !request('status') ? 'bg-amber-700 text-white' : 'bg-gray-200 text-gray-600' }}">All</a>
        @foreach(['pending','assigned','ready','completed','served','cancelled'] as $s)
            <a href="{{ route('branch-admin.orders.index', ['status' => $s]) }}" class="px-3 py-1 rounded-full text-sm {{ request('status') === $s ? 'bg-amber-700 text-white' : 'bg-gray-200 text-gray-600' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </form>
</div>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-left px-4">Cashier</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Action</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4">{{ $order->cashier?->name ?? '-' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="px-4 text-center"><a href="{{ route('branch-admin.orders.show', $order->id) }}" class="text-blue-600 hover:underline"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No orders found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
@endsection
