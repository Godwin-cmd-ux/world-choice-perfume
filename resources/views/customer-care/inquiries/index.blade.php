@extends('layouts.app')
@section('title', 'Inquiries')
@section('header', 'Customer Inquiries')
@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All</option>
            <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-8 px-4"></th>
                    <th class="text-left py-3 px-4">Subject</th>
                    <th class="text-left px-4">From</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-left px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inquiries as $i)
                    <tr class="border-t hover:bg-gray-50 {{ ($i->is_read ?? false) ? '' : 'bg-blue-50' }}">
                        <td class="px-4">
                            @if(!($i->is_read ?? false))
                                <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('customer-care.inquiries.show', $i->id) }}" class="font-medium hover:text-blue-700">{{ $i->subject ?? 'No Subject' }}</a>
                            <p class="text-xs text-gray-400 truncate max-w-xs">{{ $i->message ?? '' }}</p>
                        </td>
                        <td class="px-4 text-gray-500">{{ $i->email ?? $i->user?->name ?? 'Customer' }}</td>
                        <td class="px-4">
                            @if(($i->status ?? '') === 'replied')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Replied</span>
                            @elseif($i->is_read ?? false)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Read</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-600">Unread</span>
                            @endif
                        </td>
                        <td class="px-4 text-xs text-gray-500">{{ $i->created_at ? \Carbon\Carbon::parse($i->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '—' }}</td>
                        <td class="px-4">
                            <div class="flex items-center gap-2">
                                @if(!($i->is_read ?? false))
                                    <form action="{{ route('customer-care.inquiries.mark-read', $i->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:underline text-xs font-medium"><i class="fas fa-check mr-1"></i>Mark as Read</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400"><i class="fas fa-check mr-1"></i>Read</span>
                                @endif
                                @if(!($i->is_featured ?? false))
                                    <form action="{{ route('customer-care.inquiries.comment', $i->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:underline text-xs font-medium"><i class="fas fa-star mr-1"></i>Mark as Comment</button>
                                    </form>
                                @else
                                    <span class="text-xs text-amber-600 font-medium"><i class="fas fa-star mr-1"></i>Featured</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-gray-400"><i class="fas fa-envelope-open text-3xl mb-2 block"></i>No inquiries</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
