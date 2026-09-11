@extends('stock-manager.layouts.app')
@section('title', 'Order ' . $order->order_number)
@section('header', 'Order: ' . $order->order_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-bold">{{ $order->order_number }}</h2>
                <p class="text-sm text-gray-500">{{ $order->branch->name }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                {{ ucfirst($order->status) }}
            </span>
        </div>
        <div class="border-t border-b py-3 mb-4 text-sm space-y-1">
            <div class="flex justify-between"><span>Customer:</span><span>{{ $order->customer?->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between"><span>Phone:</span><span>{{ $order->customer?->phone ?? 'N/A' }}</span></div>
            @if($order->cashier?->name)<div class="flex justify-between"><span>Cashier:</span><span>{{ $order->cashier->name }}</span></div>@endif
            @if($order->delivery_notes)<div class="flex justify-between"><span>Notes:</span><span>{{ $order->delivery_notes }}</span></div>@endif
        </div>
        <table class="w-full text-sm mb-4">
            <thead><tr class="border-b"><th class="text-left py-1">Item</th><th class="text-center">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b"><td class="py-2">{{ $item->product->name }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-right">TZS {{ number_format($item->unit_price) }}</td><td class="text-right font-medium">TZS {{ number_format($item->total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-right text-lg font-bold text-emerald-700">Total: TZS {{ number_format($order->total) }}</div>

        @if(!($inCrossBranch ?? false))
            @php $allowed = $transitions[$order->status] ?? []; @endphp
            @if($allowed)
                <div class="mt-4 flex gap-3 flex-wrap">
                    @foreach($allowed as $s)
                        <form action="{{ route('stock-manager.orders.update-status', $order->id) }}" method="POST">@csrf
                            <input type="hidden" name="status" value="{{ $s }}">
                            <button type="submit"
                                class="{{ $s === 'cancelled' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white px-4 py-2 rounded-lg"
                                {{ $s === 'cancelled' ? 'onclick="return confirm(\'Cancel order?\')"' : '' }}>
                                <i class="fas {{ $s === 'cancelled' ? 'fa-times' : 'fa-arrow-right' }} mr-1"></i>
                                {{ $s === 'cancelled' ? 'Cancel Order' : 'Mark ' . ucfirst($s) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
    <a href="{{ route('stock-manager.orders.index') }}" class="mt-4 inline-block text-emerald-700 hover:underline">&larr; Back to Orders</a>
</div>
@endsection