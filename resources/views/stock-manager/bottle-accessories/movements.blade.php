@extends('stock-manager.layouts.app')
@section('title', 'Bottle Accessories Movements')
@section('header', 'Bottle Accessories — Movements')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Date</th>
                    <th class="text-left px-4">Type</th>
                    <th class="text-left px-4">Color</th>
                    @if($isGlobalScope ?? false)
                        <th class="text-left px-4">Branch</th>
                    @endif
                    <th class="text-left px-4">Action</th>
                    <th class="text-right px-4">Packets</th>
                    <th class="text-left px-4">Reason</th>
                    <th class="text-left px-4">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 font-medium capitalize">{{ str_replace('_', ' ', $m->type ?? '') }}</td>
                        <td class="px-4 capitalize">{{ $m->color ?? '—' }}</td>
                        @if($isGlobalScope ?? false)
                            <td class="px-4 text-gray-500">{{ $m->branchName ?? '—' }}</td>
                        @endif
                        <td class="px-4">
                            @if(($m->movement_type ?? '') === 'stock_in')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Stock In</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">Stock Out</span>
                            @endif
                        </td>
                        <td class="px-4 text-right font-medium">{{ $m->quantity }}</td>
                        <td class="px-4 text-gray-500">{{ $m->reason ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $m->performedBy->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($isGlobalScope ?? false) ? 8 : 7 }}" class="py-8 text-center text-gray-400">No movements recorded</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('stock-manager.bottle-accessories.index') }}" class="text-emerald-600 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Accessories</a>
</div>
@endsection
