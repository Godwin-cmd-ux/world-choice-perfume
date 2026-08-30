@extends('layouts.app')
@section('title', 'Sales Report')
@section('header', 'Sales Report: ' . $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'))

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition">
        <i class="fas fa-print mr-1"></i> Generate Report
    </button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-3xl font-bold text-green-600">TZS {{ number_format($report['total_sales']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Sales</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-3xl font-bold text-blue-600">{{ $report['total_transactions'] }}</p>
        <p class="text-sm text-gray-500 mt-1">Transactions</p>
    </div>
    <div class="bg-white rounded-xl shadow p-6 text-center">
        <p class="text-3xl font-bold text-amber-600">{{ $report['total_items_sold'] }}</p>
        <p class="text-sm text-gray-500 mt-1">Items Sold</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b"><h3 class="font-semibold">Transaction Details</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="text-left py-3 px-4">Sale #</th><th class="text-left px-4">Cashier</th><th class="text-left px-4">Customer</th><th class="text-right px-4">Total</th><th class="text-left px-4">Date</th></tr></thead>
            <tbody>
                @forelse($report['sales'] as $sale)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-2 px-4">{{ $sale->sale_number }}</td>
                        <td class="px-4">{{ $sale->cashier->name }}</td>
                        <td class="px-4 text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($sale->total) }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No sales in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<a href="{{ route('branch-admin.reports.index') }}" class="mt-4 inline-block text-amber-700 hover:underline no-print">&larr; Back to Reports</a>
@endsection
