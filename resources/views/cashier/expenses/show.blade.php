@extends('layouts.app')
@section('title', 'Expense Details')
@section('header', 'Expense Details')

@section('content')
@include('cashier.partials.cross-branch-banner')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold mb-4">Expense #{{ $expense->id }}</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Category:</span><span class="capitalize">{{ $expense->category }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Amount:</span><span class="font-bold text-red-600">TZS {{ number_format($expense->amount) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Entered By:</span><span>{{ $expense->user?->name ?? '—' }}</span></div>
            @if($expense->branch?->name)
                <div class="flex justify-between"><span class="text-gray-500">Branch:</span><span>{{ $expense->branch->name }}</span></div>
            @endif
            <div class="flex justify-between"><span class="text-gray-500">Date:</span><span>{{ \Carbon\Carbon::parse($expense->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span></div>
            <div class="pt-3 border-t">
                <p class="text-gray-500 mb-1">Description:</p>
                <p class="bg-gray-50 p-3 rounded whitespace-pre-line">{{ $expense->description }}</p>
            </div>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-4">
        <a href="{{ route('cashier.expenses.index') }}" class="text-amber-700 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Back to Expenses</a>
        @unless($inCrossBranch)
            <a href="{{ route('cashier.expenses.edit', $expense->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200">
                <i class="fas fa-edit mr-1"></i> Edit Expense
            </a>
        @endunless
    </div>
</div>
@endsection
