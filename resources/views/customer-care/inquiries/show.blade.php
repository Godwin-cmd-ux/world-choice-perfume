@extends('layouts.app')
@section('title', 'Inquiry Details')
@section('header', 'Inquiry: ' . ($inquiry->subject ?? ''))
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">{{ $inquiry->subject ?? 'No Subject' }}</h3>
                @if(($inquiry->status ?? '') === 'replied')
                    <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Replied</span>
                @endif
            </div>
            <div class="prose prose-sm max-w-none text-gray-700 mb-6">
                <p>{{ $inquiry->message ?? 'No message content.' }}</p>
            </div>

            @if($inquiry->reply_message)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                    <h4 class="font-semibold text-green-800 mb-2"><i class="fas fa-reply mr-1"></i> Your Reply</h4>
                    <p class="text-sm text-green-700">{{ $inquiry->reply_message }}</p>
                </div>
            @endif

            @if(!($inquiry->status ?? '') === 'replied')
                <div class="border-t mt-6 pt-4">
                    <h4 class="font-semibold mb-3">Reply</h4>
                    <form method="POST" action="{{ route('customer-care.inquiries.reply', $inquiry->id) }}">
                        @csrf
                        <textarea name="reply_message" rows="4" required placeholder="Type your reply..." class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mb-3"></textarea>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium"><i class="fas fa-paper-plane mr-1"></i> Send Reply</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3">Details</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">From:</span><span>{{ $inquiry->user->name ?? 'Customer' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email:</span><span>{{ $inquiry->email ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone:</span><span>{{ $inquiry->phone ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Date:</span><span>{{ $inquiry->created_at ? \Carbon\Carbon::parse($inquiry->created_at)->format('M d, Y H:i') : '—' }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('customer-care.inquiries.index') }}" class="text-blue-600 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Inquiries</a>
</div>
@endsection
