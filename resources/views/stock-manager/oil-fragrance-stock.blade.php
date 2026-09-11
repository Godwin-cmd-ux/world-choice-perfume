@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance Stock')
@section('header', 'Oil Fragrance Stock')

@section('header-actions')
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.oil-fragrance-stock-in') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-plus mr-1"></i> Stock In
        </a>
        <a href="{{ route('stock-manager.oil-fragrance-stock-out') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-arrow-right mr-1"></i> Stock Out
        </a>
    @endif
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

{{-- Search --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ route('stock-manager.oil-fragrance') }}" class="flex gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search fragrance name..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">
            Search
        </button>
        @if(request('search'))
            <a href="{{ route('stock-manager.oil-fragrance') }}" class="text-sm text-purple-600 hover:text-purple-800 font-medium">Clear</a>
        @endif
    </form>
</div>

{{-- Records Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Oil Fragrance Records</h3>
        <span class="text-xs text-gray-400">{{ $oils->count() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fragrance Name</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Quantity</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Volume</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($oils as $oil)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $oil->name }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold text-lg text-purple-700">{{ number_format($oil->quantity ?? 0) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600">{{ $oil->volume === 500 ? '500ml' : ($oil->volume === 1000 ? '1000ml' : ($oil->volume ?? '-')) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(!($inCrossBranch ?? false))
                                <div class="flex items-center gap-2">
                                    {{-- Edit --}}
                                    <form method="POST" action="{{ route('stock-manager.oil-fragrance.update', $oil->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-center gap-1.5">
                                            <input type="number" name="quantity" value="{{ $oil->quantity }}" min="0"
                                                class="w-20 px-2 py-1 border border-gray-300 rounded text-xs text-right focus:ring-2 focus:ring-purple-500"
                                                placeholder="Qty">
                                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-2.5 py-1 rounded text-xs font-medium">
                                                <i class="fas fa-pen mr-0.5"></i> Edit
                                            </button>
                                        </div>
                                    </form>
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('stock-manager.oil-fragrance.destroy', $oil->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs"
                                            data-confirm="Delete oil fragrance '{{ $oil->name }}'? This will remove the stock record.">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-inbox text-2xl mb-2 block"></i>
                            No oil fragrance stock records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
