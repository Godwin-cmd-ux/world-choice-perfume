@extends('layouts.app')
@section('title', 'Notifications')
@section('header', 'Notifications')

@section('header-actions')
    <a href="{{ route('super-admin.notifications.generate-report') }}" class="bg-amber-700 hover:bg-amber-800 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-file-alt mr-1"></i> Generate Report
    </a>
    <form method="POST" action="{{ route('super-admin.notifications.mark-all-read') }}" class="inline">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-check-double mr-1"></i> Mark All Read
        </button>
    </form>
@endsection

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Types</option>
            <option value="discount_used" {{ request('type') === 'discount_used' ? 'selected' : '' }}>Discount Used</option>
            <option value="price_customization" {{ request('type') === 'price_customization' ? 'selected' : '' }}>Price Customization</option>
            <option value="order_placed" {{ request('type') === 'order_placed' ? 'selected' : '' }}>Order Placed</option>
            <option value="staff_blocked" {{ request('type') === 'staff_blocked' ? 'selected' : '' }}>Staff Blocked</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border rounded-lg text-sm" placeholder="From">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border rounded-lg text-sm" placeholder="To">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

{{-- Notifications List --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 w-8"></th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-left px-4">Message</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-left px-4">User</th>
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
                            @php
                                $typeLabels = [
                                    'discount_used' => ['label' => 'Discount', 'class' => 'bg-red-100 text-red-800'],
                                    'price_customization' => ['label' => 'Price Custom', 'class' => 'bg-purple-100 text-purple-800'],
                                    'order_placed' => ['label' => 'Order', 'class' => 'bg-blue-100 text-blue-800'],
                                    'staff_blocked' => ['label' => 'Staff', 'class' => 'bg-gray-100 text-gray-800'],
                                ];
                                $typeInfo = $typeLabels[$n->type ?? ''] ?? ['label' => ucfirst($n->type ?? 'unknown'), 'class' => 'bg-gray-100 text-gray-800'];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                        </td>
                        <td class="px-4 max-w-xs truncate">{{ $n->message ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $n->branch?->name ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $n->user?->name ?? '—' }}</td>
                        <td class="px-4 text-xs text-gray-500">{{ $n->created_at ? \Carbon\Carbon::parse($n->created_at)->format('M d, Y H:i') : '—' }}</td>
                        <td class="px-4 text-center">
                            @if(!($n->is_read ?? false))
                                <form method="POST" action="{{ route('super-admin.notifications.mark-read', $n->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs" title="Mark as read"><i class="fas fa-check"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <i class="fas fa-bell text-3xl mb-2 block"></i>
                            No notifications found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
