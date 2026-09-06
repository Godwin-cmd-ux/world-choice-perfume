@extends('layouts.app')
@section('title', 'Order Details')
@section('header', 'Order ' . ($order->order_number ?? ''))

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">{{ $order->order_number ?? 'N/A' }}</h3>
                    <p class="text-xs text-gray-500">{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('M d, Y \a\t h:i A') : '—' }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    @if(($order->status ?? '') === 'pending') bg-yellow-100 text-yellow-800
                    @elseif(($order->status ?? '') === 'assigned') bg-blue-100 text-blue-800
                    @elseif(($order->status ?? '') === 'ready') bg-emerald-100 text-emerald-800
                    @elseif(($order->status ?? '') === 'completed') bg-purple-100 text-purple-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($order->status ?? 'unknown') }}
                </span>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-6">Product</th>
                        <th class="text-right px-6">Qty</th>
                        <th class="text-right px-6">Price</th>
                        <th class="text-right px-6">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr class="border-t">
                            <td class="py-3 px-6 font-medium">{{ $item->product?->name ?? 'Unknown' }}</td>
                            <td class="px-6 text-right">{{ $item->quantity }}</td>
                            <td class="px-6 text-right">TZS {{ number_format($item->unit_price ?? 0) }}</td>
                            <td class="px-6 text-right font-medium">TZS {{ number_format($item->total ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2">
                    <tr>
                        <td colspan="3" class="py-3 px-6 font-semibold text-right">Total:</td>
                        <td class="py-3 px-6 text-right font-bold text-lg">TZS {{ number_format($order->total ?? 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3">Order Info</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Status:</span><span class="capitalize">{{ $order->status ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Branch:</span><span>{{ $order->branch?->name ?? '—' }}</span></div>
                @if($order->delivery_notes)
                    <div class="flex justify-between"><span class="text-gray-500">Notes:</span><span class="text-xs">{{ $order->delivery_notes }}</span></div>
                @endif
            </div>
        </div>

        @if($order->customer)
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3">Customer</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Name:</span><span>{{ $order->customer->name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Phone:</span><span>{{ $order->customer->phone ?? '—' }}</span></div>
                </div>
            </div>
        @endif

        @if($order->cashier)
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3">Assigned To</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Cashier:</span><span>{{ $order->cashier->name ?? '—' }}</span></div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('super-admin.orders.index') }}" class="text-amber-700 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Orders</a>
</div>
@endsection
