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
                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('home') }}" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">Home</a>
                    <a href="{{ route('customer.products.index') }}" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">Shop</a>
                    <a href="{{ route('customer.orders.track') }}" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">Track Order</a>
                    <a href="#about" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">About</a>
                    <a href="#branches" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">Branches</a>
                    <a href="#contact" class="nav-link text-sm font-medium text-gray-300 hover:text-gold-400 transition">Contact</a>
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
                        <div class="relative group">
                            <button class="text-sm font-medium text-gray-300 hover:text-gold-400 transition flex items-center gap-1">
                                <i class="fas fa-user-lock"></i> Staff Login <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-dark-800 border border-dark-600 rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 overflow-hidden">
                                <a href="{{ route('login') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-gold-400 transition">
                                    <i class="fas fa-sign-in-alt mr-2 text-gold-500"></i> Staff Login
                                </a>
                                <div class="border-t border-dark-600"></div>
                                <a href="{{ route('register.branch-admin') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-gold-400 transition">
                                    <i class="fas fa-user-tie mr-2 text-blue-400"></i> Branch Admin Register
                                </a>
                                <a href="{{ route('register.cashier') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-gold-400 transition">
                                    <i class="fas fa-user mr-2 text-green-400"></i> Cashier Register
                                </a>
                                <a href="{{ route('register.super-admin') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-gold-400 transition">
                                    <i class="fas fa-crown mr-2 text-yellow-400"></i> Super Admin Register
                                </a>
                            </div>
                        </div>
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
                    <a href="{{ route('login') }}" class="block px-4 py-3 rounded-lg bg-gold-500/10 text-gold-400"><i class="fas fa-sign-in-alt mr-2"></i> Staff Login</a>
                    <a href="{{ route('register.cashier') }}" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800"><i class="fas fa-user mr-2"></i> Cashier Register</a>
                    <a href="{{ route('register.branch-admin') }}" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-800"><i class="fas fa-user-tie mr-2"></i> Branch Admin Register</a>
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
    </script>
    @yield('modals')
    @stack('scripts')
</body>
</html>
