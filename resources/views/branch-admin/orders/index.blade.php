@extends('layouts.app')
@section('title', 'Branch Orders')
@section('header', 'Branch Orders')

@section('content')
{{-- Branch Admin supervises the branch, so these tabs carry the branch's own
     labels rather than the shared "My ..." wording, and they list every order
     placed here, not only the ones this admin picked. --}}
@include('partials.order-tabs', ['tabRoute' => $tabRoute, 'tabLabels' => $tabLabels])

@php
    $watch = $pendingWatch ?? ['count' => 0, 'longest_minutes' => null, 'longest_label' => '—', 'late_count' => 0];
@endphp

{{-- How the branch queue is doing right now, on any tab. --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">Waiting to be picked</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $watch['count'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Unclaimed orders at this branch</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">Longest wait</p>
        <p class="text-2xl font-bold {{ ($watch['longest_minutes'] ?? 0) >= \App\Services\OrderWorkflowService::WAIT_LATE_MINUTES ? 'text-red-600' : 'text-amber-600' }}">{{ $watch['longest_label'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $watch['late_count'] }} waiting over {{ \App\Services\OrderWorkflowService::WAIT_LATE_MINUTES }}m</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">On progress</p>
        <p class="text-2xl font-bold text-blue-600">{{ $counts['progress'] ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Picked, not yet served</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500">Completed</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $counts['completed'] ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Served at this branch</p>
    </div>
</div>

<form method="GET" action="{{ route($tabRoute) }}" class="mb-4 flex justify-end">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="relative w-full sm:w-72">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search order number or personal name"
               class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
    </div>
</form>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Personal Name</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-left px-4">Picked By</th>
                <th class="text-left px-4">Waiting</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        // assigned_to is the current picker column, cashier_id
                        // the legacy one still set on orders placed before it.
                        $me = (string) $userId;
                        $isMine = (string) ($order->assigned_to ?? '') === $me
                            || (string) ($order->cashier_id ?? '') === $me;
                        $canName = $isMine && in_array($order->status, ['picked', 'served'], true);
                        $next = $order->status === 'pending' ? 'picked' : ($order->status === 'picked' && $isMine ? 'served' : null);
                        $waitingFrom = match ($order->status) {
                            'served' => 'since served',
                            'picked' => 'since picked',
                            default => 'since placed',
                        };
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4">
                            @include('partials.order-personal-name', ['order' => $order, 'nameRoute' => $nameRoute, 'canName' => $canName])
                        </td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-600">
                            {{ $order->assigned_to ? ($pickers[(string) $order->assigned_to] ?? 'Staff') : '—' }}
                        </td>
                        {{-- How long this order has been in its current state, so a
                             supervisor can see at a glance what is going stale. --}}
                        <td class="px-4 whitespace-nowrap">
                            <span @if(!empty($order->waiting_since)) title="{{ $order->waiting_since->copy()->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}" @endif
                                  class="font-medium {{ match($order->waiting_tone ?? 'unknown') { 'late' => 'text-red-600', 'warn' => 'text-amber-600', 'ok' => 'text-gray-500', default => 'text-gray-400' } }}">
                                {{ $order->waiting_label ?? '—' }}
                            </span>
                            <span class="block text-[10px] text-gray-400">{{ $waitingFrom }}</span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('branch-admin.orders.show', $order->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @if($next)
                                <form action="{{ route('branch-admin.orders.update-status', $order->id) }}" method="POST" class="inline" onsubmit="return requireNote(this)">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <input type="hidden" name="note">
                                    <button type="submit" class="{{ $next === 'picked' ? 'text-green-600' : 'text-green-700 font-bold' }} hover:underline font-medium">
                                        <i class="fas {{ $next === 'picked' ? 'fa-hand-pointer' : 'fa-hand-holding' }} mr-1"></i>{{ ucfirst($next) }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-400">
                            @if(request('q'))
                                No orders match "{{ request('q') }}".
                            @elseif($tab === 'pending')
                                No orders are waiting to be picked at this branch.
                            @elseif($tab === 'progress')
                                No orders are currently on progress at this branch.
                            @else
                                No orders have been completed at this branch yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Who is carrying the open work, so one slow order is traceable to a name. --}}
<div class="bg-white rounded-xl shadow overflow-hidden mt-5">
    <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-800"><i class="fas fa-users text-amber-600 mr-1"></i>Who is picking</h3>
        <p class="text-xs text-gray-500">Open = picked and not yet served</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-2.5 px-4">Staff</th>
                <th class="text-center px-4">Open</th>
                <th class="text-left px-4">Holding longest</th>
                <th class="text-center px-4">Served</th>
            </tr></thead>
            <tbody>
                @forelse($team as $member)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-2.5 px-4 font-medium">{{ $member->name }}</td>
                        <td class="px-4 text-center">{{ $member->open }}</td>
                        <td class="px-4 {{ ($member->longest_open_tone ?? 'ok') === 'late' ? 'text-red-600 font-semibold' : (($member->longest_open_tone ?? 'ok') === 'warn' ? 'text-amber-600' : 'text-gray-500') }}">
                            {{ $member->longest_open_label }}
                        </td>
                        <td class="px-4 text-center text-gray-600">{{ $member->served }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-400">Nobody has picked an order at this branch yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
function requireNote(form) {
    var note = form.querySelector('input[name="note"]');
    if (!note || !note.value.trim()) {
        var val = prompt('Enter a note about this order update (what point you have reached):');
        if (val === null) return false;
        note.value = val;
    }
    return note.value.trim() !== '';
}
</script>
@endsection
