@extends('layouts.app')
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
        <div class="text-right text-lg font-bold text-amber-700">Total: TZS {{ number_format($order->total) }}</div>

        <div class="mt-4 flex gap-3 flex-wrap">
            @if($order->status === 'pending')
                <form action="{{ route('cashier.orders.pick', $order->id) }}" method="POST">@csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-hand-pointer mr-1"></i> Pick Order</button>
                </form>
            @endif
            @if($order->status === 'assigned' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                <form action="{{ route('cashier.orders.ready', $order->id) }}" method="POST">@csrf
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-check mr-1"></i> Mark Ready</button>
                </form>
            @endif
            @if($order->status === 'ready' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                <form action="{{ route('cashier.orders.complete', $order->id) }}" method="POST">@csrf
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg"><i class="fas fa-check-double mr-1"></i> Complete Order</button>
                </form>
            @endif
            @if($order->status === 'completed' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                <form action="{{ route('cashier.orders.serve', $order->id) }}" method="POST">@csrf
                    <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg font-bold"><i class="fas fa-hand-holding mr-1"></i> Mark as Served</button>
                </form>
            @endif
        </div>
    </div>
    <a href="{{ route('cashier.orders.index') }}" class="mt-4 inline-block text-amber-700 hover:underline">&larr; Back to Orders</a>
</div>
@endsection
