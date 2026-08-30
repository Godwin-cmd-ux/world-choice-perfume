@extends('layouts.app')
@section('title', 'Cashier Accountability')
@section('header', 'Cashier Accountability - ' . $date->format('M d, Y'))

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> View</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Cashier</th>
                <th class="text-right px-4">Expected Cash</th>
                <th class="text-right px-4">Actual Cash</th>
                <th class="text-right px-4">Expenses</th>
                <th class="text-right px-4">Difference</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Action</th>
            </tr></thead>
            <tbody>
                @forelse($results as $r)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $r['cashier']->name }}</td>
                        <td class="px-4 text-right">TZS {{ number_format($r['expected_cash']) }}</td>
                        <td class="px-4 text-right">
                            @if($r['actual_cash'] !== null)
                                TZS {{ number_format($r['actual_cash']) }}
                            @else
                                <span class="text-gray-400">Not recorded</span>
                            @endif
                        </td>
                        <td class="px-4 text-right text-red-600">TZS {{ number_format($r['expenses']) }}</td>
                        <td class="px-4 text-right font-medium
                            {{ $r['status'] === 'loss' ? 'text-red-600' : ($r['status'] === 'surplus' ? 'text-green-600' : '') }}">
                            @if($r['difference'] !== null)
                                {{ $r['difference'] >= 0 ? '+' : '' }}TZS {{ number_format($r['difference']) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            @if($r['status'] === 'balanced')
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Balanced</span>
                            @elseif($r['status'] === 'loss')
                                <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Loss</span>
                            @elseif($r['status'] === 'surplus')
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">Surplus</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            @if($r['status'] === 'pending')
                                <form method="POST" action="{{ route('branch-admin.cashiers.store-accountability') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="cashier_id" value="{{ $r['cashier']->id }}">
                                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                                    <input type="number" step="0.01" name="actual_cash" placeholder="Actual" required class="w-24 px-2 py-1 border rounded text-sm">
                                    <button type="submit" class="text-green-600 hover:underline ml-1"><i class="fas fa-save"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-400">No cashiers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
