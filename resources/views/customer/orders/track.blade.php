<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Track Order - World Choice Perfumes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
        <div class="text-center mb-6">
            <img src="{{ asset('our_logo.jpeg') }}" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover">
            <h1 class="text-2xl font-bold text-amber-900">Track Your Order</h1>
        </div>

        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('customer.orders.track-by-phone') }}">
            @csrf
            <input type="text" name="phone" placeholder="Enter your phone number" required
                   class="w-full px-4 py-3 border rounded-lg text-center text-lg mb-4 focus:ring-2 focus:ring-amber-500">
            <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-semibold py-3 rounded-lg">
                <i class="fas fa-search mr-1"></i> Track Order
            </button>
        </form>

        <p class="text-center mt-4"><a href="{{ route('customer.products.index') }}" class="text-amber-700 hover:underline text-sm">&larr; Back to Shop</a></p>
    </div>
</body>
</html>
