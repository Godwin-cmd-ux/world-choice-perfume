@extends('layouts.app')
@section('title', 'Expense Report')
@section('header', 'Expense Report')

@section('header-actions')
    <button onclick="window.print()" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
@if($showCashierBanner ?? false)
    @include('cashier.partials.cross-branch-banner')
@endif

<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" action="{{ route($routePrefix . '.expenses') }}" class="flex gap-3 items-end flex-wrap">
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
        <span class="text-xs text-gray-500 pb-2">{{ $startDate->format('M d, Y') }} – {{ $endDate->format('M d, Y') }}</span>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold">Expenses: {{ $startDate->format('M d') }} — {{ $endDate->format('M d, Y') }}</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-6">Category</th>
                <th class="text-right px-6">Count</th>
                <th class="text-right px-6">Total (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expensesByCategory as $cat)
                <tr class="border-t hover:bg-gray-50">
                    <td class="py-3 px-6 font-medium capitalize">{{ $cat['category'] }}</td>
                    <td class="px-6 text-right">{{ $cat['count'] }}</td>
                    <td class="px-6 text-right font-medium text-red-600">TZS {{ number_format($cat['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-8 text-center text-gray-400">No expenses data</td></tr>
            @endforelse
        </tbody>
        @if(count($expensesByCategory))
            <tfoot class="bg-gray-50 border-t font-semibold">
                <tr>
                    <td class="py-3 px-6 text-right">Total</td>
                    <td class="px-6 text-right">{{ $transactions ?? array_sum(array_column($expensesByCategory, 'count')) }}</td>
                    <td class="px-6 text-right text-red-600">TZS {{ number_format($totalExpenses ?? array_sum(array_column($expensesByCategory, 'total'))) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
@endsection
