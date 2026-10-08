@extends('layouts.public')

@section('title', 'Staff Access — World Choice Perfume')

@section('content')
<section class="min-h-screen flex items-center justify-center pt-20 pb-12 px-4">
    <div class="w-full max-w-md fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-full object-cover border-2 border-gold-500/30">
            </a>
            <h1 class="font-display text-3xl font-bold text-white">Staff Access</h1>
            <p class="text-gray-400 mt-2">Enter the company secret code to continue</p>
        </div>

        <!-- Secret Code Form -->
        <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-8">
            {{-- Why the member is looking at this screen again: a credential
                 POST whose staff verification had expired. Saying it here is
                 the difference between "you were signed out" and "the site
                 ate my login". --}}
            @if(session('staff_access_notice'))
                <div class="bg-gold-500/10 border border-gold-500/30 text-gold-400 px-4 py-3 rounded-xl mb-6 text-sm">
                    <i class="fas fa-clock mr-1"></i> {{ session('staff_access_notice') }}
                </div>
            @endif

            @if($errors->has('secret_code'))
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first('secret_code') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verify-staff-access') }}">
                @csrf
                {{-- The address they had already typed, carried through this
                     one step so it is waiting on the form after verifying. --}}
                @if(old('email'))
                    <input type="hidden" name="email" value="{{ old('email') }}">
                @endif
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Company Secret Code</label>
                    <div class="relative">
                        <input type="password" name="secret_code" required autofocus
                            class="w-full pl-12 pr-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none text-center tracking-widest"
                            placeholder="Enter code">
                        <i class="fas fa-user-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                    <i class="fas fa-unlock mr-2"></i> Verify
                </button>
            </form>

            <p class="text-center text-xs text-gray-500 mt-4">
                The staff login page is restricted. Contact your administrator if you do not have the code.
            </p>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gold-400 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Home
            </a>
        </div>
    </div>
</section>
@endsection
