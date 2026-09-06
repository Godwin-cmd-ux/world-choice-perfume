@extends('layouts.app')
@section('title', 'Customer Care Dashboard')
@section('header', 'Customer Care Dashboard')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-newspaper text-blue-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $newsCount }}</p><p class="text-sm text-gray-500">News Posts</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-envelope text-amber-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $inquiriesCount }}</p><p class="text-sm text-gray-500">Total Inquiries</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-circle text-red-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $unreadInquiries }}</p><p class="text-sm text-gray-500">Unread Inquiries</p></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold">Recent Inquiries</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Subject</th>
                    <th class="text-left px-4">From</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentInquiries as $i)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <a href="{{ route('customer-care.inquiries.show', $i->id) }}" class="font-medium hover:text-amber-700">{{ $i->subject ?? '—' }}</a>
                        </td>
                        <td class="px-4 text-gray-500">{{ $i->user->name ?? 'Customer' }}</td>
                        <td class="px-4">
                            @if(($i->is_read ?? false))
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Read</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-600">Unread</span>
                            @endif
                        </td>
                        <td class="px-4 text-xs text-gray-500">{{ $i->created_at ? \Carbon\Carbon::parse($i->created_at)->format('M d, H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-400">No inquiries yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
