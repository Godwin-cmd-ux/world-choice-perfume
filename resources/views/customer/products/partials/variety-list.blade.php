{{--
    Sizes a product is bottled in at one branch, with an order button on each.

    Only volumes reach the customer: how a bottling is boxed, branded or
    coloured never changes what it costs, so the branch packs whichever of its
    buckets fits the size that was ordered.

    Expects:
      $buckets       [['volume' => 50, 'label' => '50ml', 'available' => 7, 'price' => 45000], ...]
      $branchId      the branch these prices belong to
      $fallbackPrice used when a size has no price of its own
      $product       the product being ordered
--}}
<div class="space-y-2">
    @foreach($buckets as $bucket)
        @php
            // A size with no price of its own falls back to the product's
            // price at that branch, exactly as the staff sale form does.
            $bucketPrice = (float) ($bucket['price'] ?? 0) > 0 ? (float) $bucket['price'] : (float) $fallbackPrice;
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 rounded-xl bg-dark-800 border border-dark-600">
            <div class="min-w-0">
                <p class="text-sm font-medium text-white">{{ $bucket['label'] }}</p>
                <p class="text-[11px] text-gray-500">{{ $bucket['available'] }} in stock</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-base font-bold text-gold-400">TZS {{ number_format($bucketPrice) }}</p>
                <a href="{{ route('customer.orders.create', [
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'volume' => $bucket['volume'],
                ]) }}"
                   class="px-4 py-2 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 text-xs font-semibold rounded-lg hover:from-gold-400 hover:to-gold-500 transition">
                    <i class="fas fa-shopping-cart mr-1"></i> Order Now
                </a>
            </div>
        </div>
    @endforeach
</div>
