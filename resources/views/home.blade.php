@extends('layouts.public')

@section('title', 'World Choice Perfumes — Authentic & Premium Fragrances')

@section('content')
<!-- Hero Section -->
<section class="hero-gradient relative min-h-screen flex items-center overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, #C8A02A 1px, transparent 1px); background-size: 50px 50px;"></div>
    </div>

    <!-- Floating Elements -->
    <div class="absolute top-20 right-20 w-72 h-72 bg-gold-500/5 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 left-20 w-96 h-96 bg-gold-500/3 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="fade-in">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gold-500/10 border border-gold-500/20 mb-8">
                    <span class="w-2 h-2 rounded-full bg-gold-400 animate-pulse"></span>
                    <span class="text-xs font-medium text-gold-400 tracking-wider uppercase">Tanzania's #1 Fragrance Store</span>
                </div>

                <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl font-bold leading-tight mb-6">
                    <span class="text-white">World Choice</span><br>
                    <span class="gold-text">Perfume</span>
                </h1>

                <div class="mb-4">
                    <p class="text-xl text-gold-400 font-display italic tracking-wide">"Be Smart, Nukia Kijanja"</p>
                </div>

                <p class="text-lg text-gray-400 mb-8 max-w-lg leading-relaxed">
                    From the world's most iconic perfume houses to niche artisanal scents — find your signature fragrance at World Choice Perfumes. Be smart, choose the best.
                </p>

                <div class="flex flex-wrap gap-4 mb-12">
                    <a href="{{ route('customer.products.index') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                        <i class="fas fa-shopping-bag"></i> Shop Now
                    </a>
                    <a href="#about" class="inline-flex items-center gap-2 px-8 py-4 border border-gold-500/30 text-gold-400 font-semibold rounded-xl hover:bg-gold-500/10 transition-all duration-300">
                        <i class="fas fa-play-circle"></i> Our Story
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="flex flex-wrap gap-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gold-500/10 flex items-center justify-center">
                            <i class="fas fa-certificate text-gold-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">100% Authentic</p>
                            <p class="text-xs text-gray-500">Guaranteed genuine</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gold-500/10 flex items-center justify-center">
                            <i class="fas fa-truck text-gold-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Fast Delivery</p>
                            <p class="text-xs text-gray-500">Across Tanzania</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gold-500/10 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-gold-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Secure Shopping</p>
                            <p class="text-xs text-gray-500">Safe & reliable</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Image/Visual -->
            <div class="hidden lg:flex justify-center items-center relative">
                <div class="relative">
                    <div class="w-80 h-80 rounded-full bg-gradient-to-br from-gold-500/20 to-gold-700/10 flex items-center justify-center border border-gold-500/20">
                        <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfumes" class="w-48 h-48 rounded-full object-cover shadow-2xl shadow-gold-500/20">
                    </div>
                    <!-- Floating Cards -->
                    <div class="absolute -top-4 -right-4 bg-dark-800/90 backdrop-blur-sm border border-dark-600 rounded-xl px-4 py-3 shadow-xl">
                        <div class="flex items-center gap-2">
                            <div class="flex -space-x-1">
                                @for($i = 0; $i < 5; $i++)
                                    <i class="fas fa-star text-gold-400 text-xs"></i>
                                @endfor
                            </div>
                            <span class="text-xs font-semibold text-white">4.9 Rating</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Trusted by 10,000+ customers</p>
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-dark-800/90 backdrop-blur-sm border border-dark-600 rounded-xl px-4 py-3 shadow-xl">
                        <p class="text-xs font-semibold text-gold-400">{{ $branches->count() }} Location{{ $branches->count() !== 1 ? 's' : '' }}</p>
                        <p class="text-[10px] text-gray-400">Across Tanzania</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
        <a href="#categories" class="text-gold-400/60 hover:text-gold-400 transition">
            <i class="fas fa-chevron-down text-xl"></i>
        </a>
    </div>
</section>

<!-- Categories Section -->
<section id="categories" class="py-20 bg-dark-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 scroll-hidden">
            <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">Our Collection</span>
            <h2 class="font-display text-4xl sm:text-5xl font-bold mt-3 mb-4">
                <span class="text-white">Shop by </span><span class="gold-text">Category</span>
            </h2>
            <p class="text-gray-400 max-w-2xl mx-auto">From bold masculine scents to elegant feminine fragrances and everything in between.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 scroll-hidden">
            <a href="{{ route('customer.products.index', ['category' => 'Men']) }}" class="group relative h-80 rounded-2xl overflow-hidden bg-dark-800 border border-dark-600 card-hover">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/60 to-transparent z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-blue-900/20 to-dark-800"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-mars text-blue-400 text-xl"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-white mb-1">Men's Fragrances</h3>
                    <p class="text-sm text-gray-400">Bold, powerful, unforgettable</p>
                    <div class="mt-3 flex items-center gap-2 text-gold-400 text-sm font-medium group-hover:gap-3 transition-all">
                        Explore <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('customer.products.index', ['category' => 'Women']) }}" class="group relative h-80 rounded-2xl overflow-hidden bg-dark-800 border border-dark-600 card-hover">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/60 to-transparent z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-pink-900/20 to-dark-800"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20">
                    <div class="w-12 h-12 rounded-xl bg-pink-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-venus text-pink-400 text-xl"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-white mb-1">Women's Fragrances</h3>
                    <p class="text-sm text-gray-400">Elegant, seductive, timeless</p>
                    <div class="mt-3 flex items-center gap-2 text-gold-400 text-sm font-medium group-hover:gap-3 transition-all">
                        Explore <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('customer.products.index', ['category' => 'Unisex']) }}" class="group relative h-80 rounded-2xl overflow-hidden bg-dark-800 border border-dark-600 card-hover">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/60 to-transparent z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-purple-900/20 to-dark-800"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-venus-mars text-purple-400 text-xl"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-white mb-1">Unisex Fragrances</h3>
                    <p class="text-sm text-gray-400">For everyone, by everyone</p>
                    <div class="mt-3 flex items-center gap-2 text-gold-400 text-sm font-medium group-hover:gap-3 transition-all">
                        Explore <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('customer.products.index', ['category' => 'Gift Set']) }}" class="group relative h-80 rounded-2xl overflow-hidden bg-dark-800 border border-dark-600 card-hover">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/60 to-transparent z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-gold-900/20 to-dark-800"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20">
                    <div class="w-12 h-12 rounded-xl bg-gold-500/10 flex items-center justify-center mb-4">
                        <i class="fas fa-gift text-gold-400 text-xl"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-white mb-1">Gift Sets & Accessories</h3>
                    <p class="text-sm text-gray-400">The perfect present</p>
                    <div class="mt-3 flex items-center gap-2 text-gold-400 text-sm font-medium group-hover:gap-3 transition-all">
                        Explore <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Featured Brands -->
<section class="py-20 bg-dark-900/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 scroll-hidden">
            <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">World-Class Houses</span>
            <h2 class="font-display text-4xl sm:text-5xl font-bold mt-3">
                <span class="text-white">Featured </span><span class="gold-text">Brands</span>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 scroll-hidden">
            @foreach(['Chanel', 'Dior', 'Versace', 'YSL', 'Tom Ford', 'Creed', 'Gucci', 'Lancôme', 'Paco Rabanne', 'Byredo', 'Le Labo', 'Armani'] as $brand)
                <a href="{{ route('customer.products.index', ['search' => $brand]) }}" class="group bg-dark-800/50 border border-dark-600 rounded-xl p-6 text-center hover:border-gold-500/30 hover:bg-dark-800 transition-all duration-300">
                    <div class="w-14 h-14 mx-auto rounded-full bg-dark-700 group-hover:bg-gold-500/10 flex items-center justify-center mb-3 transition-all">
                        <span class="font-display text-lg font-bold text-gray-400 group-hover:text-gold-400 transition">{{ strtoupper(substr($brand, 0, 2)) }}</span>
                    </div>
                    <p class="text-sm font-medium text-gray-300 group-hover:text-gold-400 transition">{{ $brand }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-20 bg-dark-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center scroll-hidden">
            <div>
                <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">Our Story</span>
                <h2 class="font-display text-4xl sm:text-5xl font-bold mt-3 mb-6">
                    <span class="text-white">Tanzania's Trusted</span><br>
                    <span class="gold-text">Fragrance House</span>
                </h2>
                <div class="space-y-4 text-gray-400 leading-relaxed">
                    <p>
                        <strong class="gold-text">"Be Smart, Nukia Kijanja"</strong> — World Choice Perfumes was founded with a singular vision: to bring the world's finest fragrances to Tanzania. What started as a passion for scent has grown into Tanzania's most trusted destination for authentic, premium perfumes.
                    </p>
                    <p>
                        Every bottle we carry is sourced directly from authorized distributors, ensuring you receive only genuine products. From the iconic Chanel No. 5 to the exclusive Creed Aventus, our collection spans over 200 fragrances from the world's most prestigious perfume houses.
                    </p>
                    <p>
                        With five strategically located branches across Dar es Salaam, Arusha, Mwanza, and Zanzibar, we're always close to you. Our expert consultants are trained to help you find your perfect signature scent.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-6 mt-10">
                    <div class="text-center">
                        <p class="font-display text-3xl font-bold gold-text">200+</p>
                        <p class="text-xs text-gray-500 mt-1">Fragrances</p>
                    </div>
                    <div class="text-center">
                        <p class="font-display text-3xl font-bold gold-text">{{ $branches->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Location{{ $branches->count() !== 1 ? 's' : '' }}</p>
                    </div>
                    <div class="text-center">
                        <p class="font-display text-3xl font-bold gold-text">10K+</p>
                        <p class="text-xs text-gray-500 mt-1">Happy Customers</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="bg-gradient-to-br from-dark-800 to-dark-900 rounded-2xl border border-dark-600 p-8">
                    <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfumes" class="w-full h-80 object-cover rounded-xl mb-6">
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-2">
                            @for($i = 0; $i < 3; $i++)
                                <div class="w-10 h-10 rounded-full bg-gold-500/20 border-2 border-dark-800 flex items-center justify-center">
                                    <i class="fas fa-user text-gold-400 text-xs"></i>
                                </div>
                            @endfor
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Trusted by 10,000+ Customers</p>
                            <div class="flex items-center gap-1 mt-1">
                                @for($i = 0; $i < 5; $i++)
                                    <i class="fas fa-star text-gold-400 text-xs"></i>
                                @endfor
                                <span class="text-xs text-gray-400 ml-1">4.9/5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Branches Section -->
<section id="branches" class="py-20 bg-dark-900/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 scroll-hidden">
            <span class="text-xs font-semibold tracking-[0.3em] uppercase text-gold-400/60">Visit Us</span>
            <h2 class="font-display text-4xl sm:text-5xl font-bold mt-3 mb-4">
                <span class="text-white">Our </span><span class="gold-text">Branches</span>
            </h2>
            <p class="text-gray-400 max-w-2xl mx-auto">Find us across Tanzania. Walk into any of our branches and let our experts help you find your perfect scent.</p>
        </div>

        @if($branches->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 scroll-hidden">
                @foreach($branches as $index => $branch)
                    <div class="group bg-dark-800 border border-dark-600 rounded-2xl overflow-hidden card-hover">
                        <div class="h-48 bg-gradient-to-br from-dark-700 to-dark-800 flex items-center justify-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-gold-500/5 to-transparent"></div>
                            <div class="w-20 h-20 rounded-full bg-gold-500/10 border border-gold-500/20 flex items-center justify-center z-10">
                                <i class="fas fa-store text-gold-400 text-2xl"></i>
                            </div>
                            <div class="absolute top-4 right-4">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="font-display text-lg font-bold text-white mb-2 group-hover:text-gold-400 transition">{{ $branch->name }}</h3>
                            <div class="flex items-start gap-2 text-gray-400 text-sm mb-3">
                                <i class="fas fa-map-marker-alt text-gold-500/60 mt-0.5"></i>
                                <span>{{ $branch->address ?? 'Location details coming soon' }}</span>
                            </div>
                            @if($branch->latitude && $branch->longitude)
                                <div class="flex items-center gap-3 mt-1">
                                    <a href="{{ route('customer.twende-dukani', $branch->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gold-500/10 border border-gold-500/30 text-gold-400 text-xs font-semibold rounded-lg hover:bg-gold-500/20 transition">
                                        <i class="fas fa-walking"></i> Twende Dukani
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-16 h-16 rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-store text-gray-500 text-xl"></i>
                </div>
                <p class="text-gray-500">Our branches are being set up. Stay tuned!</p>
            </div>
        @endif
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-dark-900 via-dark-800 to-dark-900 border-y border-dark-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center scroll-hidden">
        <h2 class="font-display text-4xl sm:text-5xl font-bold mb-6">
            <span class="text-white">Find Your </span><span class="gold-text">Signature Scent</span>
        </h2>
        <p class="text-lg text-gray-400 mb-8 max-w-2xl mx-auto">
            Visit any of our 5 branches across Tanzania or shop online. Our fragrance experts are ready to help you discover your perfect match.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('customer.products.index') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                <i class="fas fa-shopping-bag"></i> Browse Collection
            </a>
            <a href="{{ route('customer.orders.track') }}" class="inline-flex items-center gap-2 px-8 py-4 border border-gold-500/30 text-gold-400 font-semibold rounded-xl hover:bg-gold-500/10 transition-all duration-300">
                <i class="fas fa-truck"></i> Track Your Order
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Scroll reveal animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('scroll-visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.scroll-hidden').forEach(el => observer.observe(el));
</script>
@endpush
