@extends('layouts.app')
@section('title', $cashier->name)
@section('header', 'Cashier: ' . $cashier->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="text-center">
            @if($cashier->profile_picture)
                <img src="{{ $cashier->profile_picture }}" class="w-20 h-20 rounded-full mx-auto mb-3 object-cover">
            @else
                <div class="w-20 h-20 rounded-full bg-amber-200 flex items-center justify-center text-3xl font-bold text-amber-700 mx-auto mb-3">{{ substr($cashier->name, 0, 1) }}</div>
            @endif
            <h2 class="text-xl font-bold">{{ $cashier->name }}</h2>
            <p class="text-gray-500 text-sm">{{ $cashier->email }}</p>
            <span class="px-2 py-1 rounded-full text-xs {{ match($cashier->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }} mt-2 inline-block">
                {{ ucfirst($cashier->status) }}
            </span>
        </div>
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Phone:</span><span>{{ $cashier->phone ?? 'N/A' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Registered:</span><span>{{ \Carbon\Carbon::parse($cashier->created_at)->format('M d, Y') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Today's Sales:</span><span class="font-bold text-green-700">TZS {{ number_format($todaySales) }}</span></div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-4">Recent Sales</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b"><tr><th class="text-left py-2">Sale #</th><th class="text-right">Total</th><th class="text-right">Items</th><th class="text-right">Date</th></tr></thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr class="border-b"><td class="py-2">{{ $sale->sale_number }}</td><td class="text-right">TZS {{ number_format($sale->total) }}</td><td class="text-right">{{ $sale->items->sum('quantity') }}</td><td class="text-right text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<a href="{{ route('branch-admin.cashiers.index') }}" class="mt-4 inline-block text-amber-700 hover:underline">&larr; Back to Cashiers</a>
@endsection
