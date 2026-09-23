@extends('layouts.public')

@section('title', 'Shop — World Choice Perfume')

@section('content')
<!-- Page Header -->
<section class="pt-28 pb-12 bg-dark-900/50 border-b border-dark-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
            <div>
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <a href="{{ route('home') }}" class="hover:text-gold-400 transition">Home</a>
                    <i class="fas fa-chevron-right text-xs text-gray-600"></i>
                    <span class="text-gold-400">Shop</span>
                </nav>
                <h1 class="font-display text-3xl sm:text-4xl font-bold">
                    <span class="text-white">New </span><span class="gold-text">Arrivals</span>
                </h1>
                @if($selectedBranch)
                    <p class="text-gray-400 mt-2">
                        Showing products at <span class="text-gold-400 font-medium">{{ $selectedBranch->name }}</span>
                    </p>
                @endif
            </div>

            <!-- Search -->
            <form action="{{ route('customer.products.index') }}" method="GET" class="flex-1 max-w-md">
                @if(request('branch_id'))
                    <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                @endif
                @if(request('sex_category'))
                    <input type="hidden" name="sex_category" value="{{ request('sex_category') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('fundamental_ingredient'))
                    <input type="hidden" name="fundamental_ingredient" value="{{ request('fundamental_ingredient') }}">
                @endif
                @if(request('brand'))
                    <input type="hidden" name="brand" value="{{ request('brand') }}">
                @endif
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, brand, or category..."
                        class="w-full pl-12 pr-4 py-3 bg-dark-800 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none">
                    <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gold-400 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="py-12 bg-dark-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Sidebar Filters -->
            <aside class="w-full lg:w-72 flex-shrink-0">
                @php
                    $sexOptions = ['male' => 'Male', 'female' => 'Female', 'unisex' => 'Unisex', 'accessories' => 'Accessories'];
                    $typeOptions = ['Oil Fragrance' => 'Oil Fragrance', 'Brand Perfume' => 'Brand Perfume'];
                    $scentOptions = ['Floral', 'Fresh/Citrus', 'Wood', 'Amber/Spicy', 'Fruity', 'Oud', 'Gourmand', 'Aromatic'];
                @endphp
                <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-4 sticky top-28 space-y-2.5">

                    <!-- Branch Selector -->
                    <div class="filter-group" data-filter-group>
                        <button type="button" data-filter-toggle
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition {{ $selectedBranch ? 'border-gold-500/30 bg-gold-500/5' : 'border-dark-600 hover:border-gray-500' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas fa-store text-gold-400 text-sm w-4 text-center"></i>
                                <span class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-500">Select Branch</span>
                                    <span class="block truncate text-sm font-medium {{ $selectedBranch ? 'text-gold-400' : 'text-white' }}">{{ $selectedBranch?->name ?? 'All Branches' }}</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs chevron transition-transform duration-300"></i>
                        </button>
                        <div class="filter-panel">
                            <div class="pt-2 space-y-1">
                                <a href="{{ route('customer.products.index', request()->except('branch_id')) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ !$selectedBranch ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    <i class="fas fa-globe mr-2"></i> All Branches
                                </a>
                                @foreach($branches as $branch)
                                    <div class="relative">
                                        <a href="{{ route('customer.products.index', array_merge(request()->except('branch_id'), ['branch_id' => $branch->id])) }}"
                                           class="block px-4 py-2.5 pr-12 rounded-lg text-sm transition {{ $selectedBranch && $selectedBranch->id === $branch->id ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                            <i class="fas fa-map-marker-alt mr-2 text-xs"></i> {{ $branch->name }}
                                        </a>
                                        @if($branch->latitude && $branch->longitude)
                                            <a href="{{ route('customer.twende-dukani', $branch->id) }}"
                                               class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gold-500/10 border border-gold-500/20 text-gold-400 text-[10px] font-bold rounded-md hover:bg-gold-500/20 transition" title="Twende Dukani">
                                                <i class="fas fa-walking"></i>
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Sex Category Filter -->
                    <div class="filter-group" data-filter-group>
                        <button type="button" data-filter-toggle
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition {{ request('sex_category') ? 'border-gold-500/30 bg-gold-500/5' : 'border-dark-600 hover:border-gray-500' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas fa-venus-mars text-gold-400 text-sm w-4 text-center"></i>
                                <span class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-500">Sex Category</span>
                                    <span class="block truncate text-sm font-medium {{ request('sex_category') ? 'text-gold-400' : 'text-white' }}">{{ $sexOptions[request('sex_category')] ?? 'All' }}</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs chevron transition-transform duration-300"></i>
                        </button>
                        <div class="filter-panel">
                            <div class="pt-2 space-y-1">
                                <a href="{{ route('customer.products.index', request()->except('sex_category')) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ !request('sex_category') ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    <i class="fas fa-globe mr-2"></i> All
                                </a>
                                @foreach($sexOptions as $value => $label)
                                    <a href="{{ route('customer.products.index', array_merge(request()->except('sex_category'), ['sex_category' => $value])) }}"
                                       class="block px-4 py-2.5 rounded-lg text-sm transition {{ request('sex_category') === $value ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Product Type Filter -->
                    <div class="filter-group" data-filter-group>
                        <button type="button" data-filter-toggle
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition {{ request('category') ? 'border-gold-500/30 bg-gold-500/5' : 'border-dark-600 hover:border-gray-500' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas fa-tag text-gold-400 text-sm w-4 text-center"></i>
                                <span class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-500">Product Type</span>
                                    <span class="block truncate text-sm font-medium {{ request('category') ? 'text-gold-400' : 'text-white' }}">{{ request('category') ?: 'All Types' }}</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs chevron transition-transform duration-300"></i>
                        </button>
                        <div class="filter-panel">
                            <div class="pt-2 space-y-1">
                                <a href="{{ route('customer.products.index', request()->except('category')) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ !request('category') ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    All Types
                                </a>
                                @foreach($typeOptions as $value => $label)
                                    <a href="{{ route('customer.products.index', array_merge(request()->except('category'), ['category' => $value])) }}"
                                       class="block px-4 py-2.5 rounded-lg text-sm transition {{ request('category') === $value ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Fundamental Ingredient Filter -->
                    <div class="filter-group" data-filter-group>
                        <button type="button" data-filter-toggle
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition {{ request('fundamental_ingredient') ? 'border-gold-500/30 bg-gold-500/5' : 'border-dark-600 hover:border-gray-500' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas fa-flask text-gold-400 text-sm w-4 text-center"></i>
                                <span class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-500">Scent Family</span>
                                    <span class="block truncate text-sm font-medium {{ request('fundamental_ingredient') ? 'text-gold-400' : 'text-white' }}">{{ request('fundamental_ingredient') ?: 'All Scent Families' }}</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs chevron transition-transform duration-300"></i>
                        </button>
                        <div class="filter-panel">
                            <div class="pt-2 space-y-1">
                                <a href="{{ route('customer.products.index', request()->except('fundamental_ingredient')) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ !request('fundamental_ingredient') ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    All Scent Families
                                </a>
                                @foreach($scentOptions as $ingredient)
                                    <a href="{{ route('customer.products.index', array_merge(request()->except('fundamental_ingredient'), ['fundamental_ingredient' => $ingredient])) }}"
                                       class="block px-4 py-2.5 rounded-lg text-sm transition {{ request('fundamental_ingredient') === $ingredient ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                        {{ $ingredient }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <div class="filter-group" data-filter-group>
                        <button type="button" data-filter-toggle
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition {{ request('brand') ? 'border-gold-500/30 bg-gold-500/5' : 'border-dark-600 hover:border-gray-500' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <i class="fas fa-crown text-gold-400 text-sm w-4 text-center"></i>
                                <span class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider text-gray-500">Brand</span>
                                    <span class="block truncate text-sm font-medium {{ request('brand') ? 'text-gold-400' : 'text-white' }}">{{ request('brand') ?: 'All Brands' }}</span>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs chevron transition-transform duration-300"></i>
                        </button>
                        <div class="filter-panel">
                            <div class="pt-2 space-y-1">
                                <a href="{{ route('customer.products.index', request()->except('brand')) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ !request('brand') ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    All Brands
                                </a>
                                @foreach($availableBrands as $brandName)
                                    <a href="{{ route('customer.products.index', array_merge(request()->except('brand'), ['brand' => $brandName])) }}"
                                       class="block px-4 py-2.5 rounded-lg text-sm transition {{ request('brand') === $brandName ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                        {{ $brandName }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    @php $hasFilters = request()->hasAny(['branch_id', 'sex_category', 'search', 'category', 'fundamental_ingredient', 'brand']); @endphp
                    <a href="{{ route('customer.products.index') }}"
                       class="flex items-center justify-center gap-2 w-full text-center px-4 py-3 rounded-xl border transition text-sm {{ $hasFilters ? 'border-gold-500/30 text-gold-400 hover:bg-gold-500/10' : 'border-dark-600 text-gray-500 hover:text-gray-300 hover:border-gray-500' }}">
                        <i class="fas fa-times"></i> Clear All Selections
                    </a>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1">
                @if($selectedBranch && $products->count() > 0)
                    <div class="mb-6 flex items-center justify-between">
                        <p class="text-sm text-gray-400">
                            <span class="font-semibold text-white">{{ $products->count() }}</span> products available at {{ $selectedBranch->name }}
                        </p>
                    </div>
                @endif

                @if(!$selectedBranch)
                    <!-- All Branches — Show all products or empty state -->
                    @if($products->isEmpty())
                        <div class="text-center py-20">
                            <div class="w-20 h-20 mx-auto rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mb-6">
                                <i class="fas fa-store text-gold-400 text-2xl"></i>
                            </div>
                            <h3 class="font-display text-2xl font-bold text-white mb-3">No Products Available</h3>
                            <p class="text-gray-400 max-w-md mx-auto mb-8">
                                No products are currently in stock across any branch. Check back later!
                            </p>
                        </div>
                    @else
                        <!-- Products Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($products as $stock)
                                <a href="{{ route('customer.products.show', ['product' => $stock->product_id, 'branch_id' => $stock->branch_id]) }}"
                                   class="group bg-dark-800/50 border border-dark-600 rounded-2xl overflow-hidden card-hover">
                                    <!-- Product Image -->
                                    <div class="relative h-56 bg-gradient-to-br from-dark-700 to-dark-800 flex items-center justify-center overflow-hidden">
                                        @if($stock->product->images->count())
                                            <img src="{{ $stock->product->images->first()->image_url }}" alt="{{ $stock->product->name }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <div class="text-center">
                                                <i class="fas fa-spray-can text-4xl text-gold-500/20 mb-2"></i>
                                                <p class="text-xs text-gray-600">{{ $stock->product->name }}</p>
                                            </div>
                                        @endif
                                        <!-- Availability -->
                                        @if($stock->quantity <= 0)
                                            <span class="absolute top-3 right-3 px-2.5 py-1 bg-red-600 text-white text-[10px] font-bold rounded-full shadow-lg shadow-red-900/50">
                                                Out of Stock
                                            </span>
                                        @endif
                                        <!-- Category Badges: product type + sex category -->
                                        <div class="absolute top-3 left-3 flex flex-col items-start gap-1.5">
                                            <span class="px-2.5 py-1 backdrop-blur-sm text-[10px] font-bold rounded-full border {{ $stock->product->category === 'Oil Fragrance' ? 'bg-purple-500/20 text-purple-300 border-purple-500/40' : 'bg-gold-500/15 text-gold-400 border-gold-500/40' }}">
                                                {{ $stock->product->category }}
                                            </span>
                                            @if($stock->product->sex_category)
                                                <span class="px-2.5 py-1 bg-dark-900/80 backdrop-blur-sm text-gray-300 text-[10px] font-semibold rounded-full border border-dark-600">
                                                    {{ ucwords($stock->product->sex_category) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="p-5">
                                        <p class="text-[10px] font-semibold text-gold-400/60 uppercase tracking-wider mb-1">{{ $stock->product->brand }}</p>
                                        <h3 class="font-display text-lg font-bold text-white group-hover:text-gold-400 transition line-clamp-1">
                                            {{ $stock->product->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $stock->product->description }}</p>

                                        <div class="flex items-end justify-between mt-4 pt-4 border-t border-dark-600">
                                            <div>
                                                @if($stock->quantity > 0)
                                                    <p class="text-2xl font-bold text-gold-400">TZS {{ number_format($stock->selling_price) }}</p>
                                                @else
                                                    <p class="text-sm font-medium text-gray-500">Check availability</p>
                                                @endif
                                            </div>
                                            <span class="px-3 py-1.5 bg-gold-500/10 text-gold-400 text-xs font-medium rounded-lg border border-gold-500/20 group-hover:bg-gold-500/20 transition">
                                                View Details
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @elseif($products->isEmpty())
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mb-6">
                            <i class="fas fa-search text-gray-500 text-2xl"></i>
                        </div>
                        <h3 class="font-display text-2xl font-bold text-white mb-3">No Products Found</h3>
                        <p class="text-gray-400 max-w-md mx-auto">
                            @if(request('search'))
                                No products match"{{ request('search') }}" at {{ $selectedBranch->name }}. Try a different search.
                            @else
                                No products are currently available at {{ $selectedBranch->name }}.
                            @endif
                        </p>
                    </div>
                @else
                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($products as $stock)
                            <a href="{{ route('customer.products.show', ['product' => $stock->product_id, 'branch_id' => $selectedBranch->id]) }}"
                               class="group bg-dark-800/50 border border-dark-600 rounded-2xl overflow-hidden card-hover">
                                <!-- Product Image -->
                                <div class="relative h-56 bg-gradient-to-br from-dark-700 to-dark-800 flex items-center justify-center overflow-hidden">
                                    @if($stock->product->images->count())
                                        <img src="{{ $stock->product->images->first()->image_url }}" alt="{{ $stock->product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    @else
                                        <div class="text-center">
                                            <i class="fas fa-spray-can text-4xl text-gold-500/20 mb-2"></i>
                                            <p class="text-xs text-gray-600">{{ $stock->product->name }}</p>
                                        </div>
                                    @endif
                                    <!-- Availability -->
                                    @if($stock->quantity <= 0)
                                        <span class="absolute top-3 right-3 px-2.5 py-1 bg-red-600 text-white text-[10px] font-bold rounded-full shadow-lg shadow-red-900/50">
                                            Out of Stock
                                        </span>
                                    @endif
                                    <!-- Category Badge -->
                                    <span class="absolute top-3 left-3 px-2.5 py-1 bg-dark-900/80 backdrop-blur-sm text-gold-400 text-[10px] font-semibold rounded-full border border-dark-600">
                                        {{ $stock->product->sex_category ? ucwords($stock->product->sex_category) : ($stock->product->category ?? '') }}
                                    </span>
                                </div>

                                <!-- Product Info -->
                                <div class="p-5">
                                    <p class="text-[10px] font-semibold text-gold-400/60 uppercase tracking-wider mb-1">{{ $stock->product->brand }}</p>
                                    <h3 class="font-display text-lg font-bold text-white group-hover:text-gold-400 transition line-clamp-1">
                                        {{ $stock->product->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $stock->product->description }}</p>

                                    <div class="flex items-end justify-between mt-4 pt-4 border-t border-dark-600">
                                        <div>
                                            @if($stock->quantity > 0)
                                                <p class="text-2xl font-bold text-gold-400">TZS {{ number_format($stock->selling_price) }}</p>
                                            @else
                                                <p class="text-sm font-medium text-gray-500">Check availability</p>
                                            @endif
                                        </div>
                                        <span class="px-3 py-1.5 bg-gold-500/10 text-gold-400 text-xs font-medium rounded-lg border border-gold-500/20 group-hover:bg-gold-500/20 transition">
                                            View Details
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Clear a stale search token when the page is genuinely refreshed, so the
     search bar (and results) don't keep the old keyword after a reload. --}}
<script>
    (function () {
        var entries = window.performance && performance.getEntriesByType
            ? performance.getEntriesByType('navigation')
            : [];
        var isReload = entries.length > 0 && entries[0].type === 'reload';
        if (!isReload) { return; }

        var params = new URLSearchParams(window.location.search);
        if (!params.has('search')) { return; }

        params.delete('search');
        var query = params.toString();
        window.location.replace(window.location.pathname + (query ? '?' + query : ''));
    })();
</script>
@endsection

@push('styles')
<style>
    .filter-panel {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s ease;
    }
    .filter-group.open .filter-panel {
        max-height: 22rem;
        overflow-y: auto;
    }
    .filter-group.open .chevron {
        transform: rotate(180deg);
    }
    .filter-panel::-webkit-scrollbar { width: 4px; }
    .filter-panel::-webkit-scrollbar-thumb { background: #424242; border-radius: 4px; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var groups = Array.prototype.slice.call(document.querySelectorAll('[data-filter-group]'));

        function closeAll(except) {
            groups.forEach(function (group) {
                if (group !== except) group.classList.remove('open');
            });
        }

        groups.forEach(function (group) {
            var toggle = group.querySelector('[data-filter-toggle]');
            if (!toggle) return;

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var isOpen = group.classList.contains('open');
                closeAll(group);
                group.classList.toggle('open', !isOpen);
            });

            // Collapse immediately when an option is chosen (the link then reloads
            // the filtered page, which keeps every group collapsed by default).
            group.querySelectorAll('.filter-panel a').forEach(function (link) {
                link.addEventListener('click', function () {
                    group.classList.remove('open');
                });
            });
        });
    })();
</script>
@endpush
