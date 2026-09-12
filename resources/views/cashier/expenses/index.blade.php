@extends('layouts.app')
@section('title', 'My Expenses')
@section('header', 'My Expenses')


@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <p class="text-sm text-gray-500">Total Expenses: <strong class="text-lg text-red-600">TZS {{ number_format($totalExpenses) }}</strong></p>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="category" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Categories</option>
            @foreach(['electricity','water','transport','cleaning','packaging','other'] as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        @if(request('category') || request('date_from') || request('date_to'))
            <a href="{{ route('cashier.expenses.index') }}" class="text-gray-500 text-sm px-3 py-2">Clear</a>
        @endif
    </form>
    <a href="{{ route('cashier.expenses.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-plus mr-1"></i> Record Expense
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Category</th>
                <th class="text-right px-4">Amount</th>
                <th class="text-left px-4">Entered By</th>
                <th class="text-left px-4">Description</th>
                <th class="text-left px-4">Date</th>
            </tr></thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 capitalize">{{ $expense->category }}</span>
                        </td>
                        <td class="px-4 text-right font-medium text-red-600">TZS {{ number_format($expense->amount) }}</td>
                        <td class="px-4 text-gray-700 font-medium">{{ $expense->user?->name ?? '—' }}</td>
                        <td class="px-4 text-gray-500 max-w-xs truncate">{{ $expense->description }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($expense->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No expenses recorded</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
