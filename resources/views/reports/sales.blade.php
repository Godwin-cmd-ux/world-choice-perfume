@extends('layouts.app')
@section('title', 'Daily Sales Report')
@section('header', 'Daily Sales Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print View</button>
    <a href="{{ route($routePrefix . '.print-sales-report', ['date' => $date->toDateString()]) }}"
       target="_blank" rel="noopener"
       style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition">
        <i class="fas fa-print mr-1"></i> Print PDF
    </a>
    <a href="{{ route($routePrefix . '.generate-sales-report', ['date' => $date->toDateString()]) }}"
       style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition">
        <i class="fas fa-file-pdf mr-1"></i> Download PDF
    </a>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

{{-- Date filter --}}
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" action="{{ route($routePrefix . '.sales') }}" class="flex gap-3 items-end flex-wrap">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Date</label>
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Branch</label>
            <div class="px-3 py-2 border rounded-lg text-sm bg-gray-50 text-gray-600">
                <i class="fas fa-store mr-1 text-amber-600"></i>{{ $branchName ?? 'Branch' }}
            </div>
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route($routePrefix . '.sales') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Reset</a>
    </form>
</div>

{{-- Report Header --}}
<div class="mb-6 print-header">
    <h2 class="text-lg font-semibold text-gray-800">World Choice Perfumes — Daily Sales Report</h2>
    <p class="text-sm text-gray-500">{{ $date->timezone('Africa/Dar_es_Salaam')->format('l, d F Y') }}</p>
    <p class="text-sm text-gray-500">Branch: {{ $branchName ?? 'Branch' }}</p>
</div>

{{-- Grand Total Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-green-600">TZS {{ number_format($totalSales) }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Sales Revenue</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $totalTransactions }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Transactions</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-2xl font-bold text-amber-600">{{ $totalItems }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Items Sold</p>
    </div>
</div>

{{-- Branch breakdown --}}
@if($branchGroups->count() > 0)
    @foreach($branchGroups as $group)
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="px-6 py-4 border-b flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-store text-blue-700"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $group['branch_name'] }}</h3>
                        <p class="text-xs text-gray-400">{{ $group['transactions'] }} transaction{{ $group['transactions'] !== 1 ? 's' : '' }} • {{ $group['items_sold'] }} item{{ $group['items_sold'] !== 1 ? 's' : '' }} sold</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-green-700">TZS {{ number_format($group['total_sales']) }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4">Sale #</th>
                            <th class="text-left px-4">Served By</th>
                            <th class="text-right px-4">Items</th>
                            <th class="text-right px-4">Total</th>
                            <th class="text-left px-4">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($group['sales'] as $sale)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium">{{ $sale->sale_number ?? '—' }}</td>
                                <td class="px-4 text-gray-500">{{ $sale->cashier?->name ?? '—' }}</td>
                                <td class="px-4 text-right text-gray-500">{{ $sale->items_count ?? 0 }}</td>
                                <td class="px-4 text-right font-medium text-green-700">TZS {{ number_format($sale->total ?? 0) }}</td>
                                <td class="px-4 text-gray-500 text-xs">{{ $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-400">No sales for this branch on this day</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@else
    <div class="bg-white rounded-xl shadow p-8 text-center">
        <i class="fas fa-chart-bar text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-400">No sales recorded for {{ $date->timezone('Africa/Dar_es_Salaam')->format('d M Y') }}</p>
    </div>
@endif

{{-- Print-only summary footer --}}
<div class="hidden print:block mt-8 text-center text-xs text-gray-400">
    Report generated on {{ now()->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') }} • World Choice Perfumes
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .print-header { text-align: center; margin-bottom: 1.5rem; }
        body { font-size: 12px; }
        .shadow { box-shadow: none; }
        table { page-break-inside: avoid; }
    }
</style>
@endsection
