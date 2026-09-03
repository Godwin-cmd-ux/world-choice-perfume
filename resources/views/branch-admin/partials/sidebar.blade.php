<aside id="sidebar" class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex-shrink-0 flex flex-col">
    {{-- Logo --}}
    <div class="p-5 border-b border-gray-700">
        <a href="{{ route('branch-admin.dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-lg object-cover border-2 border-amber-500">
            <div>
                <h1 class="font-bold text-sm tracking-wide">WORLD CHOICE PERFUMES</h1>
                <p class="text-[10px] text-amber-400 tracking-widest uppercase">Branch Admin</p>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 p-4 space-y-1">
        <a href="{{ route('branch-admin.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.dashboard') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('branch-admin.products.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.products.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-box w-5 text-center"></i>
            <span>Products</span>
        </a>
        <a href="{{ route('branch-admin.sales.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.sales.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-receipt w-5 text-center"></i>
            <span>Sales</span>
        </a>
        <a href="{{ route('branch-admin.orders.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.orders.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-shopping-bag w-5 text-center"></i>
            <span>Orders</span>
        </a>
        <a href="{{ route('branch-admin.cashiers.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.cashiers.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-users w-5 text-center"></i>
            <span>Cashiers</span>
        </a>
        <a href="{{ route('branch-admin.stock-managers.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.stock-managers.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-clipboard-check w-5 text-center"></i>
            <span>Stock Managers</span>
        </a>
        <a href="{{ route('branch-admin.expenses.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.expenses.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-money-bill-wave w-5 text-center"></i>
            <span>Expenses</span>
        </a>
        <a href="{{ route('branch-admin.reports.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('branch-admin.reports.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-chart-bar w-5 text-center"></i>
            <span>Reports</span>
        </a>

        <div class="pt-4 mt-4 border-t border-gray-700">
            <p class="px-3 text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Account</p>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('profile.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
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
                <img src="{{ auth()->user()->profile_picture }}" alt="" class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-500">
            @else
                <div class="w-9 h-9 rounded-full bg-amber-600 flex items-center justify-center text-sm font-bold ring-2 ring-amber-400">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-amber-400 uppercase tracking-wider">Branch Admin</p>
            </div>
        </div>
    </div>
</aside>
