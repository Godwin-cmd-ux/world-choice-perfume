@extends('layouts.app')
@section('title', $user->name . ' — Staff Details')
@section('header', $user->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Profile Card --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-center mb-6">
                @if($user->profile_picture)
                    <img src="{{ $user->profile_picture }}" alt="" class="w-20 h-20 rounded-full object-cover mx-auto mb-3 ring-4 ring-amber-100">
                @else
                    <div class="w-20 h-20 rounded-full bg-amber-500 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3 ring-4 ring-amber-100">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                        @if(($user->role ?? '') === 'super_admin') bg-purple-100 text-purple-800
                        @elseif(($user->role ?? '') === 'branch_admin') bg-blue-100 text-blue-800
                        @else bg-green-100 text-green-800 @endif">
                        {{ str_replace('_', ' ', ucfirst($user->role ?? '')) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                        @if(($user->status ?? '') === 'active') bg-green-100 text-green-800
                        @elseif(($user->status ?? '') === 'pending') bg-yellow-100 text-yellow-800
                        @elseif(($user->status ?? '') === 'blocked') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($user->status ?? 'unknown') }}
                    </span>
                </div>
            </div>

            <div class="space-y-3 text-sm border-t pt-4">
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $user->phone ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Branch</span><span>{{ $user->branch?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Joined</span><span>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M d, Y') : '—' }}</span></div>
            </div>

            {{-- Actions --}}
            @if(($user->role ?? '') !== 'super_admin')
                <div class="mt-6 space-y-2 border-t pt-4">
                    <form method="POST" action="{{ route('super-admin.staff.toggle-status', $user->id) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Are you sure?')"
                            class="w-full px-4 py-2 rounded-lg text-sm font-medium {{ ($user->status ?? '') === 'blocked' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white' }}">
                            <i class="fas fa-{{ ($user->status ?? '') === 'blocked' ? 'unlock' : 'ban' }} mr-1"></i>
                            {{ ($user->status ?? '') === 'blocked' ? 'Unblock User' : 'Block User' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('super-admin.staff.destroy', $user->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('This will permanently delete this user. Are you sure?')"
                            class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50">
                            <i class="fas fa-trash mr-1"></i> Delete User
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Activity Tabs --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">Total Sales</p>
                <p class="text-2xl font-bold text-green-600">TZS {{ number_format($totalSales) }}</p>
                <p class="text-xs text-gray-400">{{ $sales->count() }} transactions</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">Total Expenses</p>
                <p class="text-2xl font-bold text-red-500">TZS {{ number_format($totalExpenses) }}</p>
                <p class="text-xs text-gray-400">{{ $expenses->count() }} records</p>
            </div>
        </div>

        {{-- Recent Sales --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-receipt mr-1 text-green-600"></i> Recent Sales</h3>
                <span class="text-xs text-gray-400">{{ $sales->count() }} records</span>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="text-left py-2 px-4 text-xs">Sale #</th>
                            <th class="text-left px-4 text-xs">Customer</th>
                            <th class="text-left px-4 text-xs">Payment</th>
                            <th class="text-right px-4 text-xs">Total</th>
                            <th class="text-left px-4 text-xs">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-2 px-4 font-medium text-xs">{{ $sale->sale_number }}</td>
                                <td class="px-4 text-xs text-gray-500">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td class="px-4 text-xs">{{ $sale->payment_summary ?? $sale->payment_method ?? '—' }}</td>
                                <td class="px-4 text-right font-medium text-xs">TZS {{ number_format($sale->total) }}</td>
                                <td class="px-4 text-xs text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->format('M d, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-400 text-xs">No sales yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Expenses --}}
        @if($expenses->count() > 0)
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-money-bill-wave mr-1 text-red-500"></i> Recent Expenses</h3>
                <span class="text-xs text-gray-400">{{ $expenses->count() }} records</span>
            </div>
            <div class="overflow-x-auto max-h-60 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="text-left py-2 px-4 text-xs">Category</th>
                            <th class="text-right px-4 text-xs">Amount</th>
                            <th class="text-left px-4 text-xs">Description</th>
                            <th class="text-left px-4 text-xs">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-2 px-4"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ $expense->category }}</span></td>
                                <td class="px-4 text-right font-medium text-xs text-red-600">TZS {{ number_format($expense->amount) }}</td>
                                <td class="px-4 text-xs text-gray-500 max-w-xs truncate">{{ $expense->description }}</td>
                                <td class="px-4 text-xs text-gray-500">{{ \Carbon\Carbon::parse($expense->created_at)->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-400 text-xs">No expenses yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Audit Log --}}
        @if($auditLogs->count() > 0)
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-history mr-1 text-blue-600"></i> Activity Log</h3>
            </div>
            <div class="max-h-60 overflow-y-auto">
                @foreach($auditLogs as $log)
                    <div class="flex items-center justify-between px-6 py-2 border-b last:border-0 hover:bg-gray-50">
                        <span class="text-xs text-gray-700">{{ str_replace('_', ' ', $log->action) }}</span>
                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, H:i') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('super-admin.staff.index') }}" class="text-amber-700 hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Staff</a>
</div>
@endsection
