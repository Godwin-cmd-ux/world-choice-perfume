@extends('layouts.app')
@section('title', 'Expense Report')
@section('header', 'Expense Report')

@section('header-actions')
    <button onclick="window.print()" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-800 transition">
        <i class="fas fa-print mr-1"></i> Generate Report
    </button>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6 no-print">
        <input type="date" name="date_from" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <input type="date" name="date_to" value="{{ $endDate->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($expensesByCategory as $exp)
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-receipt text-red-600"></i></div>
                <div>
                    <h3 class="font-semibold capitalize">{{ $exp['category'] }}</h3>
                    <p class="text-xs text-gray-500">{{ $exp['count'] }} expense(s)</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600">TZS {{ number_format($exp['total']) }}</p>
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-gray-400">No expenses in this period</div>
    @endforelse
</div>
<a href="{{ route('branch-admin.reports.index') }}" class="mt-4 inline-block text-amber-700 hover:underline no-print">&larr; Back to Reports</a>
@endsection
