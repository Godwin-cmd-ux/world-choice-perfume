@extends('layouts.app')
@section('title', 'Staff Performance Report')
@section('header', 'Staff Performance Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition">
        <i class="fas fa-print mr-1"></i> Print Report
    </button>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" action="{{ route($routePrefix . '.staff-performance') }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Branch</label>
            <div class="px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-600">
                <i class="fas fa-store mr-1 text-amber-600"></i>{{ $branchName ?? 'Branch' }}
            </div>
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold">Performance: {{ $startDate->format('M d') }} — {{ $endDate->format('M d, Y') }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Staff Member</th>
                    <th class="text-left px-4">Role</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-right px-4">Sales (TZS)</th>
                    <th class="text-right px-4">Transactions</th>
                    <th class="text-right px-4">Items Sold</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $r)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $r['user_name'] }}</td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if(($r['role'] ?? '') === 'cashier') bg-green-100 text-green-800
                                @elseif(($r['role'] ?? '') === 'stock_manager') bg-emerald-100 text-emerald-800
                                @elseif(($r['role'] ?? '') === 'branch_admin') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ str_replace('_', ' ', ucfirst($r['role'] ?? '')) }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-500">{{ $r['branch_name'] ?? '—' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($r['total_sales']) }}</td>
                        <td class="px-4 text-right">{{ $r['transaction_count'] }}</td>
                        <td class="px-4 text-right">{{ $r['items_sold'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No staff performance data for the selected period.</td></tr>
                @endforelse
            </tbody>
            @if(count($report))
                <tfoot class="bg-gray-50 border-t font-semibold">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right">Total</td>
                        <td class="px-4 text-right">TZS {{ number_format(array_sum(array_column($report, 'total_sales'))) }}</td>
                        <td class="px-4 text-right">{{ number_format(array_sum(array_column($report, 'transaction_count'))) }}</td>
                        <td class="px-4 text-right">{{ number_format(array_sum(array_column($report, 'items_sold'))) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
