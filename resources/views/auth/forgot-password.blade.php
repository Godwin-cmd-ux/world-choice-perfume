@extends('layouts.public')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md">
        <!-- Back to Login -->
        <a href="{{ route('login') }}" class="inline-flex items-center text-gray-400 hover:text-gold-400 transition-colors mb-8">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Login
        </a>

        <!-- Card -->
        <div class="bg-zinc-900/80 backdrop-blur-sm border border-zinc-800 rounded-2xl p-8 shadow-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gold-400/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-bold text-white">Forgot Password?</h1>
                <p class="text-gray-400 mt-2 text-sm">Enter your email address and we'll send you a verification code.</p>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-6">
                    <p class="text-emerald-400 text-sm text-center">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Errors -->
            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                    @foreach($errors->all() as $error)
                        <p class="text-red-400 text-sm text-center">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400 focus:ring-1 focus:ring-gold-400 transition-colors"
                        placeholder="your@email.com"
                        value="{{ old('email') }}">
                </div>

                <button type="submit" class="w-full bg-gold-400 hover:bg-gold-500 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-[1.02]">
                    Send Verification Code
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
