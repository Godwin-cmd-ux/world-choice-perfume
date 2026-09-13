@extends('layouts.app')
@section('title', 'News Dashboard')
@section('header', 'News Dashboard')
@section('content')
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    @php
        $cards = [
            ['label' => 'Total Posts',    'count' => $counts['total'],    'icon' => 'fa-newspaper', 'bg' => 'bg-purple-50', 'iconBg' => 'bg-purple-100 text-purple-600'],
            ['label' => 'Approved',       'count' => $counts['approved'], 'icon' => 'fa-check-circle', 'bg' => 'bg-green-50', 'iconBg' => 'bg-green-100 text-green-600'],
            ['label' => 'Pending',        'count' => $counts['pending'],  'icon' => 'fa-clock', 'bg' => 'bg-amber-50', 'iconBg' => 'bg-amber-100 text-amber-600'],
            ['label' => 'Rejected',       'count' => $counts['rejected'], 'icon' => 'fa-times-circle', 'bg' => 'bg-red-50', 'iconBg' => 'bg-red-100 text-red-600'],
            ['label' => "Today's Posts",  'count' => $counts['today'],    'icon' => 'fa-calendar-day', 'bg' => 'bg-blue-50', 'iconBg' => 'bg-blue-100 text-blue-600'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="{{ $card['bg'] }} border border-gray-200 rounded-xl p-5 flex items-center gap-4">
            <div class="{{ $card['iconBg'] }} w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas {{ $card['icon'] }}"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ $card['count'] }}</p>
                <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>

@if($rejected->count())
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800"><i class="fas fa-times-circle text-red-500 mr-2"></i> Rejected Posts</h3>
        <span class="text-xs text-gray-400">{{ $rejected->count() }} post(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Title</th>
                    <th class="text-left px-4">Rejection Reason</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rejected as $post)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $post->title }}</td>
                        <td class="px-4 text-sm text-red-600">{{ $post->rejection_reason ?? '—' }}</td>
                        <td class="px-4 text-xs text-gray-500">{{ $post->created_at ? \Carbon\Carbon::parse($post->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</td>
                        <td class="px-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('graphic-designer.news.edit', $post->id) }}"
                                   class="px-2.5 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700 hover:bg-purple-200">
                                    <i class="fas fa-pen mr-1"></i>Redesign &amp; Submit
                                </a>
                                <form method="POST" action="{{ route('graphic-designer.news.destroy', $post->id) }}" class="inline" data-confirm="Delete this rejected post?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-medium text-red-600 hover:bg-red-50">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-xl shadow p-12 text-center">
    <i class="fas fa-check-circle text-green-400 text-4xl mb-3 block"></i>
    <p class="text-gray-500 font-medium">All your posts have been approved. Great work!</p>
</div>
@endif
@endsection