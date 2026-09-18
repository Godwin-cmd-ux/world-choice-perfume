@extends('stock-manager.layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_number)
@section('header', 'Stock Transfer ' . $transfer->transfer_number)
@section('header-subtitle', $stock_type_label)

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gradient-to-r from-gray-50 to-white flex flex-wrap items-center gap-3">
            <span class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i class="fas fa-arrow-right-arrow-left text-emerald-600"></i>
            </span>
            <div>
                <p class="font-mono text-xs font-semibold text-emerald-700">{{ $transfer->transfer_number }}</p>
                <p class="text-sm text-gray-500">{{ $stock_type_label }}</p>
            </div>
            <div class="ml-auto">
                @if($transfer->status === 'received')
                    <span class="px-3 py-1 rounded-full text-xs bg-emerald-100 text-emerald-800 font-medium"><i class="fas fa-check mr-1"></i>Received</span>
                @elseif($pendingCount > 0 && $pendingCount < $items->count())
                    <span class="px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-800 font-medium">Partially received</span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs bg-amber-100 text-amber-800 font-medium"><i class="fas fa-truck mr-1"></i>In Transit</span>
                @endif
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">From</p>
                    <p class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-university text-emerald-600"></i>{{ $from_branch_name }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">To</p>
                    <p class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-university text-emerald-600"></i>{{ $to_branch_name }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Officer</p>
                    @if($transfer->officer_name)
                        <p class="text-gray-800">{{ $transfer->officer_name }}</p>
                        <p class="text-xs text-gray-500">{{ $transfer->officer_phone }}{{ $transfer->officer_id ? ' · ID ' . $transfer->officer_id : '' }}</p>
                    @else
                        <p class="text-gray-400">—</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Created</p>
                    <p class="text-gray-800">{{ \Carbon\Carbon::parse($transfer->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y · H:i') }}</p>
                    <p class="text-xs text-gray-500">by {{ $created_by_name ?? 'Unknown' }}</p>
                </div>
            </div>

            @if($transfer->note)
                <div class="mt-5 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-amber-700 mb-1">Note</p>
                    <p class="text-sm text-gray-700">{{ $transfer->note }}</p>
                </div>
            @endif

            @if($transfer->status === 'received')
                <div class="mt-5 p-4 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <p class="text-sm text-emerald-800">
                        Fully received {{ $transfer->received_at ? 'on ' . \Carbon\Carbon::parse($transfer->received_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y · H:i') : '' }}
                        @if($received_by_name) by <strong>{{ $received_by_name }}</strong>@endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-clipboard-check mr-2 text-emerald-600"></i>Progress</h3>
        <div class="flex justify-between items-end mb-2">
            <span class="text-sm text-gray-500">{{ $items->count() - $pendingCount }} of {{ $items->count() }} items received</span>
            <span class="text-2xl font-bold text-emerald-700">{{ $pendingCount === 0 ? 100 : round((($items->count() - $pendingCount) / $items->count()) * 100) }}%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 transition-all"
                 style="width: {{ $items->count() === 0 ? 0 : (($items->count() - $pendingCount) / $items->count()) * 100 }}%"></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <span class="text-sm text-gray-500">Items ({{ $items->count() }})</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-6">#</th>
                    <th class="text-left px-6">Item</th>
                    <th class="text-right px-6">Quantity</th>
                    <th class="text-center px-6">Status</th>
                    <th class="text-left px-6">Received by</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-6 text-gray-400">{{ $item->item->item_index ?? $loop->iteration }}</td>
                        <td class="px-6 font-medium text-gray-800">{{ $item->item_label }}</td>
                        <td class="px-6 text-right text-gray-600">{{ $item->item->quantity }}</td>
                        <td class="px-6 text-center">
                            @if($item->item->status === 'received')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800"><i class="fas fa-check mr-1"></i>Received</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">In Transit</span>
                            @endif
                        </td>
                        <td class="px-6 text-gray-500">
                            @if($item->item->status === 'received')
                                {{ $item->received_by_name ?? 'Unknown' }}
                                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($item->received_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-400">No items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection