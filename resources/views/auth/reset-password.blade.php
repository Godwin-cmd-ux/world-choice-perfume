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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-bold text-white">Reset Password</h1>
                <p class="text-gray-400 mt-2 text-sm">Enter the verification code sent to <strong class="text-gold-400">{{ $email }}</strong></p>
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
            <form action="{{ route('password.reset') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <!-- OTP Code -->
                <div class="mb-5">
                    <label for="otp" class="block text-sm font-medium text-gray-300 mb-2">Verification Code</label>
                    <input type="text" id="otp" name="otp" required maxlength="6"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400 focus:ring-1 focus:ring-gold-400 transition-colors text-center text-2xl tracking-[8px] font-mono"
                        placeholder="000000"
                        autocomplete="one-time-code">
                </div>

                <!-- New Password -->
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required minlength="8"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 pr-12 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400 focus:ring-1 focus:ring-gold-400 transition-colors"
                            placeholder="Min 8 characters">
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gold-400 transition-colors">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-gold-400 focus:ring-1 focus:ring-gold-400 transition-colors"
                        placeholder="Confirm your new password">
                </div>

                <button type="submit" class="w-full bg-gold-400 hover:bg-gold-500 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-[1.02]">
                    Reset Password
                </button>
            </form>

            <!-- Resend OTP -->
            <div class="mt-6 text-center">
                <form action="{{ route('password.forgot') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button type="submit" class="text-sm text-gray-400 hover:text-gold-400 transition-colors">
                        Didn't receive the code? <span class="text-gold-400 font-medium">Resend</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');
    if (input.type === 'password') {
        input.type = 'text';
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
    } else {
        input.type = 'password';
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
    }
}
</script>
@endsection
