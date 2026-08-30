@extends('layouts.app')
@section('title', 'Record Expense')
@section('header', 'Record New Expense')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('branch-admin.expenses.store') }}" class="bg-white rounded-xl shadow p-6">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('category') border-red-500 @enderror">
                    <option value="">-- Select Category --</option>
                    <option value="electricity">Electricity</option>
                    <option value="water">Water</option>
                    <option value="transport">Transport</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="packaging">Packaging</option>
                    <option value="other">Other</option>
                </select>
                @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS) *</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required min="0.01"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('amount') border-red-500 @enderror">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Description * <span class="text-xs text-gray-400">(Required - be specific)</span></label>
                <textarea name="description" rows="4" required minlength="10"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 @error('description') border-red-500 @enderror"
                    placeholder="e.g., Payment of electricity bill for Kinondoni branch for August 2026">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <a href="{{ route('branch-admin.expenses.index') }}" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">Record Expense</button>
        </div>
    </form>
</div>
@endsection
