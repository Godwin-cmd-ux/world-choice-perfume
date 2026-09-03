<aside id="sidebar" class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex-shrink-0 flex flex-col">
    {{-- Logo --}}
    <div class="p-5 border-b border-gray-700">
        <a href="{{ route('stock-manager.dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-lg object-cover border-2 border-emerald-500">
            <div>
                <h1 class="font-bold text-sm tracking-wide">WORLD CHOICE PERFUMES</h1>
                <p class="text-[10px] text-emerald-400 tracking-widest uppercase">Stock Manager</p>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 p-4 space-y-1">
        <a href="{{ route('stock-manager.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock-manager.dashboard') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>

        <div class="pt-3 mt-3 border-t border-gray-700">
            <p class="px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Stock Management</p>
        </div>

        <a href="{{ route('stock-manager.product-stock') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock-manager.product-stock*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-box w-5 text-center"></i>
            <span>Product Stock</span>
        </a>

        <a href="{{ route('stock-manager.bottle-stock') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock-manager.bottle*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-wine-bottle w-5 text-center"></i>
            <span>Bottle Stock</span>
        </a>

        <a href="{{ route('stock-manager.oil-fragrance') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock-manager.oil-fragrance*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-flask w-5 text-center"></i>
            <span>Oil Fragrance</span>
        </a>

        <div class="pt-3 mt-3 border-t border-gray-700">
            <p class="px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Tools</p>
        </div>

        <a href="{{ route('stock-manager.qr-code') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock-manager.qr-code') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-qrcode w-5 text-center"></i>
            <span>QR Code</span>
        </a>

        <div class="pt-4 mt-4 border-t border-gray-700">
            <p class="px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Account</p>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('profile.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-user-cog w-5 text-center"></i>
            <span>Profile</span>
        </a>
        <a href="{{ route('logout') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Logout</span>
        </a>
    </nav>

    {{-- User Info --}}
    <div class="p-4 border-t border-gray-700">
        <div class="flex items-center gap-3">
            @if(auth()->user()->profile_picture)
                <img src="{{ auth()->user()->profile_picture }}" alt="" class="w-9 h-9 rounded-full object-cover ring-2 ring-emerald-500">
            @else
                <div class="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold ring-2 ring-emerald-400">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-emerald-400 uppercase tracking-wider">Stock Manager</p>
            </div>
        </div>
    </div>
</aside>
