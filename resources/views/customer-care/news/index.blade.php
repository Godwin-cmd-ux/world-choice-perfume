@extends('layouts.app')
@section('title', 'News')
@section('header', 'News')
@section('header-actions')
    <a href="{{ route('customer-care.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
    </a>
@endsection
@section('content')
{{-- Tabs --}}
<div class="flex items-center gap-2 border-b border-gray-200 mb-6">
    <a href="{{ route('customer-care.news.index', ['tab' => 'designer']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'designer' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        <i class="fas fa-palette mr-1"></i> From Designer
        <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ $counts['pending'] }} pending</span>
    </a>
    <a href="{{ route('customer-care.news.index', ['tab' => 'custom']) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'custom' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        <i class="fas fa-pen-nib mr-1"></i> Custom News
    </a>
</div>

@if($tab === 'designer')
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center gap-3 text-xs text-gray-500">
            <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> {{ $counts['approved'] }} approved</span>
            <span class="flex items-center gap-1"><i class="fas fa-clock text-amber-500"></i> {{ $counts['pending'] }} pending</span>
            <span class="flex items-center gap-1"><i class="fas fa-times-circle text-red-500"></i> {{ $counts['rejected'] }} rejected</span>
        </div>
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
                    @forelse($designerPosts as $post)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium max-w-xs">
                                <div class="truncate">{{ $post->title }}</div>
                                @if($post->status === 'rejected' && $post->rejection_reason)
                                    <div class="text-xs text-red-600 mt-1"><i class="fas fa-info-circle mr-1"></i>{{ $post->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="px-4 text-gray-500">{{ $post->branch?->name ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ $post->author?->name ?? '—' }}</td>
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
                                    @if($post->status === 'pending')
                                        <a href="{{ route('customer-care.news.edit', $post->id) }}"
                                           class="px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                        <form method="POST" action="{{ route('customer-care.news.approve', $post->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" style="background-color: #F89A1E;" class="px-2.5 py-1 rounded-md text-xs font-medium  text-white hover:opacity-90">
                                                <i class="fas fa-check mr-1"></i>Approve
                                            </button>
                                        </form>
                                        <button type="button" data-reject-open="{{ $post->id }}" data-title="{{ $post->title }}"
                                                class="px-2.5 py-1 rounded-md text-xs font-medium bg-red-600 text-white hover:bg-red-700">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    @elseif($post->status === 'rejected')
                                        <form method="POST" action="{{ route('customer-care.news.destroy', $post->id) }}" class="inline" data-confirm="Delete this rejected post?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-medium text-red-600 hover:bg-red-50">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Published</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-gray-400"><i class="fas fa-newspaper text-3xl mb-2 block"></i>No posts from the designer yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Custom news form --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-pen-nib text-blue-600 mr-2"></i>Write Custom News</h3>
                <form method="POST" action="{{ route('customer-care.news.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                        <textarea name="content" rows="6" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('content') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select name="branch_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 outline-none">
                            <option value="">Select branch</option>
                            @foreach($branches ?? [] as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image (optional)</label>
                        <input type="file" name="image" accept="image/*" class="w-full text-sm">
                    </div>
                    <button type="submit" style="background-color: #F89A1E;" class="w-full  hover:opacity-90 text-white px-4 py-2.5 rounded-lg text-sm font-medium">
                        <i class="fas fa-paper-plane mr-1"></i> Publish Now
                    </button>
                </form>
            </div>
        </div>

        {{-- Custom news list --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-700">
                    <i class="fas fa-list mr-1"></i> Published by Customer Care ({{ $counts['custom'] }})
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4">Title</th>
                                <th class="text-left px-4">Branch</th>
                                <th class="text-left px-4">Date</th>
                                <th class="text-center px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customPosts as $post)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">
                                        <span class="flex items-center gap-2">
                                            {{ $post->title }}
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Live</span>
                                        </span>
                                    </td>
                                    <td class="px-4 text-gray-500">{{ $post->branch?->name ?? '—' }}</td>
                                    <td class="px-4 text-xs text-gray-500">{{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</td>
                                    <td class="px-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('customer-care.news.edit', $post->id) }}"
                                               class="px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form method="POST" action="{{ route('customer-care.news.destroy', $post->id) }}" class="inline" data-confirm="Delete this custom news post?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-medium text-red-600 hover:bg-red-50">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-12 text-center text-gray-400"><i class="fas fa-pen-nib text-3xl mb-2 block"></i>No custom news yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Reject modal --}}
<div id="reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 p-6">
        <h3 class="font-semibold text-gray-800 mb-1"><i class="fas fa-times-circle text-red-600 mr-2"></i>Reject Post</h3>
        <p class="text-sm text-gray-500 mb-4">Reason for rejecting <span id="reject-title" class="font-medium text-gray-700"></span>:</p>
        <form id="reject-form" method="POST" action="">
            @csrf
            <input type="hidden" id="reject-id" name="post_id" value="">
            <textarea name="rejection_reason" id="reject-reason" rows="3" required maxlength="500"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
                      placeholder="Required — the designer will see this reason."></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button type="button" id="reject-cancel" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" style="background-color: #F89A1E;" class="px-4 py-2 rounded-lg text-sm font-medium text-white  hover:opacity-90">
                    <i class="fas fa-times mr-1"></i> Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-reject-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-reject-open');
            var title = btn.getAttribute('data-title');
            document.getElementById('reject-title').textContent = title ? '"' + title + '"' : '';
            document.getElementById('reject-id').value = id;
            var form = document.getElementById('reject-form');
            form.action ="{{ url('customer-care/news') }}" + '/' + id + '/reject';
            var modal = document.getElementById('reject-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('reject-reason').focus();
        });
    });
    document.getElementById('reject-cancel').addEventListener('click', function () {
        var modal = document.getElementById('reject-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
</script>
@endpush
@endsection