@extends('stock-manager.layouts.app')
@section('title', 'Sales')
@section('header', 'Sales')

@section('header-actions')
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.sales.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-plus mr-1"></i> New Sale
        </a>
    @endif
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <span class="text-sm text-gray-500">Total: TZS {{ number_format($totalSales) }}</span>
        <span class="text-sm text-gray-500">{{ $sales->count() }} sales</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Sale #</th>
                    <th class="text-left px-4">Customer</th>
                    <th class="text-left px-4">Payment</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-right px-4">Total</th>
                    <th class="text-left px-4">Date</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $sale->sale_number }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 text-xs">{{ $sale->payment_summary ?? $sale->payment_method ?? '—' }}</td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ ($sale->sale_type ?? '') === 'wholesale' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ ucfirst($sale->sale_type ?? 'retail') }}
                            </span>
                        </td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') }}</td>
                        <td class="px-4 text-center">
                            <a href="{{ route('stock-manager.sales.show', $sale->id) }}" class="text-emerald-600 hover:text-emerald-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <i class="fas fa-receipt text-3xl mb-2 block"></i>
                            No sales yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
