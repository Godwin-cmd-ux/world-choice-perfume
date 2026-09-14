@extends('layouts.app')
@section('title', 'Cross-Branch Cashier Monitoring')
@section('header', 'Cross-Branch Cashier Monitoring')

@section('content')
<div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg text-sm mb-6">
    <i class="fas fa-globe-africa mr-2"></i>
    Monitor the cashier activities of <strong>@if($isSuperAdmin) all branches @else all other branches @endif</strong>. Click a branch to view its sales. Monitoring is <strong>read-only</strong> — no changes can be made while inside a branch.
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">@if($isSuperAdmin) All Branches @else Other Branches @endif</h3>
        <span class="text-xs text-gray-400">{{ $rows->count() }} branch(es)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Today's Revenue</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Transactions</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Paid</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Pending Orders</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Active Cashiers</th>
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
                        <td class="px-6 py-4 text-right font-bold text-green-700">TZS {{ number_format($branch->todayRevenue) }}</td>
                        <td class="px-6 py-4 text-center text-gray-700">{{ $branch->todayTransactions }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-green-600 font-medium">{{ $branch->todayPaid }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="{{ $branch->pendingOrders > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">{{ $branch->pendingOrders }}</span>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-700">{{ $branch->activeCashiers }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('cashier.cross-branch.enter', $branch->id) }}"
                               style="background-color: #F89A1E;"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-white hover:opacity-90 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-eye text-[10px]"></i> Open branch
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
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
