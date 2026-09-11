@extends('stock-manager.layouts.app')
@section('title', 'Cross-Branch Stock')
@section('header', 'Cross-Branch Stock')

@section('content')
<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm mb-6">
    <i class="fas fa-globe-africa mr-2"></i>
    Monitor the stock of <strong>all branches</strong>. Click a branch to open it and view its stock exactly like that
    branch's stock manager. Monitoring is <strong>read-only</strong> — no changes can be made while inside a branch.
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">All Branches</h3>
        <span class="text-xs text-gray-400">{{ $rows->count() }} branch(es)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Product Items</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Low Stock</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Bottles</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Oil Fragrances</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $branch)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $branch->name }}</p>
                            @if($branch->address)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $branch->address }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-gray-700">{{ number_format($branch->totalProducts) }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="{{ $branch->lowStock > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">{{ number_format($branch->lowStock) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($branch->totalBottles) }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($branch->totalOils) }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('stock-manager.cross-branch.enter', $branch->id) }}"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-eye text-[10px]"></i> Open branch
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-university text-3xl mb-2 block"></i>
                            No branches found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection