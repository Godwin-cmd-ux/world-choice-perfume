<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Register as Super Admin - World Choice Perfumes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-amber-50 to-orange-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
        <div class="text-center mb-6">
            <img src="{{ asset('our_logo.jpeg') }}" alt="Logo" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover">
            <h1 class="text-2xl font-bold text-amber-900">Super Admin Registration</h1>
        </div>
        <form method="POST" action="{{ route('register.super-admin') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('email') border-red-500 @enderror">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('phone') border-red-500 @enderror">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Secret Code *</label>
                    <input type="text" name="secret_code" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('secret_code') border-red-500 @enderror">
                    @error('secret_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('password') border-red-500 @enderror">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <button type="submit" class="w-full mt-6 bg-amber-700 hover:bg-amber-800 text-white font-semibold py-2 px-4 rounded-lg transition">Register</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-500">Already have an account? <a href="{{ route('login') }}" class="text-amber-700 hover:underline">Sign In</a></p>
    </div>
</body>
</html>
