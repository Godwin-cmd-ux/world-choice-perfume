@extends('layouts.public')

@section('title', 'All Brands — World Choice Perfume')

@section('content')
<section class="pt-32 pb-20 bg-dark-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">World-Class Houses</span>
            <h1 class="font-display text-4xl sm:text-5xl font-bold mt-3 mb-4">
                <span class="text-white">All </span><span class="gold-text">Brands</span>
            </h1>
            <p class="text-gray-400 max-w-2xl mx-auto">Browse every perfume house we carry. Click a brand to explore its fragrances.</p>
        </div>

        @if($brands->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                @foreach($brands as $brand)
                    <a href="{{ route('customer.products.index', ['brand' => $brand->name]) }}"
                       class="group bg-dark-800/50 border border-dark-600 rounded-xl overflow-hidden hover:border-gold-500/30 hover:bg-dark-800 transition-all duration-300 text-center card-hover">
                        @if($brand->logo_url)
                            <div class="h-24 flex items-center justify-center px-4 pt-6">
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" loading="lazy" class="object-contain" style="max-height: 4.5rem;">
                            </div>
                        @else
                            <div class="w-14 h-14 mx-auto rounded-full bg-dark-700 group-hover:bg-gold-500/10 flex items-center justify-center mt-6 transition-all">
                                <span class="font-display text-lg font-bold text-gray-400 group-hover:text-gold-400 transition">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                            </div>
                        @endif
                        <p class="text-sm font-medium text-gray-300 group-hover:text-gold-400 transition py-5">{{ $brand->name }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-16 h-16 rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-crown text-gray-500 text-xl"></i>
                </div>
                <p class="text-gray-500">Brands are being added. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection