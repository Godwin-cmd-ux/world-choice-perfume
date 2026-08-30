<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Order Placed - World Choice Perfumes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check text-4xl text-green-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-4">Your order has been received and is being processed.</p>
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm"><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p class="text-sm"><strong>Total:</strong> TZS {{ number_format($order->total) }}</p>
            <p class="text-sm"><strong>Status:</strong> <span class="text-yellow-600">Pending</span></p>
        </div>
        <p class="text-sm text-gray-500 mb-4">A cashier will pick up your order shortly. You can track it using your phone number.</p>
        <a href="{{ route('customer.orders.track') }}" class="inline-block bg-amber-700 hover:bg-amber-800 text-white px-6 py-2 rounded-lg">
            Track Your Order
        </a>
    </div>
</body>
</html>
