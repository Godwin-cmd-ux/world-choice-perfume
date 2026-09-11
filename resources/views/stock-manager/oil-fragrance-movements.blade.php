@extends('stock-manager.layouts.app')
@section('title', 'Oil Fragrance History')
@section('header', 'Oil Fragrance History')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Types</option>
                    <option value="stock_in" {{ request('type') == 'stock_in' ? 'selected' : '' }}>Stock In</option>
                    <option value="stock_out" {{ request('type') == 'stock_out' ? 'selected' : '' }}>Stock Out</option>
                </select>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Date</th>
                    <th class="text-left px-4">Fragrance</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-right px-4">Quantity</th>
                    <th class="text-left px-4">Reason</th>
                    <th class="text-left px-4">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 font-medium">{{ $m->name }}</td>
                        <td class="px-4">
                            @if($m->type === 'stock_in')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Stock In</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">Stock Out</span>
                            @endif
                        </td>
                        <td class="px-4 text-right font-medium">{{ $m->quantity }}</td>
                        <td class="px-4 text-gray-500">{{ $m->reason ?? '-' }}</td>
                        <td class="px-4 text-gray-500">{{ $m->performedBy->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No movements found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
