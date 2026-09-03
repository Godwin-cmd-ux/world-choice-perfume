@extends('layouts.public')

@section('title', 'Stock Manager Registration — World Choice Perfumes')

@section('content')
<section class="min-h-screen flex items-center justify-center pt-20 pb-12 px-4">
    <div class="w-full max-w-md fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-full object-cover border-2 border-gold-500/30">
            </a>
            <h1 class="font-display text-3xl font-bold text-white">Join Our Team</h1>
            <p class="text-gray-400 mt-2">Register as a Stock Manager at World Choice Perfumes</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-dark-800/50 border border-dark-600 rounded-2xl p-8">
            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.stock-manager') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="John Doe">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="you@example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="+255 7XX XXX XXX">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Branch</label>
                        <select name="branch_id" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none">
                            <option value="">Select a branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="Min. 8 characters">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="Repeat your password">
                    </div>
                </div>

                <button type="submit" class="w-full mt-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300 shadow-lg shadow-emerald-500/25">
                    <i class="fas fa-user-plus mr-2"></i> Register as Stock Manager
                </button>
            </form>

            <div class="mt-4 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i> Your account will be reviewed by the branch admin before activation.
                </p>
            </div>
        </div>

        <div class="mt-6 text-center space-y-2">
            <p class="text-sm text-gray-500">
                Already have an account? <a href="{{ route('login') }}" class="text-gold-400 hover:text-gold-300 transition">Sign in</a>
            </p>
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gold-400 transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Home
            </a>
        </div>
    </div>
</section>
@endsection
