@extends('stock-manager.layouts.app')
@section('title', 'Bottle Stock')
@section('header', 'Bottle Stock')

@section('header-actions')
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.bottle-stock-in') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-plus mr-1"></i> Stock In
        </a>
        <a href="{{ route('stock-manager.bottle-broken') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-broken-image mr-1"></i> Broken
        </a>
    @endif
    <a href="{{ route('stock-manager.bottle-movements') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-history mr-1"></i> History
    </a>
@endsection

@section('content')
{{-- Summary --}}
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
    @foreach($volumes as $volume)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-wine-bottle text-amber-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($bottleMap[$volume] ?? 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $volume }} bottles</p>
        </div>
    @endforeach
</div>

{{-- Search --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ route('stock-manager.bottle-stock') }}" class="flex gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by volume (e.g. 50ml)"
                class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
        </div>
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">
            Search
        </button>
        @if(request('search'))
            <a href="{{ route('stock-manager.bottle-stock') }}" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">Clear</a>
        @endif
    </form>
</div>

{{-- Records Table --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Bottle Stock Records</h3>
        <span class="text-xs text-gray-400">{{ $bottleRecords->count() }} record(s)</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Volume</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Variant</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Logo</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Box</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bottleRecords as $record)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-amber-400 border border-amber-500"></span>
                                <span class="font-medium text-gray-800">{{ $record->volume }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-medium">
                                {{ $variantLabelMap[$record->id] ?? \App\Services\BottleStockService::VARIANT_PLAIN }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($record->has_logo ?? '') === 'yes')
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                                        <i class="fas fa-tag"></i> Logo
                                    </span>
                                    @php $logoColor = ($record->logo_color ?? ''); @endphp
                                    @if($logoColor)
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                            <span class="w-3 h-3 rounded-full border {{ $logoColor === 'yellow' ? 'bg-yellow-400 border-yellow-500' : 'bg-gray-800 border-gray-600' }}"></span>
                                            {{ ucfirst($logoColor) }}
                                        </span>
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-gray-400">No Logo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if(($record->has_box ?? '') === 'yes')
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium">
                                        <i class="fas fa-box"></i> Box
                                    </span>
                                    @php $boxColor = ($record->box_color ?? ''); @endphp
                                    @if($boxColor)
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                            <span class="w-3 h-3 rounded-full border {{ $boxColor === 'black' ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-300' }}"></span>
                                            {{ ucfirst($boxColor) }}
                                        </span>
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-gray-400">No Box</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700 font-medium">{{ number_format($record->quantity ?? 0) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(!($inCrossBranch ?? false))
                                <div class="flex items-center gap-2">
                                    {{-- Edit --}}
                                    <a href="{{ route('stock-manager.bottle-stock.edit', $record->id) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-medium inline-flex items-center gap-1">
                                        <i class="fas fa-pen mr-0.5"></i> Edit
                                    </a>
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('stock-manager.bottle-stock.destroy', $record->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs"
                                            data-confirm="Delete this bottle stock record? The quantity will be lost.">
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
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-inbox text-2xl mb-2 block"></i>
                            No bottle stock records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
