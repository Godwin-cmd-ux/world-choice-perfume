@extends('layouts.app')
@section('title', 'Cashier List')
@section('header', 'Cashiers')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Name</th>
                <th class="text-left px-4">Email</th>
                <th class="text-center px-4">Status</th>
                <th class="text-right px-4">Total Sales</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($cashiers as $cashier)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                @if($cashier->profile_picture)
                                    <img src="{{ $cashier->profile_picture }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-700">{{ substr($cashier->name, 0, 1) }}</div>
                                @endif
                                <span class="font-medium">{{ $cashier->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 text-gray-500">{{ $cashier->email }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ match($cashier->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                                {{ ucfirst($cashier->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-right">TZS {{ number_format($cashier->total_sales ?? 0) }}</td>
                        <td class="px-4 text-center">
                            <a href="{{ route('branch-admin.cashiers.show', $cashier->id) }}" class="text-blue-600 hover:underline mr-2"><i class="fas fa-eye"></i></a>
                            @if($cashier->status === 'pending')
                                <form action="{{ route('branch-admin.cashiers.approve', $cashier->id) }}" method="POST" class="inline">@csrf
                                    <button type="submit" class="text-green-600 hover:underline mr-2"><i class="fas fa-check"></i></button>
                                </form>
                                <form action="{{ route('branch-admin.cashiers.reject', $cashier->id) }}" method="POST" class="inline">@csrf
                                    <button type="submit" class="text-red-600 hover:underline"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No cashiers yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>
@endsection
