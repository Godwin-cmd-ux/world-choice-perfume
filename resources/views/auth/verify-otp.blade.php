@extends('layouts.public')

@section('title', 'Verify Email — World Choice Perfumes')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md fade-in">
        <!-- Card -->
        <div class="bg-zinc-900/80 backdrop-blur-sm border border-zinc-800 rounded-2xl p-8 shadow-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gold-400/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-bold text-white">Verify Your Email</h1>
                <p class="text-gray-400 mt-2 text-sm">
                    {{ $message ?? 'We sent a 6-digit verification code to:' }}
                </p>
                <p class="text-gold-400 font-medium mt-1">{{ $email }}</p>
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

            <!-- OTP Form -->
            <form method="POST" action="{{ route('verify-otp') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="user_id" value="{{ $user_id }}">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Enter Verification Code</label>
                    <input type="text" name="otp" required maxlength="6" pattern="[0-9]{6}"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400 focus:ring-1 focus:ring-gold-400 transition-colors text-center text-3xl tracking-[12px] font-mono"
                        placeholder="000000" autocomplete="one-time-code" autofocus>
                    @error('otp') <p class="text-red-400 text-xs mt-2 text-center">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full bg-gold-400 hover:bg-gold-500 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-[1.02]">
                    <i class="fas fa-check-circle mr-2"></i> Verify Code
                </button>
            </form>

            <!-- Resend OTP -->
            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('resend-otp') }}" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="user_id" value="{{ $user_id }}">
                    <button type="submit" class="text-sm text-gray-400 hover:text-gold-400 transition">
                        <i class="fas fa-redo mr-1"></i> Didn't receive the code? <span class="text-gold-400 font-medium">Resend</span>
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gold-400 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
