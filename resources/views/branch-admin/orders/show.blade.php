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
            <div class="flex justify-between"><span>Cashier:</span><span>{{ $order->cashier?->name ?? 'Not assigned' }}</span></div>
            <div class="flex justify-between"><span>Placed:</span><span>{{ \Carbon\Carbon::parse($order->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span></div>
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

        @if(!empty($order->notes))
            <div class="mt-4 border-t pt-3">
                <h3 class="text-sm font-semibold mb-2"><i class="fas fa-sticky-note mr-1 text-amber-600"></i>Order Updates</h3>
                <div class="space-y-2">
                    @foreach(collect($order->notes)->sortByDesc('created_at') as $note)
                        <div class="text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                            <p>{{ $note->note }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($note->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!in_array($order->status, ['completed', 'served', 'cancelled']))
            <div class="mt-4 border-t pt-3">
                <form action="{{ route('branch-admin.orders.cancel', $order->id) }}" method="POST" data-confirm="Cancel this order?">@csrf
                    <label for="cancel-note" class="block text-sm font-medium text-gray-700 mb-1">Order Note <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-2">Enter why you are cancelling this order.</p>
                    <textarea name="note" id="cancel-note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3" required placeholder="Reason for cancellation..."></textarea>
                    <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm">Cancel Order</button>
                </form>
            </div>
        @endif
    </div>
    <a href="{{ route('branch-admin.orders.index') }}" class="mt-4 inline-block text-amber-700 hover:underline">&larr; Back to Orders</a>
</div>
@endsection
