@extends('layouts.app')
@section('title', 'Seller Dashboard')
@section('header', 'Seller Dashboard')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill text-green-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($todayTotal) }}</p><p class="text-sm text-gray-500">Today's Sales</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-receipt text-blue-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">{{ $totalTransactions }}</p><p class="text-sm text-gray-500">Total Transactions</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-chart-line text-amber-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($totalSales) }}</p><p class="text-sm text-gray-500">Total Revenue</p></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="font-semibold">Recent Sales</h3>
        <a href="{{ route('seller.sales.index') }}" class="text-amber-700 hover:underline text-sm">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Sale #</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-right px-4">Total</th>
                    <th class="text-left px-4">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse(collect($mySales)->take(10) as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $sale['sale_number'] ?? '—' }}</td>
                        <td class="px-4"><span class="px-2 py-0.5 rounded-full text-xs {{ ($sale['sale_type'] ?? '') === 'wholesale' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">{{ ucfirst($sale['sale_type'] ?? 'retail') }}</span></td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale['total'] ?? 0) }}</td>
                        <td class="px-4 text-gray-500 text-xs">{{ $sale['created_at'] ? \Carbon\Carbon::parse($sale['created_at'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-400">No sales yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
