@extends('layouts.app')
@section('title', 'Cashier Performance')
@section('header', 'Cashier Performance: ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'))

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition">
        <i class="fas fa-print mr-1"></i> Generate Report
    </button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Cashier</th>
                <th class="text-right px-4">Total Sales</th>
                <th class="text-right px-4">Transactions</th>
                <th class="text-right px-4">Items Sold</th>
            </tr></thead>
            <tbody>
                @forelse($report as $r)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $r['cashier_name'] }}</td>
                        <td class="px-4 text-right font-bold text-green-700">TZS {{ number_format($r['total_sales']) }}</td>
                        <td class="px-4 text-right">{{ $r['transaction_count'] }}</td>
                        <td class="px-4 text-right">{{ $r['items_sold'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-400">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<a href="{{ route('branch-admin.reports.index') }}" class="mt-4 inline-block text-amber-700 hover:underline no-print">&larr; Back to Reports</a>
@endsection
