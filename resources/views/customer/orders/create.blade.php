<!DOCTYPE html>
{{-- Customer-facing website page: always use the dark theme. --}}
<html lang="en" data-theme="dark">
<head>
    @include('partials.theme-boot', ['forceDark' => true])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Place Order - {{ $branch->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('partials.theme-mode')
</head>
<body class="bg-gray-50">
    <nav class="bg-amber-900 text-white shadow-lg">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-full object-cover">
                <span class="font-bold text-lg">World Choice Perfume</span>
            </a>
            <a href="{{ route('customer.products.index', ['branch_id' => $branch->id]) }}"
               class="text-sm text-amber-100 hover:text-white transition">
                <i class="fas fa-arrow-left mr-1"></i> Continue shopping
            </a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">Place Order</h1>
        <p class="text-gray-500 mb-6">Branch: <strong>{{ $branch->name }}</strong> {{ $branch->address ? "- {$branch->address}" : '' }}</p>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        @if($line === null && count($cartItems) === 0)
            <div class="bg-white rounded-xl shadow p-8 text-center">
                <i class="fas fa-basket-shopping text-4xl text-amber-300 mb-4"></i>
                <h2 class="text-lg font-semibold mb-1">Your cart is empty</h2>
                <p class="text-gray-500 mb-5">Pick a perfume from the shop and it will show up here.</p>
                <a href="{{ route('customer.products.index', ['branch_id' => $branch->id]) }}"
                   style="background-color: #F89A1E;"
                   class="inline-block px-6 py-3 rounded-lg text-white font-semibold hover:opacity-90 transition">
                    Browse products
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('customer.orders.store') }}" id="order-form">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                {{-- Which posted line is the product card (the rest are cart lines). --}}
                <input type="hidden" name="current_index" value="{{ $lineIndex }}">

                {{-- 1. Customer details. Kept in the cart so coming back from the
                     shop does not ask for them again. --}}
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h3 class="font-semibold mb-4">Your Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="customer_name" value="{{ old('customer_name', $contact['name']) }}" placeholder="Your Name *" required class="px-3 py-2 border rounded-lg">
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $contact['phone']) }}" placeholder="Phone/WhatsApp Number *" required class="px-3 py-2 border rounded-lg">
                        <input type="email" name="customer_email" value="{{ old('customer_email', $contact['email']) }}" placeholder="Email (optional)" class="px-3 py-2 border rounded-lg md:col-span-2">
                        <textarea name="delivery_notes" placeholder="Delivery notes (optional)" rows="2" class="px-3 py-2 border rounded-lg md:col-span-2">{{ old('delivery_notes', $contact['notes']) }}</textarea>
                    </div>
                </div>

                {{-- 2. Perfumes already chosen with "Choose more products".
                     Their lines ride along as hidden fields, so one Place Order
                     sends the whole order. --}}
                @if(count($cartItems))
                    <div class="bg-white rounded-xl shadow p-6 mb-6">
                        <h3 class="font-semibold mb-1">
                            <i class="fas fa-basket-shopping text-amber-600 mr-1"></i> Your Cart
                            <span class="text-gray-400 font-normal">
                                ({{ count($cartItems) }} {{ count($cartItems) === 1 ? 'item' : 'items' }})
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">Added earlier — adjust the quantity or remove a line, nothing is ordered until you press Place Order.</p>

                        <div class="space-y-3">
                            @foreach($cartItems as $index => $item)
                                <div class="line-row flex flex-wrap items-center gap-3 p-3 border rounded-lg"
                                     data-price="{{ (float) $item['unit_price'] }}"
                                     data-available="{{ (int) ($item['available'] ?? 0) }}">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="items[{{ $index }}][volume]" value="{{ (int) ($item['volume'] ?? 0) }}" class="item-volume">
                                    <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ (int) $item['quantity'] }}" class="item-qty">

                                    @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" class="w-12 h-12 rounded object-cover" alt="">
                                    @endif

                                    <span class="flex-1 min-w-[140px]">
                                        <span class="block font-medium">{{ $item['name'] }}</span>
                                        <span class="block text-sm text-gray-500">
                                            {{ $item['brand'] ?? '' }}
                                            @if(!empty($item['variety_label']))· {{ $item['variety_label'] }}@endif
                                        </span>
                                        <span class="block text-xs text-gray-400">TZS {{ number_format((float) $item['unit_price']) }} each</span>
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="stepQty(this, -1)" class="w-7 h-7 rounded bg-gray-200 text-sm hover:bg-gray-300">-</button>
                                        <span class="qty-display w-7 text-center font-medium">{{ (int) $item['quantity'] }}</span>
                                        <button type="button" onclick="stepQty(this, 1)" class="w-7 h-7 rounded bg-gray-200 text-sm hover:bg-gray-300">+</button>
                                    </div>

                                    <div class="text-right min-w-[90px]">
                                        <p class="font-bold text-amber-700 line-total">TZS {{ number_format((float) $item['unit_price'] * (int) $item['quantity']) }}</p>
                                    </div>

                                    <button type="submit" formaction="{{ route('customer.orders.cart.remove') }}"
                                            name="key" value="{{ $item['key'] }}"
                                            class="text-gray-400 hover:text-red-500 transition" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 3. The one perfume this page is about. A product sold in
                     several sizes offers those sizes; nothing else is listed. --}}
                @if($line !== null)
                    <div class="line-row bg-white rounded-xl shadow p-6 mb-6 border-l-4" style="border-left-color: #F89A1E;"
                         data-price="{{ (float) $line['unit_price'] }}"
                         data-available="{{ (int) $line['available'] }}">
                        <h3 class="font-semibold mb-4">Your Product</h3>

                        <input type="hidden" name="items[{{ $lineIndex }}][product_id]" value="{{ $line['product_id'] }}">
                        <input type="hidden" name="items[{{ $lineIndex }}][quantity]" value="{{ (int) $line['quantity'] }}" class="item-qty">

                        <div class="flex flex-wrap items-center gap-4">
                            @if(!empty($line['image']))
                                <img src="{{ $line['image'] }}" class="w-16 h-16 rounded object-cover" alt="">
                            @endif

                            <div class="flex-1 min-w-[160px]">
                                <p class="font-medium">{{ $line['name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $line['brand'] ?? '' }}</p>
                            </div>

                            <div class="text-right">
                                <p class="font-bold text-amber-700">
                                    <span class="line-unit-price">TZS {{ number_format((float) $line['unit_price']) }}</span>
                                </p>
                                <p class="text-xs text-gray-500 stock-note">{{ (int) $line['available'] }} in stock</p>
                            </div>
                        </div>

                        @if($line['is_variety'])
                            <div class="mt-4">
                                <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Size</label>
                                <select class="variety-select w-full rounded-lg border px-3 py-2 text-sm" onchange="syncVariety(this)">
                                    @foreach($line['buckets'] as $bucket)
                                        @php
                                            $optionPrice = (float) ($bucket['price'] ?? 0) > 0
                                                ? (float) $bucket['price']
                                                : (float) $line['unit_price'];
                                        @endphp
                                        <option value="{{ $bucket['volume'] }}"
                                                data-price="{{ $optionPrice }}"
                                                data-available="{{ (int) $bucket['available'] }}"
                                                @selected((int) $line['volume'] === (int) $bucket['volume'])>
                                            {{ $bucket['label'] }} — TZS {{ number_format($optionPrice) }} ({{ (int) $bucket['available'] }} in stock)
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="items[{{ $lineIndex }}][volume]" value="{{ (int) $line['volume'] }}" class="item-volume">
                            </div>
                        @else
                            <input type="hidden" name="items[{{ $lineIndex }}][volume]" value="0" class="item-volume">
                        @endif

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-4 border-t">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="stepQty(this, -1)" class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300">-</button>
                                <span class="qty-display w-8 text-center font-medium">{{ (int) $line['quantity'] }}</span>
                                <button type="button" onclick="stepQty(this, 1)" class="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300">+</button>
                            </div>

                            <p class="font-bold text-amber-700 text-lg line-total">TZS {{ number_format((float) $line['unit_price'] * (int) $line['quantity']) }}</p>
                        </div>
                    </div>
                @endif

                {{-- 4. Running total, kept in step with the steppers by script. --}}
                <div class="bg-white rounded-xl shadow p-6 mb-6 flex items-center justify-between">
                    <span class="text-gray-600 font-medium">Total</span>
                    <span class="text-2xl font-bold text-amber-700" id="order-total">
                        TZS {{ number_format($cartSubtotal + ($line !== null ? (float) $line['unit_price'] * (int) $line['quantity'] : 0)) }}
                    </span>
                </div>

                {{-- 5. Place this order, or park it in the cart and go pick
                     another perfume. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="submit" style="background-color: #F89A1E;"
                            class="w-full hover:opacity-90 text-white font-semibold py-3 rounded-lg text-lg transition">
                        <i class="fas fa-paper-plane mr-1"></i> Place Order
                    </button>

                    @if($line !== null)
                        <button type="submit" formaction="{{ route('customer.orders.cart.add') }}"
                                class="w-full bg-white border-2 border-amber-500 text-amber-800 font-semibold py-3 rounded-lg text-lg transition hover:bg-amber-50">
                            <i class="fas fa-plus-circle mr-1"></i> Choose More Products
                        </button>
                    @endif
                </div>

                @if($line !== null)
                    <p class="text-center text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Choose More Products keeps this in your cart and takes you back to the shop.
                    </p>
                @endif
            </form>
        @endif
    </div>

    <script>
    // One line row = one perfume in one size. The hidden quantity field is what
    // the form posts; the stepper is only the way to change it.
    function qtyOf(row) {
        return parseInt(row.querySelector('.item-qty').value, 10) || 1;
    }

    function priceOf(row) {
        const select = row.querySelector('.variety-select');
        if (select && select.selectedIndex >= 0) {
            return Number(select.options[select.selectedIndex].dataset.price) || 0;
        }
        return Number(row.dataset.price) || 0;
    }

    function availableOf(row) {
        const select = row.querySelector('.variety-select');
        if (select && select.selectedIndex >= 0) {
            return parseInt(select.options[select.selectedIndex].dataset.available, 10) || 0;
        }
        return parseInt(row.dataset.available, 10) || 0;
    }

    function setQty(row, value) {
        let qty = Math.max(1, Math.round(value));
        const available = availableOf(row);
        if (available > 0 && qty > available) qty = available;

        row.querySelector('.item-qty').value = qty;
        row.querySelector('.qty-display').textContent = qty;
    }

    function stepQty(button, delta) {
        const row = button.closest('.line-row');
        setQty(row, qtyOf(row) + delta);
        recalc();
    }

    // Keep the chosen size, its price and its stock on screen in step with the
    // option the customer picked. The posted value lives in the hidden field so
    // the server receives the size on its own — packaging follows the branch.
    function syncVariety(select) {
        const row = select.closest('.line-row');
        const option = select.options[select.selectedIndex];

        row.querySelector('.item-volume').value = select.value;
        row.dataset.price = option.dataset.price;
        row.dataset.available = option.dataset.available;

        const unit = row.querySelector('.line-unit-price');
        if (unit) unit.textContent = 'TZS ' + (Number(option.dataset.price) || 0).toLocaleString('en-US');

        const stock = row.querySelector('.stock-note');
        if (stock) stock.textContent = (option.dataset.available || 0) + ' in stock';

        setQty(row, qtyOf(row));
        recalc();
    }

    function recalc() {
        let total = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const line = priceOf(row) * qtyOf(row);
            const label = row.querySelector('.line-total');
            if (label) label.textContent = 'TZS ' + line.toLocaleString('en-US');
            total += line;
        });

        const grand = document.getElementById('order-total');
        if (grand) grand.textContent = 'TZS ' + total.toLocaleString('en-US');
    }

    document.querySelectorAll('.variety-select').forEach(select => syncVariety(select));
    recalc();
    </script>
</body>
</html>
