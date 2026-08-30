<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Your Orders - World Choice Perfumes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-amber-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" class="w-10 h-10 rounded-full object-cover">
            <span class="font-bold text-lg">World Choice Perfumes</span>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-6">Your Orders</h1>

        @forelse($orders as $order)
            <div class="bg-white rounded-xl shadow p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                        <p class="text-sm text-gray-500">{{ $order->branch->name }} | {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y H:i') }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div class="mt-3 text-sm">
                    @foreach($order->items as $item)
                        <p>{{ $item->quantity }}x {{ $item->product->name }} - TZS {{ number_format($item->total) }}</p>
                    @endforeach
                    <p class="font-bold mt-2">Total: TZS {{ number_format($order->total) }}</p>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-400">
                <p>No orders found for this phone number.</p>
            </div>
        @endforelse

        <p class="text-center mt-6"><a href="{{ route('customer.orders.track') }}" class="text-amber-700 hover:underline">&larr; Track Another Order</a></p>
    </div>
</body>
</html>
