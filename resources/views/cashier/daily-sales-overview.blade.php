@extends('layouts.app')
@section('title', 'Daily Sales — All Branches')
@section('header', 'Daily Sales — All Branches')

@section('content')
<div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg text-sm mb-6">
    <i class="fas fa-chart-bar mr-2"></i>
    Company-wide overview for <strong>{{ now()->setTimezone('Africa/Dar_es_Salaam')->format('l, M d, Y') }}</strong> (Dar es Salaam time). Paid sales only.
</div>

{{-- Company totals --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill text-green-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($companySales) }}</p><p class="text-sm text-gray-500">Company Daily Sales (Paid)</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-money-bill-wave text-red-700 text-xl"></i></div>
            <div><p class="text-2xl font-bold">TZS {{ number_format($companyExpenses) }}</p><p class="text-sm text-gray-500">Company Daily Expenses</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 {{ ($companySales - $companyExpenses) >= 0 ? 'bg-amber-100' : 'bg-red-100' }} rounded-full flex items-center justify-center"><i class="fas fa-balance-scale {{ ($companySales - $companyExpenses) >= 0 ? 'text-amber-700' : 'text-red-700' }} text-xl"></i></div>
            <div><p class="text-2xl font-bold {{ ($companySales - $companyExpenses) >= 0 ? '' : 'text-red-600' }}">TZS {{ number_format($companySales - $companyExpenses) }}</p><p class="text-sm text-gray-500">Actual Sales (Sales − Expenses)</p></div>
        </div>
    </div>
</div>

{{-- Branch table with percentage contribution --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-8">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Branches — Daily Sales & Contribution</h3>
        <span class="text-xs text-gray-400">{{ $rows->count() }} branch(es)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Daily Sales</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Daily Expenses</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actual Sales</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">% of Company Sales</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Transactions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $row->name }}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-700">TZS {{ number_format($row->dailySales) }}</td>
                        <td class="px-6 py-4 text-right font-medium text-red-600">TZS {{ number_format($row->dailyExpenses) }}</td>
                        <td class="px-6 py-4 text-right font-semibold {{ $row->actualSales >= 0 ? 'text-amber-700' : 'text-red-600' }}">TZS {{ number_format($row->actualSales) }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, $row->salesPercent) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700">{{ number_format($row->salesPercent, 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-700">{{ $row->transactions }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400"><i class="fas fa-university text-3xl mb-2 block"></i> No branch sales recorded today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Staff contribution per branch --}}
<h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-users text-amber-600 mr-2"></i>Staff Contribution Per Branch</h3>

@forelse($rows as $row)
    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between flex-wrap gap-2">
            <h4 class="font-semibold text-gray-800">{{ $row->name }}</h4>
            <span class="text-xs text-gray-500">Branch daily sales: <strong class="text-green-700">TZS {{ number_format($row->dailySales) }}</strong></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Staff</th>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Sales</th>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Expenses</th>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actual</th>
                        <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">% of Branch Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($row->staff as $staff)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $staff->name }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs capitalize bg-gray-100 text-gray-600">{{ str_replace('_', ' ', $staff->role) }}</span>
                            </td>
                            <td class="px-6 py-3 text-right font-medium text-green-700">TZS {{ number_format($staff->sales) }}</td>
                            <td class="px-6 py-3 text-right text-red-600">TZS {{ number_format($staff->expenses) }}</td>
                            <td class="px-6 py-3 text-right font-semibold {{ $staff->actual >= 0 ? 'text-amber-700' : 'text-red-600' }}">TZS {{ number_format($staff->actual) }}</td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $staff->salesPercent) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700">{{ number_format($staff->salesPercent, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No staff activity recorded today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">No branches found.</div>
@endforelse
@endsection
