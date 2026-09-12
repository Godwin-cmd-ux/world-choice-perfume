@extends('layouts.app')
@section('title', 'News Posts')
@section('header', 'News Posts')
@section('header-actions')
    <a href="{{ route('graphic-designer.news.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> New Post
    </a>
@endsection
@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Title</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-left px-4">Author</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $post->title }}</td>
                        <td class="px-4 text-gray-500">{{ $post->branch?->name ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $post->author?->name ?? '—' }}</td>
                        <td class="px-4">
                            @if($post->is_published)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Published</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 text-xs text-gray-500">{{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->format('M d, Y') : '—' }}</td>
                        <td class="px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('graphic-designer.news.edit', $post->id) }}" class="text-purple-600 hover:text-purple-800"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('graphic-designer.news.destroy', $post->id) }}" class="inline" data-confirm="Delete this post?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-gray-400"><i class="fas fa-newspaper text-3xl mb-2 block"></i>No posts yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection