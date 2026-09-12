@extends('layouts.app')
@section('title', 'Edit News Post')
@section('header', 'Edit News Post')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('graphic-designer.news.update', $post->id) }}">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $post->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <textarea name="content" rows="8" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">{{ old('content', $post->content) }}</textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium"><i class="fas fa-save mr-1"></i> Update</button>
                <a href="{{ route('graphic-designer.news.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection