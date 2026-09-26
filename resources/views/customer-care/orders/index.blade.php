@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
@php
    $tabs = [
        'pending' => ['label' => 'Pending Orders', 'icon' => 'fa-clock'],
        'ongoing' => ['label' => 'My Ongoing Orders', 'icon' => 'fa-box-open'],
        'completed' => ['label' => 'My Completed Orders', 'icon' => 'fa-circle-check'],
    ];
@endphp

{{-- Orders split three ways: nobody has touched the pending ones yet,
     while picked and served orders belong to the staff member who picked them. --}}
<div class="border-b border-gray-200 mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="flex items-center gap-1 min-w-max">
        @foreach($tabs as $slug => $meta)
            <a href="{{ route('customer-care.orders.index', ['tab' => $slug]) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $tab === $slug ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas {{ $meta['icon'] }} mr-1"></i> {{ $meta['label'] }}
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $tab === $slug ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $counts[$slug] }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Order #</th>
                <th class="text-left px-4">Customer</th>
                <th class="text-right px-4">Total</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $order->order_number ?? 'N/A' }}</td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'picked' => 'bg-blue-100 text-blue-700', 'served' => 'bg-green-100 text-green-800 font-bold', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('customer-care.orders.show', $order->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @php $next = collect($transitions[$order->status] ?? [])->first(); @endphp
                            @if($next)
                                <form action="{{ route('customer-care.orders.update-status', $order->id) }}" method="POST" class="inline" onsubmit="return requireNote(this)">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <input type="hidden" name="note">
                                    <button type="submit" class="text-amber-600 hover:underline font-medium"><i class="fas fa-arrow-right mr-1"></i>{{ ucfirst($next) }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-400">
                            @if($tab === 'pending')
                                No orders waiting to be picked.
                            @elseif($tab === 'ongoing')
                                You have no orders in progress.
                            @else
                                You have not completed any orders yet.
                            @endif
                        </td>
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
