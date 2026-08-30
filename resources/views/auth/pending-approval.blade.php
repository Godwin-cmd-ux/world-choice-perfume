@extends('layouts.public')

@section('content')
<section class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-zinc-900/80 backdrop-blur-sm border border-zinc-800 rounded-2xl p-8 shadow-2xl text-center">
            <!-- Icon -->
            <div class="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="font-display text-2xl font-bold text-white mb-3">Account Pending Approval</h1>

            <p class="text-gray-400 mb-6 leading-relaxed">
                Your <span class="text-gold-400 font-medium">{{ ucfirst(str_replace('_', ' ', $user->role ?? 'branch_admin')) }}</span> account has been created successfully.
            </p>

            <div class="bg-zinc-800/50 border border-zinc-700 rounded-lg p-4 mb-6">
                <p class="text-gray-300 text-sm">
                    Your account is awaiting approval from the <span class="text-gold-400 font-medium">Super Administrator</span>. You will be able to log in once your account has been approved.
                </p>
            </div>

            @if(isset($user) && $user->email)
                <p class="text-gray-500 text-xs mb-6">
                    We'll notify you at <span class="text-gray-400">{{ $user->email }}</span> once approved.
                </p>
            @endif

            <div class="space-y-3">
                <a href="{{ route('login') }}" class="block w-full bg-gold-400 hover:bg-gold-500 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300">
                    Back to Login
                </a>
                <a href="{{ route('home') }}" class="block w-full border border-zinc-700 hover:border-gold-400 text-gray-300 hover:text-gold-400 font-medium py-3 px-6 rounded-lg transition-all duration-300">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
