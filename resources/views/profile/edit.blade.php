@extends('layouts.app')
@section('title', 'My Profile')
@section('header', 'My Profile')

@section('content')
<div class="max-w-2xl">
    <!-- Profile Info -->
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 mb-6">
        @csrf @method('PUT')
        <h3 class="font-semibold mb-4">Personal Information</h3>
        <div class="space-y-4">
            <div class="flex items-center gap-4">
                @if($user->profile_picture)
                    <img src="{{ $user->profile_picture }}" class="w-20 h-20 rounded-full object-cover">
                @else
                    <div class="w-20 h-20 rounded-full bg-amber-200 flex items-center justify-center text-2xl font-bold text-amber-700">{{ substr($user->name, 0, 1) }}</div>
                @endif
                <input type="file" name="profile_picture" accept="image/*" class="text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="text-sm text-gray-500">
                <p>Role: <strong class="capitalize">{{ str_replace('_', ' ', $user->role) }}</strong></p>
                <p>Status: <strong class="capitalize">{{ $user->status }}</strong></p>
                @if($user->branch) <p>Branch: <strong>{{ $user->branch->name }}</strong></p> @endif
            </div>
        </div>
        <button type="submit" class="mt-4 bg-amber-700 hover:bg-amber-800 text-white px-6 py-2 rounded-lg font-medium">Update Profile</button>
    </form>

    <!-- Change Password -->
    <form method="POST" action="{{ route('profile.password') }}" class="bg-white rounded-xl shadow p-6 mb-6">
        @csrf @method('PUT')
        <h3 class="font-semibold mb-4">Change Password</h3>
        <div class="space-y-4">
            <input type="password" name="current_password" placeholder="Current Password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            <input type="password" name="password" placeholder="New Password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
            <input type="password" name="password_confirmation" placeholder="Confirm New Password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
        </div>
        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">Change Password</button>
    </form>

    @if($user->isBranchAdmin() && $user->branch)
    <!-- Branch Location — Twende Dukani -->
    <form method="POST" action="{{ route('profile.branch-location') }}" class="bg-white rounded-xl shadow p-6">
        @csrf
        <h3 class="font-semibold mb-2">Twende Dukani — Branch Location</h3>
        <p class="text-xs text-gray-500 mb-4">Set your branch location so customers can navigate directly to your shop.</p>
        <input type="hidden" name="latitude" id="profile-latitude" value="{{ $user->branch->latitude }}">
        <input type="hidden" name="longitude" id="profile-longitude" value="{{ $user->branch->longitude }}">
        <div id="profile-location-status" class="text-xs text-gray-500 mb-2">
            @if($user->branch->latitude && $user->branch->longitude)
                <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Current: {{ number_format($user->branch->latitude, 6) }}, {{ number_format($user->branch->longitude, 6) }}</span>
            @else
                <span class="text-amber-600"><i class="fas fa-exclamation-triangle mr-1"></i> No location set yet</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openProfileLocationModal()" id="profile-spot-btn"
                class="px-4 py-2 bg-green-50 border border-green-200 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 transition">
                <i class="fas fa-crosshairs mr-1"></i> Spot Branch Location
            </button>
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium text-sm">Save Location</button>
        </div>
    </form>

    <!-- Profile Location Modal -->
    <div id="profileLocationModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-map-marker-alt text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Twende Dukani</h3>
                <p class="text-gray-500 text-sm mb-6">Are you at the office right now?</p>
                <div class="flex gap-3">
                    <button type="button" onclick="closeProfileLocationModal()"
                        class="flex-1 px-4 py-3 border border-gray-300 text-gray-500 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                        No, Cancel
                    </button>
                    <button type="button" onclick="captureProfileLocation()" id="profileYesBtn"
                        class="flex-1 px-4 py-3 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition">
                        Yes, I'm Here
                    </button>
                </div>
                <div id="profile-location-loading" class="hidden mt-4">
                    <i class="fas fa-spinner fa-spin text-green-600 text-xl"></i>
                    <p class="text-xs text-gray-500 mt-2">Getting your location...</p>
                </div>
                <div id="profile-location-error" class="hidden mt-4 text-xs text-red-500"></div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@if($user->isBranchAdmin() && $user->branch)
@push('scripts')
<script>
    function openProfileLocationModal() {
        document.getElementById('profileLocationModal').classList.remove('hidden');
        document.getElementById('profile-location-loading').classList.add('hidden');
        document.getElementById('profile-location-error').classList.add('hidden');
    }

    function closeProfileLocationModal() {
        document.getElementById('profileLocationModal').classList.add('hidden');
    }

    function captureProfileLocation() {
        if (!navigator.geolocation) {
            document.getElementById('profile-location-error').textContent = 'Geolocation is not supported by your browser.';
            document.getElementById('profile-location-error').classList.remove('hidden');
            return;
        }

        document.getElementById('profileYesBtn').disabled = true;
        document.getElementById('profile-location-loading').classList.remove('hidden');
        document.getElementById('profile-location-error').classList.add('hidden');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;

                document.getElementById('profile-latitude').value = lat;
                document.getElementById('profile-longitude').value = lng;

                document.getElementById('profile-location-loading').classList.add('hidden');
                document.getElementById('profileYesBtn').disabled = false;

                document.getElementById('profile-location-status').innerHTML =
                    '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> New: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ' — Click Save to update</span>';

                closeProfileLocationModal();
            },
            function(error) {
                document.getElementById('profile-location-loading').classList.add('hidden');
                document.getElementById('profileYesBtn').disabled = false;
                var msg = 'Unable to get your location.';
                if (error.code === 1) msg = 'Location access denied. Please allow location access in your browser.';
                else if (error.code === 2) msg = 'Location unavailable. Please try again.';
                else if (error.code === 3) msg = 'Location request timed out. Please try again.';
                document.getElementById('profile-location-error').textContent = msg;
                document.getElementById('profile-location-error').classList.remove('hidden');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
</script>
@endpush
@endif
