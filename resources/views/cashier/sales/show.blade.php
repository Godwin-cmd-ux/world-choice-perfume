@extends('layouts.app')
@section('title', 'Sale ' . $sale->sale_number)
@section('header', 'Sale Receipt')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow p-6" id="receipt">
        <div class="text-center mb-6">
            <img src="{{ asset('our_logo.jpeg') }}" class="w-16 h-16 rounded-full mx-auto mb-2 object-cover">
            <h2 class="text-xl font-bold">World Choice Perfumes</h2>
            <p class="text-sm text-gray-500">{{ $sale->branch?->name }}</p>
        </div>
        <div class="border-t border-b py-3 mb-4 text-sm">
            <div class="flex justify-between"><span>Sale #:</span><strong>{{ $sale->sale_number }}</strong></div>
            <div class="flex justify-between"><span>Type:</span><span class="px-2 py-0.5 rounded-full text-xs @if(($sale->sale_type ?? '') === 'wholesale') bg-blue-100 text-blue-800 @else bg-amber-100 text-amber-800 @endif">{{ ucfirst($sale->sale_type ?? 'retail') }}</span></div>
            <div class="flex justify-between"><span>Date:</span><span>{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y H:i') }}</span></div>
            <div class="flex justify-between"><span>Cashier:</span><span>{{ $sale->cashier->name }}</span></div>
            @if(!empty($sale->supplier))
                <div class="flex justify-between"><span>Supplier:</span><span>{{ $sale->supplier }}</span></div>
            @endif
            <div class="flex justify-between"><span>Payment:</span><span class="text-sm">{{ $sale->payment_summary ?? str_replace('_', ' ', ucfirst($sale->payment_method ?? 'N/A')) }}</span></div>
            @if($sale->customer)
                <div class="flex justify-between"><span>Customer:</span><span>{{ $sale->customer->name }}</span></div>
                <div class="flex justify-between"><span>Phone:</span><span>{{ $sale->customer->phone }}</span></div>
            @endif
        </div>
        <table class="w-full text-sm mb-4">
            <thead><tr class="border-b"><th class="text-left py-1">Item</th><th class="text-center">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr class="border-b">
                        <td class="py-2">{{ $item->product->name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">TZS {{ number_format($item->unit_price) }}</td>
                        <td class="text-right font-medium">TZS {{ number_format($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-right text-lg font-bold text-amber-700">
            Total: TZS {{ number_format($sale->total) }}
        </div>
        <div class="text-center mt-6 text-sm text-gray-400">Thank you for your purchase!</div>
    </div>
    <div class="mt-4 text-center">
        <a href="{{ route('cashier.sales.index') }}" class="text-amber-700 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Back to Sales</a>
    </div>
</div>
@endsection
