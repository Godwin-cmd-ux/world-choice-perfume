@extends('stock-manager.layouts.app')
@section('title', 'Pending Incoming Stock')
@section('header', 'Pending Incoming Stock')
@section('header-subtitle', 'Sent to ' . $branchName . ' — verify each item before it joins your stock')

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.index') }}" class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <i class="fas fa-list mr-1"></i> All Transfers
    </a>
@endsection

@push('styles')
<style>
    details.sm-popover > summary { list-style: none; }
    details.sm-popover > summary::-webkit-details-marker { display: none; }
    details.sm-popover[open] > summary { opacity: 0.85; }
    .sm-item-row { transition: background-color .15s ease, box-shadow .15s ease; }
    .sm-item-row:hover { box-shadow: 0 1px 3px rgba(0,0,0,.06); }
</style>
@endpush

@section('content')
@php
    $byTransfer = $rows->groupBy('transfer_number');
    $totalPendingItems = $rows->count();
@endphp

{{-- Summary strip --}}
<div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <span class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-truck text-amber-600 text-lg"></i>
        </span>
        <div>
            <p class="text-lg font-bold text-gray-800">{{ $totalPendingItems }} item{{ $totalPendingItems === 1 ? '' : 's' }} awaiting your verification</p>
            <p class="text-sm text-gray-500">from {{ $byTransfer->count() }} transfer{{ $byTransfer->count() === 1 ? '' : 's' }} into {{ $branchName }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
        <i class="fas fa-circle-info text-gray-400"></i>
        <span><strong class="text-gray-700">Valid</strong> adds stock · <strong class="text-red-600">Invalid</strong> returns it to the sender</span>
    </div>
</div>

@forelse($byTransfer as $transferNumber => $items)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        {{-- Transfer header --}}
        <div class="px-5 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-truck text-emerald-600"></i>
                </span>
                <div class="min-w-0">
                    <p class="font-mono text-sm font-bold text-emerald-700 truncate">{{ $transferNumber }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-0.5">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700">{{ $items->first()->stock_type_label }}</span>
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-university mr-1 text-gray-400"></i>{{ $items->first()->from_branch_name }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="ml-auto flex flex-wrap items-center gap-4">
                @if($items->first()->officer_name)
                    <div class="hidden sm:block text-right">
                        <p class="text-xs font-semibold text-gray-700">
                            <i class="fas fa-user-tie mr-1 text-emerald-600"></i>{{ $items->first()->officer_name }}
                        </p>
                        <p class="text-[11px] text-gray-400">
                            @if($items->first()->officer_phone)<i class="fas fa-phone mr-1"></i>{{ $items->first()->officer_phone }}@endif
                            @if($items->first()->officer_id)<span class="ml-2">ID: {{ $items->first()->officer_id }}</span>@endif
                        </p>
                    </div>
                @endif
                <div class="text-right">
                    <p class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($items->first()->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</p>
                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">{{ $items->count() }} pending</span>
                </div>
            </div>

            @if($items->first()->note)
                <div class="w-full sm:w-auto sm:max-w-md sm:ml-6 -mt-1 sm:mt-0 sm:basis-full">
                    <p class="text-xs text-gray-500 italic bg-amber-50/70 border border-amber-100 rounded-lg px-3 py-2">
                        <i class="fas fa-sticky-note text-amber-400 mr-1"></i>{{ $items->first()->note }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Item rows --}}
        <div class="p-4 space-y-3">
            @foreach($items as $item)
                <div class="sm-item-row bg-gray-50/60 hover:bg-gray-50 border border-gray-100 rounded-xl px-4 py-3.5">
                    <div class="flex flex-wrap lg:flex-nowrap items-center gap-4">
                        {{-- Item identity --}}
                        <div class="flex-1 min-w-[200px]">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800">{{ $item->item_name }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $item->oil_type }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-500">
                                @if($item->variety_label)
                                    <span><i class="fas fa-wine-bottle text-gray-400 mr-1"></i>{{ $item->variety_label }}</span>
                                @endif
                                <span><i class="fas fa-arrow-left text-gray-400 mr-1"></i>from {{ $item->from_branch_name }}</span>
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="flex items-center gap-2 lg:flex-col lg:items-end lg:gap-0 lg:px-2">
                            <p class="text-xl font-bold text-gray-800 leading-none">{{ $item->item->quantity }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-gray-400">sent</p>
                        </div>

                        {{-- Actions --}}
                        @if(!($inCrossBranch ?? false))
                            <div class="flex items-center gap-2 w-full sm:w-auto lg:justify-end">
                                <form method="POST" action="{{ route('stock-manager.stock-transfers.receive-item', $item->item->id) }}" class="flex-1 sm:flex-none"
                                      data-confirm="Verify {{ $item->item->quantity }} x '{{ $item->item_name }}' into {{ $branchName }} stock?">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition" style="background-color: #F89A1E;">
                                        <i class="fas fa-check"></i> Valid
                                    </button>
                                </form>

                                <details class="sm-popover relative flex-1 sm:flex-none">
                                    <summary class="w-full sm:w-auto cursor-pointer inline-flex items-center justify-center gap-1.5 bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 px-5 py-2.5 rounded-xl text-sm font-semibold transition">
                                        <i class="fas fa-times"></i> Invalid
                                    </summary>
                                    <div class="mt-2 w-full sm:w-80 bg-white border border-gray-200 rounded-xl shadow-lg p-4 z-20">
                                        <form method="POST" action="{{ route('stock-manager.stock-transfers.receive-item-invalid', $item->item->id) }}">
                                            @csrf
                                            <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Why is this item invalid?</label>
                                            <textarea name="reason" rows="3" required minlength="3" maxlength="500"
                                                      placeholder="e.g. broken bottles, wrong variety, missing items…"
                                                      class="w-full text-sm border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                                            <p class="text-[11px] text-gray-500 mt-1.5 mb-3 flex items-start gap-1">
                                                <i class="fas fa-undo text-gray-400 mt-0.5"></i>
                                                <span>{{ $item->item->quantity }} x will return to {{ $item->from_branch_name }} stock and the admin is notified.</span>
                                            </p>
                                            <button type="submit"
                                                    data-confirm="Reject {{ $item->item->quantity }} x '{{ $item->item_name }}' and return it to {{ $item->from_branch_name }}?"
                                                    class="w-full inline-flex items-center justify-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-3 py-2.5 rounded-lg text-sm font-semibold transition">
                                                <i class="fas fa-undo"></i> Return to sender
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        @else
                            <span class="ml-auto text-gray-300 text-xs italic">read-only</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-14 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center">
            <i class="fas fa-inbox text-2xl text-emerald-300"></i>
        </div>
        <p class="text-lg font-semibold text-gray-700">All caught up, {{ $branchName }}!</p>
        <p class="text-sm text-gray-400 mt-1">No pending incoming stock right now.</p>
        <p class="text-xs text-gray-400 mt-0.5">Transfers confirmed by other branches will appear here until you verify them.</p>
    </div>
@endforelse
@endsection
