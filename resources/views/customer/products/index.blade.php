@extends('layouts.public')

@section('title', 'Shop — World Choice Perfumes')

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
                    <span class="text-white">Our </span><span class="gold-text">Collection</span>
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
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
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
                <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-6 sticky top-28">
                    <!-- Branch Selector -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gold-400 uppercase tracking-wider mb-4">
                            <i class="fas fa-store mr-2"></i> Select Branch
                        </h3>
                        <div class="space-y-2">
                            <a href="{{ route('customer.products.index', array_merge(request()->except('branch_id'), ['branch_id' => ''])) }}"
                               class="block px-4 py-3 rounded-xl text-sm transition {{ !$selectedBranch ? 'bg-gold-500/10 border border-gold-500/30 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white border border-transparent' }}">
                                <i class="fas fa-globe mr-2"></i> All Branches
                            </a>
                            @foreach($branches as $branch)
                                <div class="relative">
                                    <a href="{{ route('customer.products.index', array_merge(request()->except('branch_id'), ['branch_id' => $branch->id])) }}"
                                       class="block px-4 py-3 rounded-xl text-sm transition {{ $selectedBranch && $selectedBranch->id === $branch->id ? 'bg-gold-500/10 border border-gold-500/30 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white border border-transparent' }}">
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

                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gold-400 uppercase tracking-wider mb-4">
                            <i class="fas fa-filter mr-2"></i> Category
                        </h3>
                        <div class="space-y-1">
                            @foreach(['Men', 'Women', 'Unisex', 'Gift Set', 'Accessories'] as $cat)
                                @php
                                    $catParams = request()->except('category');
                                    if (request('category') !== $cat) {
                                        $catParams['category'] = $cat;
                                    }
                                @endphp
                                <a href="{{ route('customer.products.index', $catParams) }}"
                                   class="block px-4 py-2.5 rounded-lg text-sm transition {{ request('category') === $cat ? 'bg-gold-500/10 text-gold-400 font-medium' : 'text-gray-400 hover:bg-dark-700 hover:text-white' }}">
                                    {{ $cat }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['branch_id', 'category', 'search']))
                        <a href="{{ route('customer.products.index') }}" class="block w-full text-center px-4 py-3 rounded-xl border border-dark-600 text-gray-400 hover:text-white hover:border-gray-500 transition text-sm">
                            <i class="fas fa-times mr-1"></i> Clear All Filters
                        </a>
                    @endif
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
                    <!-- No Branch Selected — Show All Products -->
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mb-6">
                            <i class="fas fa-store text-gold-400 text-2xl"></i>
                        </div>
                        <h3 class="font-display text-2xl font-bold text-white mb-3">Select a Branch to Browse Products</h3>
                        <p class="text-gray-400 max-w-md mx-auto mb-8">
                            Choose a branch location from the sidebar to see available products, prices, and stock.
                        </p>
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach($branches as $branch)
                                <div class="flex flex-col items-center gap-2">
                                    <a href="{{ route('customer.products.index', ['branch_id' => $branch->id]) }}"
                                       class="px-6 py-3 bg-dark-800 border border-dark-600 rounded-xl text-sm text-gray-300 hover:border-gold-500/30 hover:text-gold-400 hover:bg-dark-700 transition-all">
                                        <i class="fas fa-map-marker-alt mr-2 text-gold-500/60"></i> {{ $branch->name }}
                                    </a>
                                    @if($branch->latitude && $branch->longitude)
                                        <a href="{{ route('customer.twende-dukani', $branch->id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-gold-500/10 border border-gold-500/20 text-gold-400 text-xs font-semibold rounded-lg hover:bg-gold-500/20 transition">
                                            <i class="fas fa-walking"></i> Twende Dukani
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($products->isEmpty())
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mb-6">
                            <i class="fas fa-search text-gray-500 text-2xl"></i>
                        </div>
                        <h3 class="font-display text-2xl font-bold text-white mb-3">No Products Found</h3>
                        <p class="text-gray-400 max-w-md mx-auto">
                            @if(request('search'))
                                No products match "{{ request('search') }}" at {{ $selectedBranch->name }}. Try a different search.
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
                                    <!-- Stock Badge -->
                                    @if($stock->quantity <= 5)
                                        <span class="absolute top-3 right-3 px-2.5 py-1 bg-red-500/20 text-red-400 text-[10px] font-semibold rounded-full border border-red-500/30">
                                            Only {{ $stock->quantity }} left
                                        </span>
                                    @endif
                                    <!-- Category Badge -->
                                    <span class="absolute top-3 left-3 px-2.5 py-1 bg-dark-900/80 backdrop-blur-sm text-gold-400 text-[10px] font-semibold rounded-full border border-dark-600">
                                        {{ $stock->product->category }}
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
                                            <p class="text-2xl font-bold text-gold-400">TZS {{ number_format($stock->selling_price) }}</p>
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
@endsection
