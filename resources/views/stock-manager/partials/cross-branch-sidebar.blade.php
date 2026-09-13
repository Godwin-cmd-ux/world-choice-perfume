<nav class="w-full md:w-44 shrink-0 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white md:min-h-[520px] md:overflow-y-auto">
    @php
        $cbLinks = [
            ['route' => ['stock-manager.dashboard'], 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ];
        $cbStockLinks = [
            ['route' => ['stock-manager.product-stock'], 'icon' => 'fa-box-open', 'label' => 'Product Stock'],
            ['route' => ['stock-manager.bottle-stock'], 'icon' => 'fa-wine-bottle', 'label' => 'Bottle Stock'],
            ['route' => ['stock-manager.bottle-accessories.index'], 'icon' => 'fa-cogs', 'label' => 'Bottle Accessories'],
            ['route' => ['stock-manager.oil-fragrance'], 'icon' => 'fa-flask', 'label' => 'Oil Fragrance'],
        ];
        $cbMovementLinks = [
            ['route' => ['stock-manager.product-stock-movements'], 'icon' => 'fa-arrows-alt-h', 'label' => 'Product Movements'],
            ['route' => ['stock-manager.bottle-movements'], 'icon' => 'fa-wine-bottle', 'label' => 'Bottle Movements'],
            ['route' => ['stock-manager.oil-fragrance-movements'], 'icon' => 'fa-flask', 'label' => 'Oil Movements'],
            ['route' => ['stock-manager.bottle-accessories.movements'], 'icon' => 'fa-cogs', 'label' => 'Accessory Movements'],
        ];
    @endphp

    <div class="p-3 border-b border-white/10 md:hidden">
        <p class="text-[10px] text-emerald-400 uppercase tracking-widest">Shared Branch</p>
    </div>

    <div class="px-2 py-2 space-y-0.5">
        @foreach($cbLinks as $item)
            <a href="{{ route($item['route'][0]) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 {{ request()->routeIs(...$item['route']) ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <i class="fas {{ $item['icon'] }} w-4 text-center"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <p class="px-3 pt-3 mt-2 text-[10px] font-semibold text-gray-500 uppercase tracking-widest border-t border-white/10">Stock</p>

        @foreach($cbStockLinks as $item)
            <a href="{{ route($item['route'][0]) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 {{ request()->routeIs(...$item['route']) ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <i class="fas {{ $item['icon'] }} w-4 text-center"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <p class="px-3 pt-3 mt-2 text-[10px] font-semibold text-gray-500 uppercase tracking-widest border-t border-white/10">Movements</p>

        @foreach($cbMovementLinks as $item)
            <a href="{{ route($item['route'][0]) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 {{ request()->routeIs(...$item['route']) ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <i class="fas {{ $item['icon'] }} w-4 text-center"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>