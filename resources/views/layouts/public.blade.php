<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'World Choice Perfumes — Be Smart, Nukia Kijanja')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 50: '#FFF9E6', 100: '#FFF0BF', 200: '#FFE080', 300: '#FFD040', 400: '#FFC107', 500: '#C8A02A', 600: '#A68523', 700: '#85691C', 800: '#634E15', 900: '#42340E' },
                        dark: { 50: '#f5f5f5', 100: '#e0e0e0', 200: '#bdbdbd', 300: '#9e9e9e', 400: '#757575', 500: '#616161', 600: '#424242', 700: '#303030', 800: '#1a1a1a', 900: '#0d0d0d', 950: '#050505' }
                    },
                    fontFamily: {
                        display: ['Playfair Display', 'serif'],
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .hero-gradient { background: linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 40%, #42340e 100%); }
        .gold-text { background: linear-gradient(135deg, #FFD040, #C8A02A, #FFE080); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .card-hover { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-8px); box-shadow: 0 25px 50px -12px rgba(200, 160, 42, 0.25); }
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .scroll-hidden { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .scroll-visible { opacity: 1; transform: translateY(0); }
        .shimmer { background: linear-gradient(90deg, transparent, rgba(255,208,64,0.1), transparent); animation: shimmer 3s infinite; }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: #C8A02A; transition: width 0.3s; }
        .nav-link:hover::after { width: 100%; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a1a; }
        ::-webkit-scrollbar-thumb { background: #C8A02A; border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body class="bg-dark-950 text-white font-sans">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300" id="mainNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfumes" class="w-12 h-12 rounded-full object-cover border-2 border-gold-500/30">
                    <div>
                        <span class="font-display text-xl font-bold gold-text">World Choice Perfumes</span>
                        <span class="block text-[10px] tracking-[0.3em] uppercase text-gold-400/60">Be Smart, Nukia Kijanja</span>
                    </div>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-2">
                    <a href="{{ route('home') }}" data-nav="home"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg transition-all {{ request()->routeIs('home') ? 'text-gold-400 bg-gold-500/10 text-base px-4 py-2.5' : 'text-gray-300 hover:text-gold-400 hover:bg-white/5' }}">Home</a>
                    <a href="{{ route('customer.products.index') }}" data-nav="shop"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg transition-all {{ request()->routeIs('customer.products.*') ? 'text-gold-400 bg-gold-500/10 text-base px-4 py-2.5' : 'text-gray-300 hover:text-gold-400 hover:bg-white/5' }}">Shop</a>
                    <a href="{{ route('customer.orders.track') }}" data-nav="track-order"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg transition-all {{ request()->routeIs('customer.orders.track') ? 'text-gold-400 bg-gold-500/10 text-base px-4 py-2.5' : 'text-gray-300 hover:text-gold-400 hover:bg-white/5' }}">Track Order</a>
                    <a href="#about" data-nav="about" data-scroll="true"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg text-gray-300 hover:text-gold-400 hover:bg-white/5 transition-all">About</a>
                    <a href="#branches" data-nav="branches" data-scroll="true"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg text-gray-300 hover:text-gold-400 hover:bg-white/5 transition-all">Branches</a>
                    <a href="#contact" data-nav="contact" data-scroll="true"
                       class="nav-link nav-item text-sm font-medium px-3 py-2 rounded-lg text-gray-300 hover:text-gold-400 hover:bg-white/5 transition-all">Contact</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center gap-4">
                    @auth
                        @if(auth()->user()->role === 'super_admin')
                            <a href="{{ route('super-admin.dashboard') }}" class="text-sm font-medium text-gold-400 hover:text-gold-300 transition">
                                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                            </a>
                        @elseif(auth()->user()->role === 'branch_admin')
                            <a href="{{ route('branch-admin.dashboard') }}" class="text-sm font-medium text-gold-400 hover:text-gold-300 transition">
                                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                            </a>
                        @elseif(auth()->user()->role === 'cashier')
                            <a href="{{ route('cashier.dashboard') }}" class="text-sm font-medium text-gold-400 hover:text-gold-300 transition">
                                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                            </a>
                        @endif
                        <a href="{{ route('logout') }}" class="text-sm text-gray-400 hover:text-white transition">Logout</a>
                    @else
                        <button onclick="document.getElementById('staffLoginModal').classList.remove('hidden')" class="text-sm font-medium text-gray-300 hover:text-gold-400 transition flex items-center gap-1">
                            <i class="fas fa-user-lock"></i> Staff Login
                        </button>
                    @endauth
                </div>

                <!-- Mobile Menu Toggle -->
                <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden text-gray-300 hover:text-gold-400">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden bg-dark-900/98 backdrop-blur-xl border-t border-dark-700">
            <div class="px-4 py-6 space-y-3">
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800 hover:text-gold-400 transition"><i class="fas fa-home mr-2"></i> Home</a>
                <a href="{{ route('customer.products.index') }}" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800 hover:text-gold-400 transition"><i class="fas fa-shopping-bag mr-2"></i> Shop</a>
                <a href="{{ route('customer.orders.track') }}" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800 hover:text-gold-400 transition"><i class="fas fa-truck mr-2"></i> Track Order</a>
                <a href="#branches" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800 hover:text-gold-400 transition"><i class="fas fa-store mr-2"></i> Branches</a>
                <div class="border-t border-dark-700 my-3"></div>
                @auth
                    <a href="{{ route(auth()->user()->role === 'super_admin' ? 'super-admin.dashboard' : (auth()->user()->role === 'branch_admin' ? 'branch-admin.dashboard' : 'cashier.dashboard')) }}" class="block px-4 py-3 rounded-lg bg-gold-500/10 text-gold-400"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
                @else
                    <button onclick="document.getElementById('staffLoginModal').classList.remove('hidden')" class="block w-full text-left px-4 py-3 rounded-lg bg-gold-500/10 text-gold-400"><i class="fas fa-sign-in-alt mr-2"></i> Staff Login</button>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer id="contact" class="bg-dark-900 border-t border-dark-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <!-- Brand -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-12 h-12 rounded-full object-cover border-2 border-gold-500/30">
                        <div>
                            <span class="font-display text-lg font-bold gold-text">World Choice Perfumes</span>
                            <span class="block text-[10px] tracking-[0.3em] uppercase text-gold-400/60">Be Smart, Nukia Kijanja</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Tanzania's trusted destination for authentic and premium fragrances. Every scent tells a story.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-display text-sm font-semibold uppercase tracking-wider text-gold-400 mb-6">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-gold-400 transition">Home</a></li>
                        <li><a href="{{ route('customer.products.index') }}" class="text-sm text-gray-400 hover:text-gold-400 transition">Shop All</a></li>
                        <li><a href="{{ route('customer.orders.track') }}" class="text-sm text-gray-400 hover:text-gold-400 transition">Track Order</a></li>
                        <li><a href="#about" class="text-sm text-gray-400 hover:text-gold-400 transition">About Us</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="font-display text-sm font-semibold uppercase tracking-wider text-gold-400 mb-6">Contact Us</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li class="flex items-center gap-2"><i class="fas fa-phone text-gold-500/60"></i> +255 710 603 637</li>
                        <li class="flex items-center gap-2"><i class="fab fa-whatsapp text-gold-500/60"></i> +255 710 603 637</li>
                        <li class="flex items-center gap-2"><i class="fas fa-clock text-gold-500/60"></i> Mon – Sat: 9AM – 8PM</li>
                    </ul>
                    <div class="flex gap-4 mt-6">
                        <a href="https://www.instagram.com/world_choice_parfum" target="_blank" class="w-10 h-10 rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-pink-400 hover:border-pink-500/50 transition"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@KessyMohammed02" target="_blank" class="w-10 h-10 rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-white hover:border-white/50 transition"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/255710603637" class="w-10 h-10 rounded-full bg-dark-800 border border-dark-600 flex items-center justify-center text-gray-400 hover:text-green-400 hover:border-green-500/50 transition"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <div class="border-t border-dark-700 mt-12 pt-8 text-center">
                <p class="text-sm text-gray-500">&copy; {{ date('Y') }} World Choice Perfumes. All rights reserved.</p>
                <p class="text-xs text-gray-600 mt-1">"Be Smart, Nukia Kijanja"</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                nav.classList.add('bg-dark-900/95', 'backdrop-blur-xl', 'shadow-lg', 'shadow-black/20');
            } else {
                nav.classList.remove('bg-dark-900/95', 'backdrop-blur-xl', 'shadow-lg', 'shadow-black/20');
            }
        });
        // Trigger scroll check on load
        window.dispatchEvent(new Event('scroll'));

        // Scroll-spy for nav active states
        (function() {
            const isHomePage = document.querySelector('section#about') !== null;
            if (!isHomePage) return;

            const navItems = document.querySelectorAll('.nav-item');
            const sections = [];

            // Map sections to nav items
            // Hero (top of page) → Home
            // #categories, #featured-brands (between hero and about) → Home
            // #about → About
            // #branches → Branches
            // #contact (footer) → Contact
            const sectionMap = [
                { id: 'hero', nav: 'home' },
                { id: 'about', nav: 'about' },
                { id: 'branches', nav: 'branches' },
                { id: 'contact', nav: 'contact' }
            ];

            // Get or create hero section reference
            const heroSection = document.querySelector('.hero-gradient');

            function setActiveNav(navKey) {
                navItems.forEach(item => {
                    const key = item.getAttribute('data-nav');
                    if (key === navKey) {
                        item.classList.add('text-gold-400', 'bg-gold-500/10');
                        item.classList.remove('text-gray-300');
                    } else {
                        item.classList.remove('text-gold-400', 'bg-gold-500/10');
                        item.classList.add('text-gray-300');
                    }
                });
            }

            function onScroll() {
                const scrollY = window.scrollY + 100; // offset for fixed nav
                let currentSection = 'home';

                // Check about section
                const aboutEl = document.getElementById('about');
                if (aboutEl && scrollY >= aboutEl.offsetTop) {
                    currentSection = 'about';
                }

                // Check branches section
                const branchesEl = document.getElementById('branches');
                if (branchesEl && scrollY >= branchesEl.offsetTop) {
                    currentSection = 'branches';
                }

                // Check contact (footer)
                const contactEl = document.getElementById('contact');
                if (contactEl && scrollY >= contactEl.offsetTop - 200) {
                    currentSection = 'contact';
                }

                setActiveNav(currentSection);
            }

            // Use IntersectionObserver for smoother detection
            const observerOptions = {
                root: null,
                rootMargin: '-100px 0px -60% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        if (id === 'about') setActiveNav('about');
                        else if (id === 'branches') setActiveNav('branches');
                        else if (id === 'contact') setActiveNav('contact');
                    }
                });
            }, observerOptions);

            // Observe sections
            ['about', 'branches', 'contact'].forEach(id => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });

            // Also handle hero area (top of page)
            const heroObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setActiveNav('home');
                    }
                });
            }, { root: null, rootMargin: '-100px 0px -60% 0px', threshold: 0 });

            if (heroSection) heroObserver.observe(heroSection);

            // Fallback scroll listener for edge cases
            window.addEventListener('scroll', onScroll, { passive: true });

            // Set initial state
            onScroll();
        })();
    </script>
    @yield('modals')

    <!-- Staff Login Secret Code Modal -->
    <div id="staffLoginModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-dark-800 border border-dark-600 rounded-2xl p-8 max-w-sm w-full shadow-2xl">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-gold-500/10 border border-gold-500/20 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-lock text-gold-400 text-2xl"></i>
                </div>
                <h3 class="font-display text-xl font-bold text-white mb-2">Staff Access</h3>
                <p class="text-gray-400 text-sm mb-6">Enter the company secret code to access staff pages</p>

                @if($errors->has('secret_code'))
                    <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-2 rounded-xl mb-4 text-sm">
                        {{ $errors->first('secret_code') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('verify-staff-access') }}">
                    @csrf
                    <input type="text" name="secret_code" required autofocus
                        class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none text-center text-lg tracking-widest"
                        placeholder="Enter code">
                    <button type="submit" class="w-full mt-4 py-3 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                        <i class="fas fa-unlock mr-2"></i> Verify
                    </button>
                </form>

                <button onclick="document.getElementById('staffLoginModal').classList.add('hidden')" class="mt-4 text-sm text-gray-500 hover:text-gray-300 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Home
                </button>
            </div>
        </div>
    </div>
    <script>
        // Show modal if session flag is set
        @if(session('show_staff_modal'))
            document.getElementById('staffLoginModal').classList.remove('hidden');
        @endif

        // Click outside modal to close
        document.getElementById('staffLoginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
