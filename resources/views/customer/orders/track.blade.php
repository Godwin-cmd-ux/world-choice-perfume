<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Track Order - World Choice Perfume</title>
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
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a1a; }
        ::-webkit-scrollbar-thumb { background: #C8A02A; border-radius: 4px; }
    </style>
</head>
<body class="bg-dark-950 text-white font-sans min-h-screen flex flex-col">
    <nav class="border-b border-gold-500/20 bg-dark-950/80 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfume" class="w-12 h-12 rounded-full object-cover border-2 border-gold-500/30">
            <div>
                <span class="font-display text-xl font-bold gold-text">World Choice Perfume</span>
                <span class="block text-[10px] tracking-[0.3em] uppercase text-gold-400/60">Be Smart, Nukia Kijanja</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md fade-in">
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gold-500/10 border border-gold-500/30 flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-box-open text-gold-400 text-2xl"></i>
                </div>
                <h1 class="font-display text-3xl font-bold gold-text">Track Your Order</h1>
                <p class="text-sm text-gray-400 mt-2">Enter the phone number you used when ordering</p>
            </div>

            <div class="hero-gradient rounded-2xl border border-gold-500/20 p-8 shadow-2xl">
                @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm">
                        <i class="fas fa-circle-exclamation mr-1"></i> {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.orders.track-by-phone') }}">
                    @csrf
                    <label for="phone" class="block text-xs font-medium uppercase tracking-wider text-gold-400/80 mb-2">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. 0754 000 000" required
                           class="w-full px-4 py-3 rounded-lg bg-dark-950/60 border border-gold-500/25 text-white text-center text-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gold-500/60 focus:border-gold-500/60 transition">
                    <button type="submit" style="background-color:#C8A02A" class="mt-4 w-full hover:opacity-90 text-white font-semibold py-3 rounded-lg transition">
                        <i class="fas fa-search mr-1"></i> Track Order
                    </button>
                </form>

                <div class="flex items-center gap-3 my-6">
                    <div class="h-px flex-1 bg-gold-500/20"></div>
                    <span class="text-[10px] uppercase tracking-[0.25em] text-gold-400/50">or</span>
                    <div class="h-px flex-1 bg-gold-500/20"></div>
                </div>

                <a href="{{ route('customer.products.index') }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 border border-gold-500/30 hover:bg-gold-500/10 text-gold-300 text-sm font-medium rounded-lg transition">
                    <i class="fas fa-arrow-left text-xs"></i> Back to Shop
                </a>
            </div>

            <p class="text-center mt-6 text-xs text-gray-500">
                Need help? <a href="https://wa.me/255616675940" target="_blank" rel="noopener" class="text-gold-400 hover:underline">Chat with us on WhatsApp</a>
            </p>
        </div>
    </main>
</body>
</html>
