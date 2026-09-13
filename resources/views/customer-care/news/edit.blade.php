@extends('layouts.app')
@section('title', 'Edit Post')
@section('header', 'Edit News Post')
@section('header-actions')
    <a href="{{ route('customer-care.news.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Back to News
    </a>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow p-6">
            <form method="POST" action="{{ route('customer-care.news.update', $post->id) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <textarea name="content" rows="10" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('content', $post->content) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                    <select name="branch_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 outline-none">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $post->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Replace image (optional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" alt="" class="h-16 mt-2 rounded object-cover">
                    @endif
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    {{-- Sidebar: status + actions --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Status</h4>
            @if($post->status === 'approved')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle"></i> Approved &amp; Published
                </span>
            @elseif($post->status === 'rejected')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <i class="fas fa-times-circle"></i> Rejected
                </span>
                @if($post->rejection_reason)
                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                        <strong>Reason:</strong> {{ $post->rejection_reason }}
                    </div>
                @endif
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    <i class="fas fa-clock"></i> Pending Review
                </span>
            @endif
            <div class="text-xs text-gray-400 mt-3">
                {{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y \a\t h:i A') : '' }}
            </div>
        </div>

        @if($post->status !== 'approved')
        <div class="bg-white rounded-xl shadow p-6 space-y-3">
            <h4 class="text-sm font-semibold text-gray-700">Moderation</h4>
            <form method="POST" action="{{ route('customer-care.news.approve', $post->id) }}">
                @csrf
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-check mr-1"></i> Approve &amp; Publish
                </button>
            </form>
            <form id="edit-reject-form" method="POST" action="{{ route('customer-care.news.reject', $post->id) }}">
                @csrf
                <textarea name="rejection_reason" rows="3" maxlength="500" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
                          placeholder="Rejection reason (required)"></textarea>
                <button type="submit" class="w-full mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-times mr-1"></i> Reject
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection