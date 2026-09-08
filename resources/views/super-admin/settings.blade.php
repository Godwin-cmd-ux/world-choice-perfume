@extends('layouts.app')
@section('title', 'Company Settings')

@section('header', 'Company Settings')
@section('subtitle', 'Manage company secret codes')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('super-admin.settings.update') }}" class="bg-white rounded-xl border border-gray-200 p-6">
        @csrf
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Super Admin Secret Code <span class="text-red-500">*</span></label>
                <input type="text" name="super_admin_secret" value="{{ old('super_admin_secret', $superAdminSecret) }}" required
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('super_admin_secret') border-red-500 @enderror">
                @error('super_admin_secret') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Required to register a Super Admin.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Staff Secret Code <span class="text-red-500">*</span></label>
                <input type="text" name="staff_secret_code" value="{{ old('staff_secret_code', $staffSecretCode) }}" required
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('staff_secret_code') border-red-500 @enderror">
                @error('staff_secret_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Required to register staff (Branch Admin, Cashier, Stock Manager, Seller, Customer Care) and for staff login.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Secrets
                </button>
                <a href="{{ route('super-admin.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection