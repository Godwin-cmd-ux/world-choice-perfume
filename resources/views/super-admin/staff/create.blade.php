@extends('layouts.app')
@section('title', 'Add Staff')
@section('header', 'Add Staff')
@section('subtitle', 'Create a staff account and assign it to any branch')
@section('header-actions')
    <a href="{{ route('super-admin.staff.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left mr-1"></i> Back to Staff
    </a>
@endsection
@section('content')
<form method="POST" action="{{ route('super-admin.staff.store') }}" class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6">
    @csrf
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                   class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                   placeholder="Staff full name">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                       placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="20"
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                       placeholder="+255 7XX XXX XXX">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
            <select name="role" required class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 outline-none">
                <option value="">Select role</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
            <select name="branch_id" required class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 outline-none">
                <option value="">Select branch</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                       placeholder="Min. 8 characters">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                       placeholder="Repeat the password">
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-700">
            <i class="fas fa-info-circle mr-1"></i> The staff member will be able to log in immediately with the email and password you set here. Share their credentials securely.
        </div>
    </div>

    <div class="flex gap-3 mt-6 pt-5 border-t border-gray-100">
        <a href="{{ route('super-admin.staff.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</a>
        <button type="submit" style="background-color: #F89A1E;" class="px-6 py-2.5  hover:opacity-90 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
            <i class="fas fa-user-plus mr-1"></i> Create Staff Account
        </button>
    </div>
</form>
@endsection