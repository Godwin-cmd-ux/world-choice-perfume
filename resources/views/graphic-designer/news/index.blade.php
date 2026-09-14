@extends('layouts.app')
@section('title', 'My News Posts')
@section('header', 'My News Posts')
@section('header-actions')
    <div class="flex items-center gap-3 text-xs text-gray-500">
        <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> {{ $counts['approved'] }}</span>
        <span class="flex items-center gap-1"><i class="fas fa-clock text-amber-500"></i> {{ $counts['pending'] }}</span>
        <span class="flex items-center gap-1"><i class="fas fa-times-circle text-red-500"></i> {{ $counts['rejected'] }}</span>
    </div>
    <a href="{{ route('graphic-designer.news.create') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
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
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium max-w-xs">
                            <div class="truncate">{{ $post->title }}</div>
                            @if($post->status === 'rejected' && $post->rejection_reason)
                                <div class="text-xs text-red-600 mt-1"><i class="fas fa-info-circle mr-1"></i>{{ $post->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">{{ $post->branch?->name ?? '—' }}</td>
                        <td class="px-4">
                            @if($post->status === 'approved')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Approved</span>
                            @elseif($post->status === 'rejected')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800">Rejected</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 text-xs text-gray-500">{{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</td>
                        <td class="px-4">
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
                    <tr><td colspan="5" class="py-12 text-center text-gray-400"><i class="fas fa-newspaper text-3xl mb-2 block"></i>No posts yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection