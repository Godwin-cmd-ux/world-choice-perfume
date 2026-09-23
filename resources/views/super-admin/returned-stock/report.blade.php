@extends('layouts.app')
@section('title', 'Lost / Broken Stock Report')
@section('header', 'Lost / Broken Stock Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition no-print">
        <i class="fas fa-print mr-1"></i> Print
    </button>
    <a href="{{ route('super-admin.returned-stock.index') }}" class="text-gray-500 hover:text-gray-700 text-sm no-print">Back</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches (sender)</option>
            @foreach($branches as $bid => $bname)
                <option value="{{ $bid }}" {{ ($filters['branch_id'] ?? 0) === (int) $bid ? 'selected' : '' }}>{{ $bname }}</option>
            @endforeach
        </select>
        <select name="damage_type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">Lost + Broken</option>
            <option value="lost" {{ ($filters['damage_type'] ?? '') === 'lost' ? 'selected' : '' }}>Lost only</option>
            <option value="broken" {{ ($filters['damage_type'] ?? '') === 'broken' ? 'selected' : '' }}>Broken only</option>
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Apply</button>
    </form>
</div>

{{-- Report header (visible always) --}}
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <div class="flex items-center gap-4 border-b border-gray-200 pb-4 mb-4">
        <img src="{{ asset('our_logo.jpeg') }}" alt="World Choice Perfume" class="w-14 h-14 object-contain">
        <div>
            <h2 class="text-lg font-bold text-gray-800">World Choice Perfume — Lost / Broken Stock Report</h2>
            <p class="text-xs text-gray-500">
                Filed by the Kinondoni branch stock manager · Generated
                {{ now()->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y \a\t h:i A') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $reportedRows->count() }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total reports</p>
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-600">{{ $lostCount }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Lost</p>
        </div>
        <div>
            <p class="text-2xl font-bold text-orange-600">{{ $brokenCount }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Broken</p>
        </div>
    </div>
</div>

{{-- Report rows --}}
<div class="space-y-4">
    @forelse($reportedRows as $row)
        <div class="bg-white rounded-xl shadow overflow-hidden page-break-inside-avoid">
            <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
                @php $badge = ($row->damage_type ?? '') === 'lost' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800'; @endphp
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $badge }}">{{ ucfirst($row->damage_type ?? 'reported') }}</span>
                <span class="font-semibold text-gray-800">{{ $row->item_label }}</span>
                <span class="text-sm text-gray-600">× {{ $row->item->quantity }}</span>
                <span class="ml-auto font-mono text-xs text-emerald-700">{{ $row->transfer_number }}</span>
            </div>
            <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="space-y-2">
                    <p><span class="text-[11px] uppercase tracking-wide text-gray-400 block">Route</span>
                        <span class="text-gray-700">{{ $row->from_branch_name }} → {{ $row->to_branch_name }}</span></p>
                    <p><span class="text-[11px] uppercase tracking-wide text-gray-400 block">Rejected by receiving branch</span>
                        <span class="text-gray-700">{{ $row->return_reason ?? 'No reason given' }}</span></p>
                    <p><span class="text-[11px] uppercase tracking-wide text-gray-400 block">Stock manager's reason</span>
                        <span class="text-gray-700">{{ $row->damage_reason ?? '—' }}</span></p>
                </div>
                <div class="space-y-2">
                    <p><span class="text-[11px] uppercase tracking-wide text-gray-400 block">Transfer officer (attached)</span>
                        @if($row->officer_name)
                            <span class="text-gray-800 font-medium">{{ $row->officer_name }}</span>
                            <span class="block text-xs text-gray-500">{{ $row->officer_phone }}{{ $row->officer_id ? ' · ID '.$row->officer_id : '' }}</span>
                        @else
                            <span class="text-gray-500 italic">Not recorded on the transfer</span>
                        @endif
                    </p>
                    <p><span class="text-[11px] uppercase tracking-wide text-gray-400 block">Report filed by</span>
                        <span class="text-gray-700">{{ $row->damage_reported_by_name ?? '—' }}
                            @if($row->damage_reported_at)
                                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($row->damage_reported_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span>
                            @endif
                        </span>
                    </p>
                    <div class="pt-3 border-t border-dashed border-gray-200">
                        <p class="text-[11px] text-gray-400">Super Admin action (physical):</p>
                        <div class="h-10"></div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
            <i class="fas fa-file-circle-check text-3xl mb-2 block"></i>
            No lost / broken reports match the selected filters.
        </div>
    @endforelse
</div>

<div class="mt-6 text-xs text-gray-400 no-print">Signature: ______________________________</div>
@endsection
