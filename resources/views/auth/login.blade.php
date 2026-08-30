@extends('layouts.public')

@section('title', 'Staff Login — World Choice Perfumes')

@section('content')
<section class="min-h-screen flex items-center justify-center pt-20 pb-12 px-4">
    <div class="w-full max-w-md fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-full object-cover border-2 border-gold-500/30">
            </a>
            <h1 class="font-display text-3xl font-bold text-white">Welcome Back</h1>
            <p class="text-gray-400 mt-2">Sign in to your staff account</p>
        </div>

        <!-- Login Form -->
        <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-8">
            @if(session('success'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-xl mb-6 text-sm">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full pl-12 pr-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="you@example.com">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" required
                            class="w-full pl-12 pr-12 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="••••••••">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>

                <div class="mt-4 text-center">
                    <a href="{{ route('password.forgot') }}" class="text-sm text-gray-400 hover:text-gold-400 transition">
                        <i class="fas fa-key mr-1"></i> Forgot Password?
                    </a>
                </div>
            </form>
        </div>

        <!-- Register Links -->
        <div class="mt-6 space-y-3">
            <p class="text-center text-sm text-gray-500">Don't have an account?</p>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('register.cashier') }}" class="py-3 bg-dark-800/50 border border-dark-600 rounded-xl text-center text-sm text-gray-300 hover:border-gold-500/30 hover:text-gold-400 transition">
                    <i class="fas fa-user mr-1"></i> Cashier Sign Up
                </a>
                <a href="{{ route('register.branch-admin') }}" class="py-3 bg-dark-800/50 border border-dark-600 rounded-xl text-center text-sm text-gray-300 hover:border-gold-500/30 hover:text-gold-400 transition">
                    <i class="fas fa-user-tie mr-1"></i> Admin Sign Up
                </a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gold-400 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Home
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function togglePassword(btn) {
    const input = btn.closest('.relative').querySelector('input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endpush
