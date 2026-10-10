<!DOCTYPE html>
{{-- data-theme is the dark default, server-rendered so the page is dark even
     with JavaScript off; partials/theme-boot swaps in the saved choice before
     the first paint. --}}
<html lang="en" data-theme="dark">
<head>
    @include('partials.theme-boot')
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
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-full object-cover">
            <span class="font-bold text-lg">World Choice Perfume</span>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">Place Order</h1>
        <p class="text-gray-500 mb-6">Branch: <strong>{{ $branch->name }}</strong> {{ $branch->address ?"- {$branch->address}" : '' }}</p>

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
                        @php
                            // Products bottled in several sizes are ordered as one
                            // size, so the row offers the volumes in stock with the
                            // price of each. How the bottling is boxed, branded or
                            // coloured never changes that price, so it is the
                            // branch that picks the packaging.
                            $buckets = $productVarieties[$stock->product_id] ?? [];
                            $isVariety = ($stock->product->category ?? '') === 'Oil Fragrance' && !empty($buckets);
                            $wanted = $preselect['product_id'] == $stock->product_id;
                            $selectedVolume = $isVariety ? (int) $buckets[0]['volume'] : 0;
                            if ($isVariety && $wanted) {
                                foreach ($buckets as $bucket) {
                                    if ((int) $bucket['volume'] === (int) $preselect['volume']) {
                                        $selectedVolume = (int) $bucket['volume'];
                                    }
                                }
                            }
                            $selectedPrice = (float) $stock->selling_price;
                            foreach ($buckets as $bucket) {
                                if ($isVariety && (int) $bucket['volume'] === $selectedVolume) {
                                    // A size with no price of its own falls back to
                                    // the product's price at this branch.
                                    $picked = (float) ($bucket['price'] ?? 0);
                                    $selectedPrice = $picked > 0 ? $picked : (float) $stock->selling_price;
                                }
                            }
                        @endphp
                        <div class="order-row flex flex-wrap items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                            <label class="flex items-center gap-3 flex-1 min-w-[220px] cursor-pointer">
                                <input type="checkbox" name="items[{{ $loop->index }}][product_id]" value="{{ $stock->product_id }}" class="product-check rounded text-amber-600">
                                @if($stock->product->images->first())
                                    <img src="{{ $stock->product->images->first()->image_url }}" class="w-12 h-12 rounded object-cover">
                                @endif
                                <span class="flex-1 min-w-0">
                                    <span class="block font-medium">{{ $stock->product->name }}</span>
                                    <span class="block text-sm text-gray-500">{{ $stock->product->brand ?? '' }}</span>
                                </span>
                            </label>
                            <input type="hidden" name="items[{{ $loop->index }}][quantity]" value="1" class="item-qty" disabled>

                            @if($isVariety)
                                {{-- One option per size in stock; the hidden field
                                     below carries the chosen size. --}}
                                <select class="variety-select w-full sm:w-auto rounded-lg border px-3 py-2 text-sm {{ $wanted ? 'border-amber-500 ring-1 ring-amber-200' : '' }}" onchange="syncVariety(this)">
                                    @foreach($buckets as $bucket)
                                        @php
                                            $optionPrice = (float) ($bucket['price'] ?? 0) > 0 ? (float) $bucket['price'] : (float) $stock->selling_price;
                                        @endphp
                                        <option value="{{ $bucket['volume'] }}" data-price="{{ $optionPrice }}" @selected((int) $selectedVolume === (int) $bucket['volume'])>
                                            {{ $bucket['label'] }} — TZS {{ number_format($optionPrice) }} ({{ $bucket['available'] }} in stock)
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="items[{{ $loop->index }}][volume]" value="{{ $selectedVolume }}" class="item-volume" disabled>
                            @endif

                            <div class="text-right">
                                @if($isVariety)
                                    <p class="font-bold text-amber-700 item-price">TZS {{ number_format($selectedPrice) }}</p>
                                @else
                                    <p class="font-bold text-amber-700">TZS {{ number_format($stock->selling_price) }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="changeQty(this, -1)" class="w-6 h-6 rounded bg-gray-200 text-sm">-</button>
                                <span class="qty-display w-6 text-center">1</span>
                                <button type="button" onclick="changeQty(this, 1)" class="w-6 h-6 rounded bg-gray-200 text-sm">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" style="background-color: #F89A1E;" class="w-full  hover:opacity-90 text-white font-semibold py-3 rounded-lg text-lg transition">
                <i class="fas fa-paper-plane mr-1"></i> Place Order
            </button>
        </form>
    </div>

    <script>
    function changeQty(btn, delta) {
        const row = btn.closest('.order-row');
        const display = row.querySelector('.qty-display');
        const qtyInput = row.querySelector('.item-qty');
        let val = parseInt(display.textContent) + delta;
        if (val < 1) val = 1;
        display.textContent = val;
        qtyInput.value = val;
    }

    // Keep the ordered size and the price on screen in step with the option the
    // customer picked. The posted value lives in a hidden field so the server
    // receives the size on its own — the packaging follows from the branch.
    function syncVariety(select) {
        const row = select.closest('.order-row');
        const volumeField = row.querySelector('.item-volume');
        if (volumeField) volumeField.value = select.value || '';

        const price = select.options[select.selectedIndex] ? select.options[select.selectedIndex].dataset.price : 0;
        const display = row.querySelector('.item-price');
        if (display) {
            display.textContent = 'TZS ' + (Number(price) || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
        }
    }

    document.querySelectorAll('.product-check').forEach(check => {
        check.addEventListener('change', function() {
            const row = this.closest('.order-row');
            // An unticked row submits nothing, so its own fields have to stop
            // being sent too or the order fails validation on a half-filled row.
            row.querySelectorAll('.item-qty, .item-volume').forEach(field => {
                field.disabled = !this.checked;
            });
            row.classList.toggle('bg-amber-50', this.checked);
        });
    });

    document.querySelectorAll('.variety-select').forEach(select => syncVariety(select));
    </script>
</body>
</html>
