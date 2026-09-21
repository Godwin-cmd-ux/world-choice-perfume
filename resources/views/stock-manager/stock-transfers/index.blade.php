@extends('stock-manager.layouts.app')
@section('title', 'Stock Transfers')
@section('header', 'Stock Transfers')
@section('header-subtitle', $branchName)

@section('header-actions')
    @if(!($inCrossBranch ?? false))
        <div class="relative">
            <select onchange="if(this.value) window.location.href = this.value;"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">New Transfer…</option>
                <option value="{{ route('stock-manager.stock-transfers.create', ['type' => 'product']) }}">Product Stock</option>
                <option value="{{ route('stock-manager.stock-transfers.create', ['type' => 'bottle']) }}">Bottle Stock</option>
                <option value="{{ route('stock-manager.stock-transfers.create', ['type' => 'oil_fragrance']) }}">Oil Fragrance</option>
                <option value="{{ route('stock-manager.stock-transfers.create', ['type' => 'bottle_accessories']) }}">Bottle Accessories</option>
            </select>
        </div>
    @endif
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
            <i class="fas fa-arrow-right-arrow-left text-emerald-600"></i>
        </span>
        <div>
            <p class="text-sm font-semibold text-gray-800">{{ $branchName }}</p>
            <p class="text-xs text-gray-500">Transfers sent from or received by this branch</p>
        </div>
    </div>
    @if(!($inCrossBranch ?? false))
        <div class="flex items-center gap-2">
            <a href="{{ route('stock-manager.stock-transfers.incoming') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-emerald-700 border border-gray-300 hover:border-emerald-500 px-4 py-2 rounded-lg bg-white">
                <i class="fas fa-inbox"></i> Pending Incoming Stock
                @if($transfers->contains(fn ($t) => $t->status === 'in_transit' && $t->to_branch_id === $activeBranchId && $t->items_pending > 0))
                    <span class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $transfers->filter(fn ($t) => $t->status === 'in_transit' && $t->to_branch_id === $activeBranchId)->sum('items_pending') }}</span>
                @endif
            </a>
        </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b">
        <span class="text-sm text-gray-500">Recent transfers</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Transfer #</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-left px-4">Direction</th>
                    <th class="text-right px-4">Items</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Officer</th>
                    <th class="text-left px-4">Created</th>
                    <th class="text-center px-4">View</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-xs font-semibold text-emerald-700">{{ $transfer->transfer_number }}</td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ $transfer->stock_type_label }}</span>
                        </td>
                        <td class="px-4 text-gray-600">
                            <span title="Sent from">{{ $transfer->from_branch_name }}</span>
                            <i class="fas fa-long-arrow-alt-right text-xs mx-1.5 text-gray-400"></i>
                            <span title="Sent to" class="{{ $transfer->to_branch_id === $activeBranchId ? 'font-semibold text-emerald-700' : '' }}">{{ $transfer->to_branch_name }}</span>
                        </td>
                        <td class="px-4 text-right text-gray-600">
                            @if($transfer->status === 'received')
                                {{ $transfer->items_total }}
                            @elseif($transfer->items_pending > 0 && $transfer->items_pending < $transfer->items_total)
                                <span class="text-emerald-700 font-medium">{{ $transfer->items_total - $transfer->items_pending }}</span>/{{ $transfer->items_total }}
                            @else
                                {{ $transfer->items_total }}
                            @endif
                        </td>
                        <td class="px-4">
                            @if($transfer->status === 'received')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800"><i class="fas fa-check mr-1"></i>Received</span>
                            @elseif($transfer->items_pending > 0 && $transfer->items_pending < $transfer->items_total)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">Partial</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800"><i class="fas fa-truck mr-1"></i>In Transit</span>
                            @endif
                        </td>
                        <td class="px-4">
                            @if($transfer->officer_name)
                                <span class="text-gray-700">{{ $transfer->officer_name }}</span>
                                @if($transfer->officer_phone)
                                    <span class="block text-xs text-gray-400">{{ $transfer->officer_phone }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($transfer->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</td>
                        <td class="px-4 text-center">
                            <a href="{{ route('stock-manager.stock-transfers.show', $transfer->id) }}" class="text-emerald-600 hover:text-emerald-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-400">
                            <i class="fas fa-arrow-right-arrow-left text-3xl mb-2 block"></i>
                            No stock transfers yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection