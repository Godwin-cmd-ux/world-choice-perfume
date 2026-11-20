@extends('layouts.public')

@section('title', $product->name . ' — World Choice Perfume')

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
                        {{ $product->sex_category ? ucwords($product->sex_category) : ($product->category ?? '') }}
                    </span>
                    <span class="inline-block px-3 py-1 bg-dark-700 text-gray-400 text-xs font-semibold rounded-full border border-dark-600 mb-3 ml-2">
                        {{ $product->brand }}
                    </span>
                </div>

                <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-4">{{ $product->name }}</h1>

                @if($product->description)
                    <p class="text-gray-400 leading-relaxed mb-6">{{ $product->description }}</p>
                @endif

                {{-- One company-wide availability answer. Where the product is
                     stocked, how much is there and how to reach that shelf are
                     internal details and are deliberately not rendered. Every
                     order goes to Mikocheni HQ (branch id 10). --}}
                <div class="bg-gradient-to-r from-gold-500/10 to-gold-600/5 border border-gold-500/20 rounded-2xl p-6 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-gold-400/80">Availability</p>
                            <p class="font-display text-2xl font-bold mt-1 {{ $isAvailable ? 'text-green-400' : 'text-red-400' }}">
                                {{ $isAvailable ? 'Available' : 'Out of Stock' }}
                            </p>
                        </div>
                        @if($isAvailable)
                            <a href="{{ route('customer.orders.create', ['product_id' => $product->id, 'branch_id' => $orderBranchId]) }}"
                               class="px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25 text-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Order Now
                            </a>
                        @endif
                    </div>

                    @if($isAvailable && count($varieties))
                        <div class="mt-5 pt-5 border-t border-gold-500/20">
                            <p class="text-sm font-semibold text-gold-400 mb-1">Depends on size</p>
                            <p class="text-xs text-gray-400 mb-4">Choose a size to order.</p>
                            @include('customer.products.partials.variety-list', [
                                'buckets' => $varieties,
                                'orderBranchId' => $orderBranchId,
                                'product' => $product,
                            ])
                        </div>
                    @endif
                </div>

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
                            <p class="text-sm font-medium text-white capitalize">{{ $product->sex_category ?? ($product->category ?? 'N/A') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Availability</p>
                            <p class="text-sm font-medium {{ $isAvailable ? 'text-green-400' : 'text-red-400' }}">
                                {{ $isAvailable ? 'Available' : 'Out of Stock' }}
                            </p>
                        </div>
                    </div>
                </div>
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
