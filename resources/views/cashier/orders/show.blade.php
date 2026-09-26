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
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
                {{ ucfirst($order->status) }}
            </span>
        </div>
        {{-- Staff-only memory aid. The official order number above is unchanged. --}}
        <div class="mb-4">
            @include('partials.order-personal-name', ['order' => $order, 'nameRoute' => $nameRoute, 'canName' => $canName])
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

        @if(!empty($order->notes))
            <div class="mt-4 border-t pt-3">
                <h3 class="text-sm font-semibold mb-2"><i class="fas fa-sticky-note mr-1 text-amber-600"></i>Order Updates</h3>
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

        @if(in_array($order->status, ['pending', 'picked']))
            <div class="mt-4 border-t pt-3">
                <label for="order-note-input" class="block text-sm font-medium text-gray-700 mb-1">Order Note <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-400 mb-2">Enter what point you have reached for this order before changing the status.</p>
                <textarea id="order-note-input" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3"
                    placeholder="e.g. Order received and being prepared..."></textarea>
            </div>
        @endif

        <div class="mt-4 flex gap-3 flex-wrap">
            @if($order->status === 'pending')
                <form action="{{ route('cashier.orders.pick', $order->id) }}" method="POST" onsubmit="return attachOrderNote(this)">@csrf
                    <input type="hidden" name="note">
                    <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg"><i class="fas fa-hand-pointer mr-1"></i> Pick Order</button>
                </form>
            @endif
            @if($order->status === 'picked' && (in_array((string) ($order->assigned_to ?? ''), [(string) (auth()->user()->supabase_id ?? auth()->id())], true) || in_array((string) ($order->cashier_id ?? ''), [(string) (auth()->user()->supabase_id ?? auth()->id())], true)))
                <form action="{{ route('cashier.orders.serve', $order->id) }}" method="POST" onsubmit="return attachOrderNote(this)">@csrf
                    <input type="hidden" name="note">
                    <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg font-bold"><i class="fas fa-hand-holding mr-1"></i> Mark as Served</button>
                </form>
            @endif
        </div>
    </div>
    <a href="{{ route('cashier.orders.index') }}" class="mt-4 inline-block text-amber-700 hover:underline">&larr; Back to Orders</a>
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
