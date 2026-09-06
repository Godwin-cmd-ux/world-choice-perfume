@extends('stock-manager.layouts.app')
@section('title', 'My Profile')
@section('header', 'My Profile')
@section('header-subtitle', 'Manage your personal information and password')

@section('content')
<div class="max-w-2xl">
    {{-- Profile Info --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 mb-6">
        @csrf @method('PUT')
        <h3 class="font-semibold mb-4 text-gray-800">Personal Information</h3>
        <div class="space-y-4">
            <div class="flex items-center gap-4">
                @if($user->profile_picture)
                    <img src="{{ $user->profile_picture }}" class="w-20 h-20 rounded-full object-cover ring-2 ring-emerald-500">
                @else
                    <div class="w-20 h-20 rounded-full bg-emerald-600 flex items-center justify-center text-2xl font-bold text-white ring-2 ring-emerald-400">{{ substr($user->name, 0, 1) }}</div>
                @endif
                <input type="file" name="profile_picture" accept="image/*" class="text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:border-0 file:rounded-lg file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
            <div class="pt-3 border-t border-gray-100">
                <p class="text-sm text-gray-500">Role: <strong class="text-emerald-600 uppercase tracking-wider">{{ str_replace('_', ' ', $user->role) }}</strong></p>
                <p class="text-sm text-gray-500">Status: <strong class="capitalize {{ $user->status === 'active' ? 'text-green-600' : 'text-amber-600' }}">{{ $user->status }}</strong></p>
                @if($user->branch) <p class="text-sm text-gray-500">Branch: <strong>{{ $user->branch->name }}</strong></p> @endif
            </div>
        </div>
        <button type="submit" class="mt-6 w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold py-2.5 rounded-lg hover:from-emerald-400 hover:to-emerald-500 transition-all shadow-lg shadow-emerald-500/25">
            <i class="fas fa-save mr-2"></i> Update Profile
        </button>
    </form>

    {{-- Change Password --}}
    <form method="POST" action="{{ route('profile.password') }}" class="bg-white rounded-xl shadow p-6 mb-6">
        @csrf @method('PUT')
        <h3 class="font-semibold mb-4 text-gray-800">Change Password</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" name="current_password" placeholder="Current Password" required
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" placeholder="New Password" required minlength="8"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" placeholder="Confirm New Password" required minlength="8"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
            </div>
        </div>
        <button type="submit" class="mt-6 w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold py-2.5 rounded-lg hover:from-blue-400 hover:to-blue-500 transition-all shadow-lg shadow-blue-500/25">
            <i class="fas fa-key mr-2"></i> Change Password
        </button>
    </form>
</div>
@endsection
