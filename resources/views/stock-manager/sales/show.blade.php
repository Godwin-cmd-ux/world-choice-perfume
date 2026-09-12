@extends('stock-manager.layouts.app')
@section('title', 'Sale Details')
@section('header', 'Sale Details')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">{{ $sale->sale_number }}</h3>
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y \a\t h:i A') }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ ($sale->payment_status ?? '') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($sale->payment_status ?? 'pending') }}
                </span>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-6">Product</th>
                        <th class="text-right px-6">Qty</th>
                        <th class="text-right px-6">Price</th>
                        <th class="text-right px-6">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr class="border-t">
                            <td class="py-3 px-6">
                                <p class="font-medium">{{ $item->product?->name ?? 'Unknown' }}</p>
                                @if(!empty($item->product?->brand))
                                    <p class="text-xs text-gray-500">{{ $item->product->brand }}</p>
                                @endif
                            </td>
                            <td class="px-6 text-right">{{ $item->quantity }}</td>
                            <td class="px-6 text-right">TZS {{ number_format($item->unit_price) }}</td>
                            <td class="px-6 text-right font-medium">TZS {{ number_format($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2">
                    <tr>
                        <td colspan="3" class="py-3 px-6 font-semibold text-right">Total:</td>
                        <td class="py-3 px-6 text-right font-bold text-lg">TZS {{ number_format($sale->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3">Payment Info</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Method:</span><span>{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—')) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Summary:</span><span class="text-xs">{{ $sale->payment_summary ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Type:</span><span class="capitalize">{{ $sale->sale_type ?? 'retail' }}</span></div>
            </div>
        </div>

        @if($sale->customer)
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold mb-3">Customer</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Name:</span><span>{{ $sale->customer->name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Phone:</span><span>{{ $sale->customer->phone ?? '—' }}</span></div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('stock-manager.sales.index') }}" class="text-emerald-600 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Sales</a>
</div>
@endsection
