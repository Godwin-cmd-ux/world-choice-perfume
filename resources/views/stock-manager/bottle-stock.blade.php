@extends('stock-manager.layouts.app')
@section('title', 'Bottle Stock')
@section('header', 'Bottle Stock')

@section('header-actions')
    <a href="{{ route('stock-manager.bottle-stock-in') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-plus mr-1"></i> Stock In
    </a>
    <a href="{{ route('stock-manager.bottle-stock-out') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-arrow-right mr-1"></i> Stock Out
    </a>
    <a href="{{ route('stock-manager.bottle-broken') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium mr-2">
        <i class="fas fa-broken-image mr-1"></i> Broken
    </a>
    <a href="{{ route('stock-manager.bottle-movements') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        <i class="fas fa-history mr-1"></i> History
    </a>
@endsection

@section('content')
{{-- Summary --}}
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
    @foreach($volumes as $volume)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-center">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-wine-bottle text-amber-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($bottleMap[$volume] ?? 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $volume }} bottles</p>
        </div>
    @endforeach
</div>

{{-- Quick Actions --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Quick Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('stock-manager.bottle-stock-in') }}" class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 transition">
            <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                <i class="fas fa-plus text-white"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Stock In</p>
                <p class="text-xs text-gray-500">Add bottles to inventory</p>
            </div>
        </a>
        <a href="{{ route('stock-manager.bottle-stock-out') }}" class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition">
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                <i class="fas fa-arrow-right text-white"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Stock Out</p>
                <p class="text-xs text-gray-500">Bottles sent for production</p>
            </div>
        </a>
        <a href="{{ route('stock-manager.bottle-broken') }}" class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition">
            <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center">
                <i class="fas fa-broken-image text-white"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800">Broken Bottles</p>
                <p class="text-xs text-gray-500">Record broken bottles</p>
            </div>
        </a>
    </div>
</div>
@endsection
