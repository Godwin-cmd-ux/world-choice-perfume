<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Your Orders - World Choice Perfume</title>
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
<body class="bg-dark-950 text-white font-sans min-h-screen">
    <nav class="sticky top-0 z-50 border-b border-gold-500/20 bg-dark-950/90 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfume" class="w-12 h-12 rounded-full object-cover border-2 border-gold-500/30">
                <div>
                    <span class="font-display text-xl font-bold gold-text">World Choice Perfume</span>
                    <span class="block text-[10px] tracking-[0.3em] uppercase text-gold-400/60">Be Smart, Nukia Kijanja</span>
                </div>
            </a>
            <a href="{{ route('customer.orders.track') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 border border-gold-500/30 hover:bg-gold-500/10 text-gold-300 text-sm font-medium rounded-lg transition">
                <i class="fas fa-magnifying-glass text-xs"></i> Track
            </a>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto px-4 py-10">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-8">
            <div>
                <h1 class="font-display text-3xl font-bold gold-text">Your Orders</h1>
                <p class="text-sm text-gray-400 mt-1">Follow every order and its progress</p>
            </div>
            <a href="{{ route('customer.orders.track') }}" class="sm:hidden inline-flex items-center gap-2 px-4 py-2 border border-gold-500/30 text-gold-300 text-sm rounded-lg">
                <i class="fas fa-magnifying-glass text-xs"></i> Track
            </a>
        </div>

        @forelse($orders as $order)
            <div class="hero-gradient rounded-2xl border border-gold-500/20 p-5 mb-5 fade-in">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        {{-- The official order number is always shown; it is never
                             replaced by any internal label. --}}
                        <p class="font-display text-lg font-bold text-white">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-store mr-1 text-gold-500/70"></i>{{ $order->branch->name }}
                            <span class="mx-1 text-gold-500/40">|</span>
                            <i class="fas fa-clock mr-1 text-gold-500/70"></i>{{ \Carbon\Carbon::parse($order->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap
                        {{ match($order->status) { 'pending' => 'bg-yellow-500/15 text-yellow-300 border border-yellow-500/30', 'picked' => 'bg-blue-500/15 text-blue-300 border border-blue-500/30', 'served' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30', default => 'bg-gray-500/15 text-gray-300 border border-gray-500/30' } }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                <div class="mt-4 pt-4 border-t border-gold-500/15 text-sm space-y-1">
                    @foreach($order->items as $item)
                        <p class="text-gray-300"><span class="text-gold-400 font-medium">{{ $item->quantity }}x</span> {{ $item->product->name }} <span class="text-gray-500">- TZS {{ number_format($item->total) }}</span></p>
                    @endforeach
                    <p class="font-display font-bold text-gold-400 pt-2">Total: TZS {{ number_format($order->total) }}</p>
                </div>

                @if(collect($order->timeline ?? [])->count() > 0)
                    <div class="mt-4 pt-4 border-t border-gold-500/15">
                        <p class="text-[10px] font-semibold text-gold-400/70 uppercase tracking-[0.2em] mb-3">Progress</p>
                        <ol class="space-y-3">
                            @foreach($order->timeline as $step)
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="w-6 h-6 rounded-full bg-gold-500/15 border border-gold-500/30 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-check text-[10px] text-gold-400"></i>
                                    </span>
                                    <span class="text-gray-200">{{ $step['label'] }}</span>
                                    <span class="text-xs text-gray-500 ml-auto">{{ \Carbon\Carbon::parse($step['at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if(collect($order->notes ?? [])->count() > 0)
                    <div class="mt-4 pt-4 border-t border-gold-500/15">
                        <p class="text-[10px] font-semibold text-gold-400/70 uppercase tracking-[0.2em] mb-3">Order Updates</p>
                        @foreach($order->notes as $note)
                            <div class="bg-gold-500/[0.07] border border-gold-500/20 rounded-lg px-3 py-2 mb-2">
                                <p class="text-sm text-gray-200">{{ $note->note }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($note->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-gold-500/10 border border-gold-500/30 flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-box-open text-gold-400 text-2xl"></i>
                </div>
                <p class="text-gray-400">No orders found for this phone number.</p>
            </div>
        @endforelse

        <div class="text-center mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="https://wa.me/255616675940" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 px-5 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                <i class="fab fa-whatsapp text-lg"></i> Chat with us
            </a>
            <a href="{{ route('customer.orders.track') }}" class="inline-flex items-center gap-2 px-5 py-3 border border-gold-500/30 hover:bg-gold-500/10 text-gold-300 text-sm font-medium rounded-lg transition">
                <i class="fas fa-arrow-left text-xs"></i> Track Another Order
            </a>
        </div>
    </main>
</body>
</html>
