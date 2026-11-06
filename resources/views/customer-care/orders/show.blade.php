@extends('layouts.app')
@section('title', 'Order ' . ($order->order_number ?? 'N/A'))
@section('header', 'Order: ' . ($order->order_number ?? 'N/A'))

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-bold">{{ $order->order_number ?? 'N/A' }}</h2>
                <p class="text-sm text-gray-500">{{ $order->branch->name }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ match($order->status) { 'awaiting_care' => 'bg-amber-100 text-amber-800', 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'failed' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                {{ match($order->status) { 'awaiting_care' => 'Awaiting Review', 'failed' => 'Failed', default => ucfirst($order->status) } }}
            </span>
        </div>
        {{-- Staff-only memory aid. The official order number above is unchanged. --}}
        <div class="mb-4">
            @include('partials.order-personal-name', ['order' => $order, 'nameRoute' => $nameRoute, 'canName' => $canName])
        </div>
        <div class="border-t border-b py-3 mb-4 text-sm space-y-1">
            <div class="flex justify-between"><span>Customer:</span><span>{{ $order->customer?->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between"><span>Phone:</span><span>{{ $order->customer?->phone ?? 'N/A' }}</span></div>
            @if($order->cashier?->name)<div class="flex justify-between"><span>Served By:</span><span>{{ $order->cashier->name }}</span></div>@endif
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
        <div class="text-right text-lg font-bold text-blue-700">Total: TZS {{ number_format($order->total) }}</div>

        @if(!empty($order->notes))
            <div class="mt-4 border-t pt-3">
                <h3 class="text-sm font-semibold mb-2"><i class="fas fa-sticky-note mr-1 text-blue-600"></i>Order Updates</h3>
                <div class="space-y-2">
                    @foreach(collect($order->notes)->sortBy('created_at') as $note)
                        <div class="text-sm bg-gray-50 border border-gray-200 rounded-lg p-3">
                            <p>{{ $note->note }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($note->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            // A held order is decided here — allow it to the branch or trash it;
            // the branch's own transitions start at pending.
            $awaiting = $order->status === 'awaiting_care';
            $allowed = $transitions[$order->status] ?? [];
            $actionRoute = $awaiting ? 'customer-care.orders.decide' : 'customer-care.orders.update-status';
            $actionLabels = ['pending' => 'Allow Order', 'failed' => 'Trash Order'];
        @endphp
        @if($allowed)
            <div class="mt-4 border-t pt-3">
                <label for="order-note-input" class="block text-sm font-medium text-gray-700 mb-1">Order Note <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-400 mb-2">Enter what point you have reached for this order before changing the status.</p>
                <textarea id="order-note-input" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3"
                    placeholder="e.g. Order received and being packed..."></textarea>
                <div class="flex gap-3 flex-wrap">
                    @foreach($allowed as $s)
                        <form action="{{ route($actionRoute, $order->id) }}" method="POST" onsubmit="return attachOrderNote(this)">@csrf
                            <input type="hidden" name="status" value="{{ $s }}">
                            <input type="hidden" name="note">
                            <button type="submit" class="text-white px-4 py-2 rounded-lg {{ $s === 'failed' ? 'bg-red-600 hover:bg-red-700' : ($s === 'pending' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700') }}">
                                <i class="fas {{ $s === 'failed' ? 'fa-trash-can' : ($s === 'pending' ? 'fa-check' : 'fa-arrow-right') }} mr-1"></i>
                                {{ $actionLabels[$s] ?? 'Mark ' . ucfirst($s) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <a href="{{ route('customer-care.orders.index', ['tab' => request('tab') ?: 'pending']) }}" class="mt-4 inline-block text-blue-700 hover:underline">&larr; Back to Orders</a>
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