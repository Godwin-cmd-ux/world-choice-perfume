@extends('layouts.app')
@section('title', 'Create News Post')
@section('header', 'Create News Post')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('customer-care.news.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <textarea name="content" rows="8" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('content') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image (optional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"><i class="fas fa-save mr-1"></i> Publish</button>
                <a href="{{ route('customer-care.news.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
