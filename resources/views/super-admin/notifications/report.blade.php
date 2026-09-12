@extends('layouts.app')
@section('title', 'Notifications Report')
@section('header', 'Notifications Report')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition">
        <i class="fas fa-print mr-1"></i> Print Report
    </button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Types</option>
            <option value="discount_used" {{ ($type ?? '') === 'discount_used' ? 'selected' : '' }}>Discount Used</option>
            <option value="price_customization" {{ ($type ?? '') === 'price_customization' ? 'selected' : '' }}>Price Customization</option>
            <option value="order_placed" {{ ($type ?? '') === 'order_placed' ? 'selected' : '' }}>Order Placed</option>
        </select>
        <input type="date" name="date_from" value="{{ $date_from ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $date_to ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('super-admin.notifications.generate-report') }}" class="text-gray-500 hover:text-gray-700 text-sm">Reset</a>
    </form>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-red-600">{{ $notifications->where('type', 'discount_used')->count() }}</p>
        <p class="text-xs text-gray-500">Discounts</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-purple-600">{{ $notifications->where('type', 'price_customization')->count() }}</p>
        <p class="text-xs text-gray-500">Price Customizations</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $notifications->where('type', 'order_placed')->count() }}</p>
        <p class="text-xs text-gray-500">Orders</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-600">{{ $notifications->count() }}</p>
        <p class="text-xs text-gray-500">Total</p>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Type</th>
                    <th class="text-left px-4">Message</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-left px-4">User</th>
                    <th class="text-left px-4">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                    <tr class="border-t">
                        <td class="py-3 px-4">
                            @php
                                $typeLabels = [
                                    'discount_used' => 'bg-red-100 text-red-800',
                                    'price_customization' => 'bg-purple-100 text-purple-800',
                                    'order_placed' => 'bg-blue-100 text-blue-800',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeLabels[$n->type ?? ''] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ str_replace('_', ' ', ucfirst($n->type ?? 'unknown')) }}
                            </span>
                        </td>
                        <td class="px-4">{{ $n->message ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $n->branch_name ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $n->user_name ?? '—' }}</td>
                        <td class="px-4 text-xs text-gray-500">{{ $n->created_at ? \Carbon\Carbon::parse($n->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No notifications found for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 text-xs text-gray-400 no-print">Generated: {{ now()->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y \a\t h:i A') }}</div>
@endsection
