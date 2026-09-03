@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance Stock Out')
@section('header', 'Oil Fragrance Stock Out')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                <i class="fas fa-arrow-right text-white"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">Use Oil Fragrance</h3>
                <p class="text-sm text-gray-500">Record oil fragrance used for production</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-manager.oil-fragrance-stock-out') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fragrance Name *</label>
                <select name="name" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Fragrance</option>
                    @foreach($oils as $oil)
                        <option value="{{ $oil['name'] }}">{{ $oil['name'] }} ({{ $oil['quantity'] ?? 0 }} in stock)</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (bottles) *</label>
                <input type="number" name="quantity" required min="1"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="Enter number of bottles used">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes</label>
                <input type="text" name="reason"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="e.g. Used for perfume batch #123">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Record Stock Out
                </button>
                <a href="{{ route('stock-manager.oil-fragrance') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
