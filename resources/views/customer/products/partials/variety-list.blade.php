{{--
    Variety prices for one branch, with an order button on each option.

    Expects:
      $buckets       [volume => ['label' => ..., 'volume' => ..., 'variants' => [...]]]
      $branchId      the branch these prices belong to
      $fallbackPrice used when a variety has no price of its own
      $product       the product being ordered
--}}
<div class="space-y-4">
    @foreach($buckets as $volume)
        <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $volume['label'] }}</p>
            <div class="space-y-2">
                @foreach($volume['variants'] as $variant)
                    @php
                        // A variety with no price of its own falls back to the
                        // product's price at that branch, exactly as the staff
                        // sale form does.
                        $variantPrice = (float) ($variant['price'] ?? 0) > 0 ? (float) $variant['price'] : (float) $fallbackPrice;
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 rounded-xl bg-dark-800 border border-dark-600">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white">{{ $variant['label'] }}</p>
                            <p class="text-[11px] text-gray-500">{{ $variant['available'] }} in stock</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <p class="text-base font-bold text-gold-400">TZS {{ number_format($variantPrice) }}</p>
                            <a href="{{ route('customer.orders.create', [
                                'product_id' => $product->id,
                                'branch_id' => $branchId,
                                'volume' => $volume['volume'],
                                'variant' => $variant['key'],
                            ]) }}"
                               class="px-4 py-2 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 text-xs font-semibold rounded-lg hover:from-gold-400 hover:to-gold-500 transition">
                                <i class="fas fa-shopping-cart mr-1"></i> Order Now
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
