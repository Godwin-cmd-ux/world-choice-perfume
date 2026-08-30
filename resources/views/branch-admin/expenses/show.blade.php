@extends('layouts.app')
@section('title', 'Expense Details')
@section('header', 'Expense Details')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold mb-4">Expense #{{ $expense->id }}</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Category:</span><span class="capitalize">{{ $expense->category }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Amount:</span><span class="font-bold text-red-600">TZS {{ number_format($expense->amount) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Entered By:</span><span>{{ $expense->user->name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Date:</span><span>{{ \Carbon\Carbon::parse($expense->created_at)->format('M d, Y H:i') }}</span></div>
            <div class="pt-3 border-t">
                <p class="text-gray-500 mb-1">Description:</p>
                <p class="bg-gray-50 p-3 rounded">{{ $expense->description }}</p>
            </div>
        </div>
    </div>
    <a href="{{ route('branch-admin.expenses.index') }}" class="mt-4 inline-block text-amber-700 hover:underline">&larr; Back to Expenses</a>
</div>
@endsection
