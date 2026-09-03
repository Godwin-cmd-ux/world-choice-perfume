@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance Stock')
@section('header', 'Oil Fragrance Stock')

@section('header-actions')
    <a href="{{ route('stock-manager.oil-fragrance-stock-in') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-plus mr-1"></i> Stock In
    </a>
    <a href="{{ route('stock-manager.oil-fragrance-stock-out') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-arrow-right mr-1"></i> Stock Out
    </a>
    <a href="{{ route('stock-manager.oil-fragrance-movements') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-history mr-1"></i> History
    </a>
@endsection

@section('content')
{{-- Summary --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex justify-between items-center">
        <span class="text-sm text-gray-500">Total Oil Fragrance Bottles:</span>
        <span class="text-lg font-bold text-purple-700">{{ number_format($totalQuantity) }}</span>
    </div>
</div>

{{-- Oil Fragrance List --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Fragrance Name</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-left px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($oils as $oil)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $oil['name'] }}</td>
                        <td class="px-4 text-right font-bold text-lg">{{ number_format($oil['quantity'] ?? 0) }}</td>
                        <td class="px-4">
                            <div class="flex gap-2">
                                <a href="{{ route('stock-manager.oil-fragrance-stock-in') }}" class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full hover:bg-green-200">+ Add</a>
                                <a href="{{ route('stock-manager.oil-fragrance-stock-out') }}" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200">- Remove</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-8 text-center text-gray-400">No oil fragrance stock recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <a href="{{ route('stock-manager.oil-fragrance-stock-in') }}" class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 transition">
        <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
            <i class="fas fa-plus text-white"></i>
        </div>
        <div>
            <p class="font-medium text-gray-800">Stock In</p>
            <p class="text-xs text-gray-500">Import oil fragrance bottles</p>
        </div>
    </a>
    <a href="{{ route('stock-manager.oil-fragrance-stock-out') }}" class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition">
        <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
            <i class="fas fa-arrow-right text-white"></i>
        </div>
        <div>
            <p class="font-medium text-gray-800">Stock Out</p>
            <p class="text-xs text-gray-500">Use for perfume production</p>
        </div>
    </a>
</div>
@endsection
