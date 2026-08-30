@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
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
                        <td class="py-3 px-4 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4">{{ $order->customer?->name ?? 'N/A' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($order->total) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ route('cashier.orders.show', $order->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @if($order->status === 'pending')
                                <form action="{{ route('cashier.orders.pick', $order->id) }}" method="POST" class="inline">
                                    @csrf <button type="submit" class="text-green-600 hover:underline font-medium"><i class="fas fa-hand-pointer mr-1"></i>Pick</button>
                                </form>
                            @endif
                            @if($order->status === 'assigned' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                                <form action="{{ route('cashier.orders.ready', $order->id) }}" method="POST" class="inline">
                                    @csrf <button type="submit" class="text-amber-600 hover:underline mr-2"><i class="fas fa-check mr-1"></i>Ready</button>
                                </form>
                            @endif
                            @if($order->status === 'ready' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                                <form action="{{ route('cashier.orders.complete', $order->id) }}" method="POST" class="inline">
                                    @csrf <button type="submit" class="text-amber-600 hover:underline mr-2"><i class="fas fa-check-double mr-1"></i>Complete</button>
                                </form>
                            @endif
                            @if($order->status === 'completed' && $order->cashier_id == (auth()->user()->supabase_id ?? auth()->id()))
                                <form action="{{ route('cashier.orders.serve', $order->id) }}" method="POST" class="inline">
                                    @csrf <button type="submit" class="text-green-700 hover:underline font-bold"><i class="fas fa-hand-holding mr-1"></i>Served</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No orders available</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
