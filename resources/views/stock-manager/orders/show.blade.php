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
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
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

        @if(!empty($order->notes))
            <div class="mt-4 border-t pt-3">
                <h3 class="text-sm font-semibold mb-2"><i class="fas fa-sticky-note mr-1 text-emerald-600"></i>Order Updates</h3>
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

        @if(!($inCrossBranch ?? false))
            @php
                $allowed = $transitions[$order->status] ?? [];
                $me = (string) ($userId ?? '');
                $isLocked = $order->status !== 'pending'
                    && !in_array((string) ($order->assigned_to ?? ''), [$me], true)
                    && !in_array((string) ($order->cashier_id ?? ''), [$me], true);
            @endphp
            @if($isLocked)
                <div class="mt-4 bg-gray-50 border border-gray-200 text-gray-500 px-4 py-3 rounded-lg text-sm">
                    <i class="fas fa-lock mr-1"></i> This order was picked by another staff member. Only they can update it.
                </div>
            @elseif($allowed)
                <div class="mt-4 border-t pt-3">
                    <label for="order-note-input" class="block text-sm font-medium text-gray-700 mb-1">Order Note <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-2">Enter what point you have reached for this order before changing the status.</p>
                    <textarea id="order-note-input" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3"
                        placeholder="e.g. Order received and being packed..."></textarea>
                    <div class="flex gap-3 flex-wrap">
                        @foreach($allowed as $s)
                            <form action="{{ route('stock-manager.orders.update-status', $order->id) }}" method="POST" onsubmit="return attachOrderNote(this)">@csrf
                                <input type="hidden" name="status" value="{{ $s }}">
                                <input type="hidden" name="note">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Mark {{ ucfirst($s) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
    <a href="{{ route('stock-manager.orders.index') }}" class="mt-4 inline-block text-emerald-700 hover:underline">&larr; Back to Orders</a>
</div>
<script>
function attachOrderNote(form) {
    var ta = document.getElementById('order-note-input');
    if (!ta || !ta.value.trim()) {
        alert('Please enter a note about this order update before changing the status.');
        return false;
    }
    form.querySelector('input[name="note"]').value = ta.value.trim();
    return true;
}
</script>
@endsection