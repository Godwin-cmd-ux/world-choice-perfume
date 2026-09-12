@extends('stock-manager.layouts.app')
@section('title', 'Bottle Accessories')
@section('header', 'Bottle Accessories')

@section('header-actions')
    @if(!($inCrossBranch ?? false))
        <a href="{{ route('stock-manager.bottle-accessories.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-plus mr-1"></i> Stock In
        </a>
        <a href="{{ route('stock-manager.bottle-accessories.stock-out') }}" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
            <i class="fas fa-minus mr-1"></i> Stock Out
        </a>
    @endif
    <a href="{{ route('stock-manager.bottle-accessories.movements') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-exchange-alt mr-1"></i> Movements
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex justify-between items-center">
        <span class="text-sm text-gray-500">Total Accessories:</span>
        <span class="text-lg font-bold text-emerald-700">{{ number_format($totalPackets) }} packets</span>
    </div>
</div>

@foreach(['straws' => 'Straws', 'bottlenecks' => 'Bottle Necks', 'bottle_tops' => 'Bottle Tops'] as $key => $label)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-cubes mr-2 text-emerald-600"></i>{{ $label }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-6">Color</th>
                        <th class="text-right px-6">Packets</th>
                        <th class="text-right px-6">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['silver' => 'Silver', 'gold' => 'Gold'] as $colorKey => $colorLabel)
                        @php
                            $item = collect($grouped[$key] ?? [])->first(fn($a) => $a->color === $colorKey);
                        @endphp
                        <tr class="border-t">
                            <td class="py-3 px-6">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full {{ $colorKey === 'gold' ? 'bg-amber-400' : 'bg-gray-300' }}"></span>
                                    <span class="font-medium">{{ $colorLabel }}</span>
                                </div>
                            </td>
                            <td class="px-6 text-right font-bold {{ ($item->quantity ?? 0) <= 5 ? 'text-red-600' : 'text-gray-800' }}">
                                {{ number_format($item->quantity ?? 0) }}
                            </td>
                            <td class="px-6 text-right">
                                @if($item && !($inCrossBranch ?? false))
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('stock-manager.bottle-accessories.edit', $item->id) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-medium inline-flex items-center gap-1">
                                            <i class="fas fa-pen mr-0.5"></i> Edit
                                        </a>
                                        <form method="POST" action="{{ route('stock-manager.bottle-accessories.destroy', $item->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs"
                                                data-confirm="Delete this accessory stock record? The quantity will be lost.">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($item)
                                    <span class="text-xs text-gray-400 italic">Read only</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
