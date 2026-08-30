<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'World Choice Perfumes') — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { width: 260px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 40; }
        .main-content { margin-left: 260px; min-height: 100vh; }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 35; }
        @media (max-width: 1024px) {
            .sidebar.open ~ .sidebar-overlay { display: block; }
        }
        @media print {
            .sidebar, .sidebar-overlay, header, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
            body { background: white !important; }
            .print-only { display: block !important; }
            .print-header { display: flex !important; align-items: center; gap: 12px; padding: 20px; border-bottom: 3px solid #92400e; margin-bottom: 20px; }
            .print-header img { width: 60px; height: 60px; object-fit: contain; }
            .print-header h1 { font-size: 22px; font-weight: bold; color: #1f2937; }
            .print-header p { font-size: 12px; color: #6b7280; }
            .print-footer { display: block !important; text-align: center; padding: 16px; border-top: 2px solid #e5e7eb; margin-top: 24px; font-size: 11px; color: #9ca3af; }
            table { page-break-inside: avoid; }
        }
        .print-only { display: none; }
        .print-footer { display: none; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">

        {{-- Auto-detect role and include correct sidebar --}}
        @auth
            @if(auth()->user()->isSuperAdmin())
                @include('super-admin.partials.sidebar')
            @elseif(auth()->user()->isBranchAdmin())
                @include('branch-admin.partials.sidebar')
            @elseif(auth()->user()->isCashier())
                @include('cashier.partials.sidebar')
            @endif
        @endauth

        {{-- Mobile overlay --}}
        <div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

        {{-- Main Content --}}
        <div class="main-content flex-1">
            {{-- Top Bar --}}
            <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">@yield('header', 'Dashboard')</h2>
                        @hasSection('subtitle')
                            <p class="text-xs text-gray-500">@yield('subtitle')</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @yield('header-actions')
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success') || session('error') || $errors->any())
                <div class="px-6 pt-4">
                    @if(session('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm mb-3">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm mb-3">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-3">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Page Content --}}
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <div class="print-only" id="printHeader">
        <div class="print-header">
            <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfumes">
            <div>
                <h1>World Choice Perfumes</h1>
                <p>Be Smart, Nukia Kijanja</p>
                <p style="font-size:11px; margin-top:2px;">@yield('header', 'Report')</p>
                <p style="font-size:11px;">Generated: {{ now()->format('M d, Y \a\t h:i A') }} | Branch: {{ auth()->user()->branch_id ? 'Branch #' . auth()->user()->branch_id : 'All' }}</p>
            </div>
        </div>
    </div>
    <div class="print-footer">
        <p>&copy; {{ date('Y') }} World Choice Perfumes — Be Smart, Nukia Kijanja</p>
        <p>This report was generated automatically. For inquiries, call +255 710 603 637</p>
    </div>
    @stack('scripts')
    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('sidebar')?.classList.remove('open');
            }
        });
    </script>
</body>
</html>
