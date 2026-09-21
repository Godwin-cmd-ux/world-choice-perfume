@extends('stock-manager.layouts.app')
@section('title', 'Pending Incoming Stock')
@section('header', 'Pending Incoming Stock')
@section('header-subtitle', 'Sent to ' . $branchName . ' — verify before it is added to stock')

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-list mr-1"></i> All Transfers
    </a>
@endsection

@section('content')
@php
    $byTransfer = $rows->groupBy('transfer_number');
@endphp

@forelse($byTransfer as $transferNumber => $items)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gradient-to-r from-gray-50 to-white flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i class="fas fa-truck text-amber-600"></i>
                </span>
                <div>
                    <p class="font-mono text-xs font-semibold text-emerald-700">{{ $transferNumber }}</p>
                    <p class="text-sm text-gray-500">
                        <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">{{ $items->first()->stock_type_label }}</span>
                    </p>
                    @if($items->first()->officer_name)
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-user-tie mr-1 text-emerald-600"></i>{{ $items->first()->officer_name }}
                            @if($items->first()->officer_phone)<span class="text-gray-400">· {{ $items->first()->officer_phone }}</span>@endif
                            @if($items->first()->officer_id)<span class="text-gray-400">· ID {{ $items->first()->officer_id }}</span>@endif
                        </p>
                    @endif
                    @if($items->first()->note)
                        <p class="text-xs text-gray-400 italic mt-0.5">{{ $items->first()->note }}</p>
                    @endif
                </div>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($items->first()->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</p>
                <p class="text-xs text-amber-600 font-medium">{{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }} pending</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-6">Item</th>
                        <th class="text-left px-4">Variety</th>
                        <th class="text-left px-4">Oil / Fragrance</th>
                        <th class="text-center px-4">Quantity Sent</th>
                        <th class="text-left px-4">Sending Branch</th>
                        <th class="text-right px-6">Verify</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-6 font-medium text-gray-800">{{ $item->item_name }}</td>
                            <td class="px-4 text-gray-600">
                                @if($item->variety_label)
                                    {{ $item->variety_label }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4">
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">{{ $item->oil_type }}</span>
                            </td>
                            <td class="px-4 text-center text-gray-600 font-medium">{{ $item->item->quantity }}</td>
                            <td class="px-4 text-gray-600">{{ $item->from_branch_name }}</td>
                            <td class="px-6 text-right whitespace-nowrap">
                                @if(!($inCrossBranch ?? false))
                                    <form method="POST" action="{{ route('stock-manager.stock-transfers.receive-item', $item->item->id) }}" class="inline"
                                          data-confirm="Verify {{ $item->item->quantity }} x '{{ $item->item_name }}' into {{ $branchName }} stock?">
                                        @csrf
                                        <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                            <i class="fas fa-check mr-1"></i> Verify
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-300 text-xs">read-only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
        <i class="fas fa-inbox text-4xl mb-3 block"></i>
        <p class="text-gray-500">No pending incoming stock at {{ $branchName }}.</p>
        <p class="text-xs text-gray-400 mt-1">Transfers confirmed by other branches will appear here until you verify them.</p>
    </div>
@endforelse
@endsection