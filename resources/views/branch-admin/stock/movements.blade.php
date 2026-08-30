@extends('layouts.app')
@section('title', 'Stock Movements')
@section('header', 'Stock Movements')

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <select name="product_id" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Products</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="type" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Types</option>
            <option value="entry" {{ request('type') === 'entry' ? 'selected' : '' }}>Entry</option>
            <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Sale</option>
            <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>Return</option>
            <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
            <option value="damage" {{ request('type') === 'damage' ? 'selected' : '' }}>Damage</option>
            <option value="missing" {{ request('type') === 'missing' ? 'selected' : '' }}>Missing</option>
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Product</th>
                <th class="text-center px-4">Type</th>
                <th class="text-right px-4">Qty</th>
                <th class="text-left px-4">Notes</th>
                <th class="text-left px-4">By</th>
                <th class="text-left px-4">Date</th>
            </tr></thead>
            <tbody>
                @forelse($movements as $m)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $m->product->name }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($m->type) { 'entry' => 'bg-green-100 text-green-700', 'sale' => 'bg-blue-100 text-blue-700', 'return' => 'bg-amber-100 text-amber-700', 'adjustment' => 'bg-gray-100 text-gray-700', 'damage' => 'bg-red-100 text-red-700', 'missing' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                                {{ ucfirst($m->type) }}
                            </span>
                        </td>
                        <td class="px-4 text-right font-medium {{ $m->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                        </td>
                        <td class="px-4 text-gray-500 max-w-xs truncate">{{ $m->notes ?? '-' }}</td>
                        <td class="px-4">{{ $m->performedBy?->name ?? '-' }}</td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No movements found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
@endsection
