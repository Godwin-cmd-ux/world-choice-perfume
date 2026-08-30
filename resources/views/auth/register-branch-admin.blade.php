@extends('layouts.public')

@section('title', 'Branch Admin Registration — World Choice Perfumes')

@section('content')
<section class="min-h-screen flex items-center justify-center pt-20 pb-12 px-4">
    <div class="w-full max-w-md fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-6">
                <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-full object-cover border-2 border-gold-500/30">
            </a>
            <h1 class="font-display text-3xl font-bold text-white">Branch Admin</h1>
            <p class="text-gray-400 mt-2">Register as a Branch Administrator</p>
        </div>

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

            <form method="POST" action="{{ route('register.branch-admin') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Your Full Name</label>
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
                        <label class="block text-sm font-medium text-gray-300 mb-2">Branch Name</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name') }}" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="e.g. Lekki Main Store">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Branch Address <span class="text-gray-500">(optional)</span></label>
                        <input type="text" name="branch_address" value="{{ old('branch_address') }}"
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="15 Posta Road, Dar es Salaam, Tanzania">
                    </div>

                    <!-- Spot Branch Location -->
                    <div class="p-4 bg-dark-700/50 border border-dark-500 rounded-xl">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-map-marker-alt text-gold-400 mr-1"></i> Branch Location <span class="text-gray-500">(optional)</span>
                        </label>
                        <p class="text-xs text-gray-400 mb-3">Set your branch location so customers can navigate to your shop using "Twende Dukani".</p>
                        <input type="hidden" name="latitude" id="reg-latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="reg-longitude" value="{{ old('longitude') }}">
                        <div id="reg-location-status" class="text-xs text-gray-400 mb-2"></div>
                        <button type="button" onclick="openLocationModal()" id="reg-spot-btn"
                            class="px-4 py-2 bg-gold-500/10 border border-gold-500/30 text-gold-400 text-sm font-medium rounded-xl hover:bg-gold-500/20 transition-all">
                            <i class="fas fa-crosshairs mr-1"></i> Spot Branch Location
                        </button>
                        <div id="reg-location-saved" class="hidden mt-2 text-xs text-green-400">
                            <i class="fas fa-check-circle mr-1"></i> Location saved successfully!
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Company Secret Code</label>
                        <input type="text" name="secret_code" value="{{ old('secret_code') }}" required
                            class="w-full px-4 py-3 bg-dark-700 border border-dark-600 rounded-xl text-white placeholder-gray-500 focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/30 transition outline-none"
                            placeholder="Enter the company secret code">
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

                <button type="submit" class="w-full mt-6 py-3 bg-gradient-to-r from-gold-500 to-gold-600 text-dark-900 font-semibold rounded-xl hover:from-gold-400 hover:to-gold-500 transition-all duration-300 shadow-lg shadow-gold-500/25">
                    <i class="fas fa-user-tie mr-2"></i> Register as Branch Admin
                </button>
            </form>
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

@section('modals')
<!-- Location Modal -->
<div id="locationModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-dark-800 border border-dark-600 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
        <div class="text-center">
            <div class="w-16 h-16 rounded-full bg-gold-500/10 border border-gold-500/20 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-map-marker-alt text-gold-400 text-2xl"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-white mb-2">Twende Dukani</h3>
            <p class="text-gray-400 text-sm mb-6">Are you at the office right now?</p>
            <div class="flex gap-3">
                <button type="button" onclick="closeLocationModal()"
                    class="flex-1 px-4 py-3 border border-dark-500 text-gray-400 rounded-xl text-sm font-medium hover:bg-dark-700 transition">
                    No, Cancel
                </button>
                <button type="button" onclick="captureLocation()" id="yesLocationBtn"
                    class="flex-1 px-4 py-3 bg-gold-500 text-dark-900 rounded-xl text-sm font-semibold hover:bg-gold-400 transition">
                    Yes, I'm Here
                </button>
            </div>
            <div id="location-loading" class="hidden mt-4">
                <i class="fas fa-spinner fa-spin text-gold-400 text-xl"></i>
                <p class="text-xs text-gray-400 mt-2">Getting your location...</p>
            </div>
            <div id="location-error" class="hidden mt-4 text-xs text-red-400"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openLocationModal() {
        document.getElementById('locationModal').classList.remove('hidden');
        document.getElementById('location-loading').classList.add('hidden');
        document.getElementById('location-error').classList.add('hidden');
    }

    function closeLocationModal() {
        document.getElementById('locationModal').classList.add('hidden');
    }

    function captureLocation() {
        if (!navigator.geolocation) {
            document.getElementById('location-error').textContent = 'Geolocation is not supported by your browser.';
            document.getElementById('location-error').classList.remove('hidden');
            return;
        }

        document.getElementById('yesLocationBtn').disabled = true;
        document.getElementById('location-loading').classList.remove('hidden');
        document.getElementById('location-error').classList.add('hidden');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                document.getElementById('reg-latitude').value = lat;
                document.getElementById('reg-longitude').value = lng;

                document.getElementById('location-loading').classList.add('hidden');
                document.getElementById('yesLocationBtn').disabled = false;

                // Show success
                document.getElementById('reg-location-status').innerHTML =
                    '<span class="text-green-400"><i class="fas fa-check-circle mr-1"></i> Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + '</span>';
                document.getElementById('reg-location-saved').classList.remove('hidden');
                document.getElementById('reg-spot-btn').innerHTML =
                    '<i class="fas fa-check-circle mr-1"></i> Update Location';

                closeLocationModal();
            },
            function(error) {
                document.getElementById('location-loading').classList.add('hidden');
                document.getElementById('yesLocationBtn').disabled = false;
                let msg = 'Unable to get your location.';
                if (error.code === 1) msg = 'Location access denied. Please allow location access in your browser.';
                else if (error.code === 2) msg = 'Location unavailable. Please try again.';
                else if (error.code === 3) msg = 'Location request timed out. Please try again.';
                document.getElementById('location-error').textContent = msg;
                document.getElementById('location-error').classList.remove('hidden');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
</script>
@endpush
