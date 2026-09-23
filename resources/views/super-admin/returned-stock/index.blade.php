@extends('layouts.app')
@section('title', 'Returned Stock')
@section('header', 'Returned Stock')
@section('subtitle', 'Rejected transfer stock and mandatory lost / broken reports from the Kinondoni branch stock manager')

@section('header-actions')
    <a href="{{ route('super-admin.returned-stock.report') }}" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-print mr-1"></i> Print Report
    </a>
@endsection

@section('content')
@if(!($hasDamageColumns ?? true))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm mb-6">
        <i class="fas fa-database mr-1"></i>
        <strong>Setup needed:</strong> run <code class="bg-amber-100 px-1 rounded">database/supabase_returned_stock.sql</code>
        in the Supabase SQL editor to see the lost / broken reports.
    </div>
@endif

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $rows->count() }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Rejected items</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-red-600">{{ $reportedRows->count() }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Lost / broken reports</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-slate-600">{{ $lostCount }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Lost</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 text-center">
        <p class="text-2xl font-bold text-orange-600">{{ $brokenCount }}</p>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Broken</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches (sender)</option>
            @foreach($branches as $bid => $bname)
                <option value="{{ $bid }}" {{ ($filters['branch_id'] ?? 0) === (int) $bid ? 'selected' : '' }}>{{ $bname }}</option>
            @endforeach
        </select>
        <select name="damage_type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All reports</option>
            <option value="lost" {{ ($filters['damage_type'] ?? '') === 'lost' ? 'selected' : '' }}>Lost only</option>
            <option value="broken" {{ ($filters['damage_type'] ?? '') === 'broken' ? 'selected' : '' }}>Broken only</option>
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('super-admin.returned-stock.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Reset</a>
    </form>
</div>

{{-- Rejected stock table --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <span class="text-sm font-semibold text-gray-700"><i class="fas fa-undo text-gray-400 mr-1"></i> All rejected stock</span>
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{{ $rows->count() }} items</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Transfer #</th>
                    <th class="text-left px-4">Item</th>
                    <th class="text-right px-4">Qty</th>
                    <th class="text-left px-4">Rejected by</th>
                    <th class="text-left px-4">Rejection reason</th>
                    <th class="text-left px-4">Damage report</th>
                    <th class="text-left px-4">Transfer officer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php $reported = ($row->return_status ?? '') === 'reported' || ! empty($row->damage_reported_at); @endphp
                    <tr class="border-t hover:bg-gray-50 {{ $reported ? 'bg-emerald-50/40' : '' }}">
                        <td class="py-3 px-4 font-mono text-xs font-semibold text-emerald-700">{{ $row->transfer_number }}</td>
                        <td class="px-4">
                            <p class="font-medium text-gray-800">{{ $row->item_label }}</p>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600">{{ $row->stock_type_label }}</span>
                        </td>
                        <td class="px-4 text-right text-gray-700">{{ $row->item->quantity }}</td>
                        <td class="px-4 text-gray-600">
                            {{ $row->to_branch_name }}
                            <span class="block text-[11px] text-gray-400">from {{ $row->from_branch_name }}</span>
                        </td>
                        <td class="px-4 max-w-[220px]">
                            <span class="block truncate text-gray-600" title="{{ $row->return_reason }}">{{ $row->return_reason ?? '—' }}</span>
                            <span class="block text-[11px] text-gray-400">{{ $row->returned_at ? \Carbon\Carbon::parse($row->returned_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '' }}</span>
                        </td>
                        <td class="px-4">
                            @if($reported)
                                @php $badge = ($row->damage_type ?? '') === 'lost' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($row->damage_type ?? 'reported') }}</span>
                                <span class="block text-[11px] text-gray-600 mt-0.5 max-w-[220px]" title="{{ $row->damage_reason }}">{{ $row->damage_reason }}</span>
                                <span class="block text-[11px] text-gray-400">by {{ $row->damage_reported_by_name ?? '—' }} · {{ $row->damage_reported_at ? \Carbon\Carbon::parse($row->damage_reported_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '' }}</span>
                            @elseif(($row->return_status ?? '') === 'resent')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">Re-sent</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Report pending</span>
                            @endif
                        </td>
                        <td class="px-4 text-gray-600">
                            @if($row->officer_name)
                                {{ $row->officer_name }}
                                <span class="block text-[11px] text-gray-400">{{ $row->officer_phone }}{{ $row->officer_id ? ' · ID '.$row->officer_id : '' }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <i class="fas fa-undo text-3xl mb-2 block"></i>
                            No rejected stock found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
