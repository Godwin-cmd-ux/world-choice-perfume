@extends('stock-manager.layouts.app')

@section('title', 'Notifications')
@section('header', 'Notifications')
@section('header-subtitle', 'Low-stock alerts for your current branch')

@section('header-actions')
    @if($readOnly)
        <span class="text-xs font-medium text-gray-500"><i class="fas fa-lock mr-1"></i> Read-only while monitoring another branch</span>
    @else
        <form method="POST" action="{{ route('stock-manager.notifications.mark-all-read') }}" class="inline">
            @csrf
            <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-check-double mr-1"></i> Mark All Read
            </button>
        </form>
    @endif
@endsection

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 w-8"></th>
                    <th class="text-left px-4">Alert</th>
                    <th class="text-left px-4">Details</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                    <tr class="border-t {{ ($n->is_read ?? false) ? '' : 'bg-amber-50' }}">
                        <td class="py-3 px-4">
                            @if(!($n->is_read ?? false))
                                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                            @endif
                        </td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                {{ $n->title ?? 'Stock Alert' }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-600" title="{{ $n->message ?? '' }}">{{ $n->message ?? '—' }}</td>
                        <td class="px-4 text-xs text-gray-500">
                            {{ $n->created_at ? \Carbon\Carbon::parse($n->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') : '—' }}
                        </td>
                        <td class="px-4 text-center">
                            @if(!($n->is_read ?? false) && !$readOnly)
                                <form method="POST" action="{{ route('stock-manager.notifications.mark-read', $n->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-600 hover:text-emerald-800 text-xs" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fas fa-bell text-3xl mb-2 block"></i>
                            No stock notifications for this branch
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
