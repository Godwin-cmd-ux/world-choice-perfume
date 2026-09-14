<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stock Manager — World Choice Perfume')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { width: 250px; height: 100vh; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 40; overflow-y: auto; overflow-x: hidden; }
        .main-content { margin-left: 250px; min-height: 100vh; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; z-index: 50; transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans">
    @php
        $smScope = new \App\Services\StockManagerScope();
        $crossMode = $smScope->inCrossBranchMode();
        $crossSelectionPage = request()->routeIs('stock-manager.cross-branch');
        $isSuperAdmin = $smScope->isSuperAdmin();
    @endphp
    <div class="flex min-h-screen">
        @include($isSuperAdmin ? 'super-admin.partials.sidebar' : 'stock-manager.partials.sidebar')

        <div class="main-content flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="md:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-800">@yield('header', 'Dashboard')</h1>
                        @if($crossMode && !$crossSelectionPage)
                            <p class="text-sm text-gray-500">Monitoring <strong>{{ $smScope->activeBranchName() }}</strong> — read-only, sales &amp; orders hidden</p>
                        @else
                            @hasSection('header-subtitle')
                                <p class="text-sm text-gray-500">@yield('header-subtitle')</p>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @yield('header-actions')
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mx-6 mt-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('warning') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Page Content --}}
            <main class="flex-1 p-6">
                @if($crossMode && !$crossSelectionPage)
                    {{-- Shared branch screen: the monitored branch's stock manager UI, embedded --}}
                    <div class="rounded-xl border border-gray-200 overflow-hidden shadow-md bg-white">
                        <div class="px-4 py-3 border-b bg-gradient-to-r from-gray-50 to-white flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i class="fas fa-code-branch text-emerald-600 text-sm"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $smScope->activeBranchName() }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Shared branch view • read-only</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('stock-manager.cross-branch') }}" class="text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50">
                                    <i class="fas fa-university mr-1"></i> All branches
                                </a>
                                <a href="{{ route('stock-manager.cross-branch.exit') }}" style="background-color: #F89A1E;" class="text-xs font-semibold text-white  hover:opacity-90 px-3 py-1.5 rounded-lg">
                                    <i class="fas fa-arrow-left mr-1"></i> Exit branch
                                </a>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row">
                            @include('stock-manager.partials.cross-branch-sidebar')
                            <div class="flex-1 min-w-0 p-5 bg-gray-50">
                                @yield('content')
                            </div>
                        </div>
                    </div>
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>
    @include('partials.toast')
    @stack('scripts')
</body>
</html>
