@extends('layouts.app')
@section('title', 'Create Branch')

@section('header', 'Create Branch')
@section('subtitle', 'Add a new store location')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('super-admin.branches.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 p-6">
        @csrf
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('name') border-red-500 @enderror"
                       placeholder="e.g. Posta Main Store">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                       placeholder="e.g. Posta Road, Dar es Salaam">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Latitude</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude') }}"
                           class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                           placeholder="-6.7924">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Longitude</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude') }}"
                           class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                           placeholder="39.2083">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch Photo</label>
                <input type="file" name="profile_picture" accept="image/*"
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign Admin</label>
                <select name="admin_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    <option value="">— Select Later —</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->email }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-8 pt-6 border-t border-gray-100">
            <a href="{{ route('super-admin.branches.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                <i class="fas fa-plus mr-1.5 text-xs"></i> Create Branch
            </button>
        </div>
    </form>
</div>
@endsection
