@extends('layouts.app')
@section('title', 'Edit Branch')

@section('header', 'Edit Branch')
@section('subtitle', $branch->name)

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('super-admin.branches.update', $branch->id) }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 p-6">
        @csrf @method('PUT')
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $branch->name) }}" required
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('name') border-red-500 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                <input type="text" name="address" value="{{ old('address', $branch->address) }}"
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Latitude</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $branch->latitude) }}"
                           class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Longitude</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $branch->longitude) }}"
                           class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $branch->is_active ? 'checked' : '' }}
                           class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <span class="text-sm font-medium text-gray-700">Branch is Active</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Profile Photo</label>
                @if($branch->profile_picture)
                    <div class="mb-3">
                        <img src="{{ $branch->profile_picture }}" class="w-20 h-20 rounded-xl object-cover border border-gray-200">
                    </div>
                @endif
                <input type="file" name="profile_picture" accept="image/*"
                       class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>
        </div>

        <div class="flex gap-3 mt-8 pt-6 border-t border-gray-100">
            <a href="{{ route('super-admin.branches.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                <i class="fas fa-save mr-1.5 text-xs"></i> Update Branch
            </button>
        </div>
    </form>
</div>
@endsection
