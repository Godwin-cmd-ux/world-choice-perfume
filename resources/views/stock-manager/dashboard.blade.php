@extends('stock-manager.layouts.app')
@section('title', 'Stock Manager Dashboard')
@section('header', 'Stock Manager Dashboard')

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Product Stock --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Products</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalProductItems) }}</p>
                <p class="text-xs text-gray-400 mt-1">TZS {{ number_format($totalProductValue) }} value</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                <i class="fas fa-box text-blue-500 text-xl"></i>
            </div>
        </div>
        @if($lowStockProducts > 0)
            <div class="mt-3 flex items-center gap-2 text-xs text-red-500">
                <i class="fas fa-exclamation-triangle"></i>
                {{ $lowStockProducts }} product(s) low stock
            </div>
        @endif
    </div>

    {{-- Bottle Stock --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Bottles</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalBottles) }}</p>
                <p class="text-xs text-gray-400 mt-1">Across all volumes</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center">
                <i class="fas fa-wine-bottle text-amber-500 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Oil Fragrance --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Oil Fragrances</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalOilFragrances) }}</p>
                <p class="text-xs text-gray-400 mt-1">Bottles in stock</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                <i class="fas fa-flask text-purple-500 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white">
        <p class="text-sm font-medium text-emerald-100">Quick Actions</p>
        <div class="mt-3 space-y-2">
            <a href="{{ route('stock-manager.product-stock.entry') }}" class="block text-sm text-white hover:text-emerald-100">
                <i class="fas fa-plus mr-2"></i> Add Product Stock
            </a>
            <a href="{{ route('stock-manager.bottle-stock-in') }}" class="block text-sm text-white hover:text-emerald-100">
                <i class="fas fa-plus mr-2"></i> Add Bottle Stock
            </a>
            <a href="{{ route('stock-manager.oil-fragrance-stock-in') }}" class="block text-sm text-white hover:text-emerald-100">
                <i class="fas fa-plus mr-2"></i> Add Oil Fragrance
            </a>
        </div>
    </div>
</div>

{{-- Bottle Stock Breakdown --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
            <i class="fas fa-wine-bottle text-amber-500 mr-2"></i> Bottle Stock by Volume
        </h3>
        <div class="space-y-3">
            @forelse($bottleStock as $bottle)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                            <i class="fas fa-flask text-amber-500"></i>
                        </div>
                        <span class="font-medium text-gray-700">{{ $bottle['volume'] }}</span>
                    </div>
                    <span class="font-bold text-gray-800">{{ number_format($bottle['quantity'] ?? 0) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No bottle stock recorded yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Oil Fragrance Breakdown --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
            <i class="fas fa-flask text-purple-500 mr-2"></i> Oil Fragrance Stock
        </h3>
        <div class="space-y-3">
            @forelse($oilStock as $oil)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                            <i class="fas fa-flask text-purple-500"></i>
                        </div>
                        <span class="font-medium text-gray-700">{{ $oil['name'] }}</span>
                    </div>
                    <span class="font-bold text-gray-800">{{ number_format($oil['quantity'] ?? 0) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No oil fragrance stock recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Movements --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
        <i class="fas fa-history text-gray-400 mr-2"></i> Recent Bottle Movements
    </h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Date</th>
                    <th class="text-left px-4">Volume</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-left px-4">Reason</th>
                    <th class="text-left px-4">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBottleMovements as $m)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 font-medium">{{ $m->volume }}</td>
                        <td class="px-4">
                            @if($m->type === 'stock_in')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Stock In</span>
                            @elseif($m->type === 'stock_out')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">Stock Out</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">Broken</span>
                            @endif
                        </td>
                        <td class="px-4 text-right font-medium">{{ $m->quantity }}</td>
                        <td class="px-4 text-gray-500">{{ $m->reason ?? '-' }}</td>
                        <td class="px-4 text-gray-500">{{ $m->performedBy->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No recent movements</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
