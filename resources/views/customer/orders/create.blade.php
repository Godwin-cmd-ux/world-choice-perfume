<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Place Order - {{ $branch->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-amber-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-full object-cover">
            <span class="font-bold text-lg">World Choice Perfumes</span>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">Place Order</h1>
        <p class="text-gray-500 mb-6">Branch: <strong>{{ $branch->name }}</strong> {{ $branch->address ? "- {$branch->address}" : '' }}</p>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.orders.store') }}">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branch->id }}">

            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="font-semibold mb-4">Your Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Your Name *" required class="px-3 py-2 border rounded-lg">
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Phone/WhatsApp Number *" required class="px-3 py-2 border rounded-lg">
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="Email (optional)" class="px-3 py-2 border rounded-lg md:col-span-2">
                    <textarea name="delivery_notes" placeholder="Delivery notes (optional)" rows="2" class="px-3 py-2 border rounded-lg md:col-span-2">{{ old('delivery_notes') }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="font-semibold mb-4">Select Products</h3>
                <div class="space-y-3" id="items-container">
                    @foreach($products as $stock)
                        <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="items[{{ $loop->index }}][product_id]" value="{{ $stock->product_id }}" class="product-check rounded text-amber-600">
                            <input type="hidden" name="items[{{ $loop->index }}][quantity]" value="1" class="item-qty" disabled>
                            @if($stock->product->images->first())
                                <img src="{{ $stock->product->images->first()->image_url }}" class="w-12 h-12 rounded object-cover">
                            @endif
                            <div class="flex-1">
                                <p class="font-medium">{{ $stock->product->name }}</p>
                                <p class="text-sm text-gray-500">{{ $stock->product->brand ?? '' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-amber-700">TZS {{ number_format($stock->selling_price) }}</p>
                                <p class="text-xs text-gray-400">{{ $stock->quantity }} in stock</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="changeQty(this, -1)" class="w-6 h-6 rounded bg-gray-200 text-sm">-</button>
                                <span class="qty-display w-6 text-center">1</span>
                                <button type="button" onclick="changeQty(this, 1)" class="w-6 h-6 rounded bg-gray-200 text-sm">+</button>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-semibold py-3 rounded-lg text-lg transition">
                <i class="fas fa-paper-plane mr-1"></i> Place Order
            </button>
        </form>
    </div>

    <script>
    function changeQty(btn, delta) {
        const row = btn.closest('label');
        const display = row.querySelector('.qty-display');
        const qtyInput = row.querySelector('.item-qty');
        const check = row.querySelector('.product-check');
        let val = parseInt(display.textContent) + delta;
        if (val < 1) val = 1;
        display.textContent = val;
        qtyInput.value = val;
    }

    document.querySelectorAll('.product-check').forEach(check => {
        check.addEventListener('change', function() {
            const row = this.closest('label');
            row.querySelector('.item-qty').disabled = !this.checked;
            row.classList.toggle('bg-amber-50', this.checked);
        });
    });
    </script>
</body>
</html>
