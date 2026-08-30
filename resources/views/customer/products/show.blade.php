@extends('layouts.public')

@section('title', $product->name . ' — World Choice Perfumes')

@section('content')
<section class="pt-28 pb-16 bg-dark-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-gold-400 transition">Home</a>
            <i class="fas fa-chevron-right text-xs text-gray-600"></i>
            <a href="{{ route('customer.products.index') }}" class="hover:text-gold-400 transition">Shop</a>
            <i class="fas fa-chevron-right text-xs text-gray-600"></i>
            <span class="text-gold-400">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Image -->
            <div class="fade-in">
                <div class="bg-dark-800/50 border border-dark-600 rounded-2xl overflow-hidden aspect-square flex items-center justify-center">
                    @if($product->images->count())
                        <img src="{{ $product->images->first()->image_url }}" alt="{{ $product->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="text-center">
                            <i class="fas fa-spray-can text-6xl text-gold-500/20 mb-4"></i>
                            <p class="text-gray-500">{{ $product->name }}</p>
                        </div>
                    @endif
                </div>
                <!-- Thumbnail Gallery -->
                @if($product->images->count() > 1)
                    <div class="grid grid-cols-4 gap-3 mt-4">
                        @foreach($product->images as $image)
                            <div class="bg-dark-800 border border-dark-600 rounded-xl overflow-hidden aspect-square cursor-pointer hover:border-gold-500/50 transition">
                                <img src="{{ $image->image_url }}" alt="" class="w-full h-full object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div class="fade-in">
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 bg-gold-500/10 text-gold-400 text-xs font-semibold rounded-full border border-gold-500/20 mb-3">
                        {{ $product->category }}
                    </span>
                    <span class="inline-block px-3 py-1 bg-dark-700 text-gray-400 text-xs font-semibold rounded-full border border-dark-600 mb-3 ml-2">
                        {{ $product->brand }}
                    </span>
                </div>

                <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-4">{{ $product->name }}</h1>

                @if($product->description)
                    <p class="text-gray-400 leading-relaxed mb-6">{{ $product->description }}</p>
                @endif

                <!-- Price by Branch -->
                <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-6 mb-6">
                    <h3 class="text-sm font-semibold text-gold-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-tags mr-2"></i> Price by Branch
                    </h3>
                    <div class="space-y-3">
                        @forelse($branchStocks->filter(fn($s) => $s->quantity > 0) as $bs)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-dark-600' : '' }}">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center">
                                        <i class="fas fa-store text-gold-400 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">{{ $bs->branch->name ?? 'Branch #' . $bs->branch_id }}</p>
                                        <p class="text-xs text-gray-500">{{ $bs->quantity }} in stock</p>
                                    </div>
                                </div>
                                <p class="text-lg font-bold text-gold-400">TZS {{ number_format($bs->selling_price) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 py-3">Not currently in stock at any branch.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Selected Branch Price -->
                @if($selectedBranch && $price)
                    <div class="bg-gradient-to-r from-gold-500/10 to-gold-600/5 border border-gold-500/20 rounded-2xl p-6 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gold-400/80">Price at {{ $selectedBranch->name }}</p>
                                <p class="font-display text-4xl font-bold text-gold-400 mt-1">TZS {{ number_format($price) }}</p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('customer.orders.create', ['product_id' => $product->id, 'branch_id' => $selectedBranch->id]) }}"
                                   class="px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25 text-center">
                                    <i class="fas fa-shopping-cart mr-2"></i> Order Now
                                </a>
                                @if($selectedBranch->latitude && $selectedBranch->longitude)
                                    <a href="{{ route('customer.twende-dukani', $selectedBranch->id) }}"
                                       class="px-8 py-3 bg-dark-800 border border-gold-500/30 text-gold-400 font-semibold rounded-xl hover:bg-dark-700 transition text-center text-sm">
                                        <i class="fas fa-walking mr-2"></i> Twende Dukani
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Product Details -->
                <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-6">
                    <h3 class="text-sm font-semibold text-gold-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Product Details
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Brand</p>
                            <p class="text-sm font-medium text-white">{{ $product->brand ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Category</p>
                            <p class="text-sm font-medium text-white">{{ $product->category ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Availability</p>
                            <p class="text-sm font-medium {{ $branchStocks->filter(fn($s) => $s->quantity > 0)->count() ? 'text-green-400' : 'text-red-400' }}">
                                {{ $branchStocks->filter(fn($s) => $s->quantity > 0)->count() ? 'In Stock' : 'Out of Stock' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Total Stock</p>
                            <p class="text-sm font-medium text-white">{{ $branchStocks->sum('quantity') }} units</p>
                        </div>
                    </div>
                </div>

                <!-- Branch Selector to See Price -->
                @if(!$selectedBranch)
                    <div class="mt-6 bg-dark-800/50 border border-gold-500/20 rounded-2xl p-6">
                        <h3 class="text-sm font-semibold text-gold-400 mb-3">
                            <i class="fas fa-store mr-2"></i> Select a branch to see price and order
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($branches as $branch)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('customer.products.show', ['product' => $product->id, 'branch_id' => $branch->id]) }}"
                                       class="px-4 py-2 bg-dark-700 border border-dark-600 rounded-xl text-sm text-gray-300 hover:border-gold-500/30 hover:text-gold-400 transition">
                                        {{ $branch->name }}
                                    </a>
                                    @if($branch->latitude && $branch->longitude)
                                        <a href="{{ route('customer.twende-dukani', $branch->id) }}"
                                           class="px-3 py-2 bg-gold-500/10 border border-gold-500/20 text-gold-400 text-xs font-bold rounded-xl hover:bg-gold-500/20 transition" title="Twende Dukani">
                                            <i class="fas fa-walking"></i>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Back to Shop -->
        <div class="mt-12">
            <a href="{{ route('customer.products.index') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-gold-400 transition">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
        </div>
    </div>
</section>
@endsection
