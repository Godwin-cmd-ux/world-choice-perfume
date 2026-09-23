@extends('stock-manager.layouts.app')
@section('title', 'Returned Stock')
@section('header', 'Returned Stock')
@section('header-subtitle', 'Stock rejected by receiving branches — every item needs a lost / broken report before it is cleared')

@section('header-actions')
    <a href="{{ route('stock-manager.stock-transfers.incoming') }}" class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <i class="fas fa-inbox mr-1"></i> Incoming Stock
    </a>
@endsection

@section('content')
@if(!($hasDamageColumns ?? true))
    <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
        <i class="fas fa-database mr-1"></i>
        <strong>Setup needed:</strong> run <code class="bg-amber-100 px-1 rounded">database/supabase_returned_stock.sql</code>
        in the Supabase SQL editor to enable lost / broken reports.
    </div>
@endif

@php
    $pendingReports = $rows->filter(fn ($r) => ($r->return_status ?? 'pending') !== 'reported' && empty($r->damage_reported_at));
    $reportedCount = $rows->count() - $pendingReports->count();
@endphp

{{-- Summary strip --}}
<div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <span class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-undo text-red-600 text-lg"></i>
        </span>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $rows->count() }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Rejected items</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <span class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-file-signature text-amber-600 text-lg"></i>
        </span>
        <div>
            <p class="text-2xl font-bold text-amber-700">{{ $pendingReports->count() }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Reports required</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <span class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
        </span>
        <div>
            <p class="text-2xl font-bold text-emerald-700">{{ $reportedCount }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Reported to Super Admin</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Transfer #</th>
                    <th class="text-left px-4">Item</th>
                    <th class="text-right px-4">Qty</th>
                    <th class="text-left px-4">Rejection</th>
                    <th class="text-left px-4">Sent to</th>
                    <th class="text-left px-4">Damage report</th>
                    <th class="text-center px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $reported = ($row->return_status ?? 'pending') === 'reported' || ! empty($row->damage_reported_at);
                        $resent = ($row->return_status ?? '') === 'resent';
                    @endphp
                    <tr class="border-t hover:bg-gray-50 {{ $reported ? 'bg-emerald-50/40' : '' }}">
                        <td class="py-3 px-4 font-mono text-xs font-semibold text-emerald-700">{{ $row->transfer_number }}</td>
                        <td class="px-4">
                            <p class="font-medium text-gray-800">{{ $row->item_label }}</p>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600">{{ $row->stock_type_label }}</span>
                        </td>
                        <td class="px-4 text-right text-gray-700">{{ $row->item->quantity }}</td>
                        <td class="px-4 max-w-[220px]">
                            <span class="block truncate text-gray-600" title="{{ $row->return_reason }}">{{ $row->return_reason ?? '—' }}</span>
                            <span class="block text-[11px] text-gray-400">{{ $row->returned_at ? \Carbon\Carbon::parse($row->returned_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '' }}</span>
                        </td>
                        <td class="px-4 text-gray-600">
                            {{ $row->to_branch_name }}
                            <span class="block text-[11px] text-gray-400">from {{ $row->from_branch_name }}</span>
                        </td>
                        <td class="px-4">
                            @if($reported)
                                @php $damageBadge = ($row->damage_type ?? '') === 'lost' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $damageBadge }}">{{ ucfirst($row->damage_type ?? 'reported') }}</span>
                                <span class="block text-[11px] text-gray-500 mt-0.5 max-w-[200px] truncate" title="{{ $row->damage_reason }}">{{ $row->damage_reason }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $row->damage_reported_by_name }} · {{ $row->damage_reported_at ? \Carbon\Carbon::parse($row->damage_reported_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '' }}</span>
                            @elseif($resent)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">Re-sent</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800 font-medium">Report required</span>
                            @endif
                        </td>
                        <td class="px-4 text-center whitespace-nowrap">
                            @if(!($inCrossBranch ?? false) && !$reported && !$resent)
                                <a href="{{ route('stock-manager.returned-stock.damage-report', $row->item->id) }}"
                                   class="inline-flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                                    <i class="fas fa-file-signature"></i> File report
                                </a>
                            @elseif($resent)
                                <span class="text-xs text-gray-400">—</span>
                            @else
                                <span class="text-xs text-emerald-600"><i class="fas fa-check mr-1"></i>Filed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-14 text-center">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                <i class="fas fa-undo text-2xl text-emerald-300"></i>
                            </div>
                            <p class="text-lg font-semibold text-gray-700">No returned stock</p>
                            <p class="text-sm text-gray-400 mt-1">Items rejected by receiving branches will appear here with their reasons.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 text-xs text-gray-400 flex items-center gap-1">
    <i class="fas fa-shield-halved"></i>
    Every filed report is sent to the Super Admin with the transfer officer attached for physical follow-up.
</div>
@endsection
