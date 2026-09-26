<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Your Orders - World Choice Perfume</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-amber-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" class="w-10 h-10 rounded-full object-cover">
            <span class="font-bold text-lg">World Choice Perfume</span>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-6">Your Orders</h1>

        @forelse($orders as $order)
            <div class="bg-white rounded-xl shadow p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                        <p class="text-sm text-gray-500">{{ $order->branch->name }} | {{ \Carbon\Carbon::parse($order->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
                <div class="mt-3 text-sm">
                    @foreach($order->items as $item)
                        <p>{{ $item->quantity }}x {{ $item->product->name }} - TZS {{ number_format($item->total) }}</p>
                    @endforeach
                    <p class="font-bold mt-2">Total: TZS {{ number_format($order->total) }}</p>
                </div>
                @if(collect($order->timeline ?? [])->count() > 0)
                    <div class="mt-3 border-t border-gray-100 pt-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Progress</p>
                        <ol class="space-y-2">
                            @foreach($order->timeline as $step)
                                <li class="flex items-center gap-2 text-sm">
                                    <i class="fas fa-check-circle text-green-600 text-xs"></i>
                                    <span class="font-medium">{{ $step['label'] }}</span>
                                    <span class="text-xs text-gray-400 ml-auto">{{ \Carbon\Carbon::parse($step['at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
                @if(collect($order->notes ?? [])->count() > 0)
                    <div class="mt-3 border-t border-gray-100 pt-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Order Updates</p>
                        @foreach($order->notes as $note)
                            <div class="bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mb-2">
                                <p class="text-sm text-gray-700">{{ $note->note }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($note->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 text-gray-400">
                <p>No orders found for this phone number.</p>
            </div>
        @endforelse

        <div class="text-center mt-6">
            <a href="https://wa.me/255616675940" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fab fa-whatsapp text-lg"></i> Chat with us
            </a>
        </div>

        <p class="text-center mt-4"><a href="{{ route('customer.orders.track') }}" class="text-amber-700 hover:underline">&larr; Track Another Order</a></p>
    </div>
</body>
</html>
