<aside id="sidebar" class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex-shrink-0 flex flex-col">
    {{-- Logo --}}
    <div class="p-5 border-b border-gray-700">
        <a href="{{ route('super-admin.dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-10 h-10 rounded-lg object-cover border-2 border-amber-500">
            <div>
                <h1 class="font-bold text-sm tracking-wide">WORLD CHOICE PERFUMES</h1>
                <p class="text-[10px] text-amber-400 tracking-widest uppercase">Super Admin</p>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 p-4 space-y-1">
        <a href="{{ route('super-admin.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('super-admin.dashboard') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('super-admin.branches.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('super-admin.branches.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-store w-5 text-center"></i>
            <span>Branches</span>
        </a>
        <a href="{{ route('super-admin.cashiers.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('super-admin.cashiers.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-user-check w-5 text-center"></i>
            <span>Approvals</span>
            @if(isset($pendingCount) && $pendingCount > 0)
                <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingCount }}</span>
            @endif
        </a>

        <a href="{{ route('super-admin.staff.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('super-admin.staff.*') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <i class="fas fa-users-cog w-5 text-center"></i>
            <span>Staff</span>
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
                <p class="text-[10px] text-amber-400 uppercase tracking-wider">Super Admin</p>
            </div>
        </div>
    </div>
</aside>
