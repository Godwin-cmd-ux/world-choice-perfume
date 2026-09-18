@extends('layouts.app')
@section('title', 'Edit Brand')
@section('header', 'Edit: ' . $brand->name)
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('graphic-designer.brands.update', $brand->id) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name *</label>
                    <input type="text" name="name" value="{{ old('name', $brand->name) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-[11px] text-gray-400 mt-1">Renaming also updates the brand on all matching products so the shop filter keeps working.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo / Photo</label>
                    @if($brand->logo_url)
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-20 h-20 rounded-xl object-cover border border-gray-200 mb-2">
                        <p class="text-[11px] text-gray-400 mb-1">Upload a new image below to replace it.</p>
                    @else
                        <p class="text-[11px] text-gray-400 mb-1">No logo yet — upload one to show on the home page.</p>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="w-full px-3 py-2 border rounded-lg">
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ $brand->is_active ? 'checked' : '' }} class="rounded text-purple-600 focus:ring-purple-500">
                    <span class="text-sm text-gray-700">Show this brand on the home page</span>
                </label>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-6 py-2 rounded-lg font-medium"><i class="fas fa-save mr-1"></i> Update Brand</button>
                <a href="{{ route('graphic-designer.brands.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection