@extends('layouts.app')
@section('title', 'Rejected Item — '.$row->item_label)
@section('header', 'Rejected Stock Details')
@section('subtitle', $row->transfer_number.' · '.$row->item_label)

@section('header-actions')
    <a href="{{ route('super-admin.returned-stock.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 font-medium">
        <i class="fas fa-arrow-left text-xs"></i> Back to list
    </a>
@endsection

@section('content')
@php
    $reported = ($row->return_status ?? '') === 'reported' || ! empty($row->damage_reported_at);
    $item = $row->item;
    $tz = 'Africa/Dar_es_Salaam';
    $badge = ($row->damage_type ?? '') === 'lost' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800';
@endphp

<div class="max-w-3xl space-y-5">

    @if($markedRead > 0)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
            <i class="fas fa-check-circle"></i>
            Viewed — {{ $markedRead }} notification{{ $markedRead === 1 ? '' : 's' }} marked as read. The Returned Stock count has gone down.
        </div>
    @endif

    {{-- Item header --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-bold text-gray-900">{{ $row->item_label }}</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] bg-gray-100 text-gray-600">{{ $row->stock_type_label }}</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $badge }}">{{ ucfirst($row->damage_type ?? 'rejected') }}</span>
                <span class="ml-auto font-mono text-xs font-semibold text-emerald-700">{{ $row->transfer_number }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                {{ $row->from_branch_name }} → {{ $row->to_branch_name }}
                @if($row->returned_at)
                    · returned {{ \Carbon\Carbon::parse($row->returned_at)->setTimezone($tz)->format('M d, Y H:i') }}
                @endif
            </p>
        </div>

        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Quantity</p>
                <p class="text-gray-900 font-semibold">{{ $item->quantity }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Damage report</p>
                @if($reported)
                    <p class="text-gray-900">{{ ucfirst($row->damage_type ?? 'reported') }}</p>
                @elseif(($row->return_status ?? '') === 'resent')
                    <p class="text-blue-700">Re-sent to the branch</p>
                @else
                    <p class="text-amber-700">Report pending</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Unit cost</p>
                <p class="text-gray-900">{{ $item->unit_cost !== null && $item->unit_cost !== '' ? 'TZS '.number_format((float) $item->unit_cost) : '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Unit price</p>
                <p class="text-gray-900">{{ $item->unit_price !== null && $item->unit_price !== '' ? 'TZS '.number_format((float) $item->unit_price) : '—' }}</p>
            </div>
            @if(! empty($item->category) || ! empty($item->supplier))
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Category</p>
                    <p class="text-gray-900">{{ $item->category ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Supplier</p>
                    <p class="text-gray-900">{{ $item->supplier ?: '—' }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Rejection --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-ban text-gray-400 mr-1"></i> Rejection</h3>
        </div>
        <div class="px-6 py-4 text-sm space-y-3">
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Reason given by the receiving branch</p>
                <p class="text-gray-800 whitespace-pre-line">{{ $row->return_reason ?? 'No reason given' }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Rejected by</p>
                    <p class="text-gray-900">{{ $row->returned_by_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Rejected at</p>
                    <p class="text-gray-900">{{ $row->returned_at ? \Carbon\Carbon::parse($row->returned_at)->setTimezone($tz)->format('M d, Y H:i') : '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Lost / broken report --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-clipboard-list text-gray-400 mr-1"></i> Stock manager's lost / broken report</h3>
        </div>
        @if($reported)
            <div class="px-6 py-4 text-sm space-y-3">
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Reported as</p>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($row->damage_type ?? 'reported') }}</span>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Reason</p>
                    <p class="text-gray-800 whitespace-pre-line">{{ $row->damage_reason ?: '—' }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Reported by</p>
                        <p class="text-gray-900">{{ $row->damage_reported_by_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium mb-1">Reported at</p>
                        <p class="text-gray-900">{{ $row->damage_reported_at ? \Carbon\Carbon::parse($row->damage_reported_at)->setTimezone($tz)->format('M d, Y H:i') : '—' }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="px-6 py-4 text-sm text-amber-700">
                <i class="fas fa-clock mr-1"></i>
                {{ ($row->return_status ?? '') === 'resent' ? 'This item was re-sent to the branch, so no lost / broken report was needed.' : 'The Kinondoni stock manager has not filed the mandatory lost / broken report yet.' }}
            </div>
        @endif
    </div>

    {{-- Transfer officer --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-user-tie text-gray-400 mr-1"></i> Transfer officer</h3>
        </div>
        <div class="px-6 py-4 text-sm">
            @if($row->officer_name)
                <p class="text-gray-900 font-medium">{{ $row->officer_name }}</p>
                <p class="text-xs text-gray-500">{{ $row->officer_phone }}{{ $row->officer_id ? ' · ID '.$row->officer_id : '' }}</p>
            @else
                <p class="text-gray-500 italic">Not recorded on the transfer.</p>
            @endif
        </div>
    </div>
</div>
@endsection
