@extends('layouts.app')
@section('title', 'Expense Report')
@section('header', 'Expense Report')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition"><i class="fas fa-print mr-1"></i> Print</button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
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
    </table>
</div>
@endsection
