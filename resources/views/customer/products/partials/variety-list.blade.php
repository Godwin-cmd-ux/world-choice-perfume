{{--
    The sizes a product is bottled in, merged across every branch.

    Only volumes reach the customer: how a bottling is boxed, branded or
    coloured never changes what it costs, so the branch packs whichever of its
    buckets fits the size that was ordered. Branch names, source stock and
    quantities are deliberately absent — the only destination is Mikocheni HQ.

    Expects:
      $buckets        [['volume' => 50, 'label' => '50ml', 'price' => 45000], ...]
      $orderBranchId  the HQ branch id every order is routed to
      $product        the product being ordered
--}}
<div class="space-y-2">
    @foreach($buckets as $bucket)
        @php
            $bucketPrice = (float) ($bucket['price'] ?? 0);
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 rounded-xl bg-dark-800 border border-dark-600">
            <p class="text-sm font-medium text-white">{{ $bucket['label'] }}</p>
            <div class="flex items-center gap-3">
                @if($bucketPrice > 0)
                    <p class="text-base font-bold text-gold-400">TZS {{ number_format($bucketPrice) }}</p>
                @endif
                <a href="{{ route('customer.orders.create', [
                    'product_id' => $product->id,
                    'branch_id' => $orderBranchId,
                    'volume' => $bucket['volume'],
                ]) }}"
                   class="px-4 py-2 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 text-xs font-semibold rounded-lg hover:from-gold-400 hover:to-gold-500 transition">
                    <i class="fas fa-shopping-cart mr-1"></i> Order Now
                </a>
            </div>
        </div>
    @endforeach
</div>
