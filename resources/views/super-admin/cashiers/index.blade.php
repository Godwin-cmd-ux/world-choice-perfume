@extends('layouts.app')
@section('title', 'Approvals')

@section('header', ucfirst($role === 'branch_admin' ? 'Branch Admin' : 'Cashier') . ' Approvals')
@section('subtitle', 'Review and manage pending registrations')

@section('content')
{{-- Tabs --}}
<div class="flex items-center gap-2 mb-5">
    <a href="{{ route('super-admin.cashiers.index', ['role' => 'cashier']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $role === 'cashier' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="fas fa-cash-register mr-1.5"></i> Cashiers
    </a>
    <a href="{{ route('super-admin.cashiers.index', ['role' => 'branch_admin']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $role === 'branch_admin' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="fas fa-user-shield mr-1.5"></i> Branch Admins
    </a>
</div>

{{-- Status Filters --}}
<div class="flex items-center gap-2 mb-5">
    @php $currentStatus = request('status'); @endphp
    <a href="{{ route('super-admin.cashiers.index', array_merge(['role' => $role])) }}"
       class="px-3 py-1.5 rounded-full text-xs font-medium transition-all {{ !$currentStatus ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        Pending
    </a>
    <a href="{{ route('super-admin.cashiers.index', array_merge(['role' => $role, 'status' => 'all'])) }}"
       class="px-3 py-1.5 rounded-full text-xs font-medium transition-all {{ $currentStatus === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        All
    </a>
    <a href="{{ route('super-admin.cashiers.index', array_merge(['role' => $role, 'status' => 'approved'])) }}"
       class="px-3 py-1.5 rounded-full text-xs font-medium transition-all {{ $currentStatus === 'approved' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        Approved
    </a>
    <a href="{{ route('super-admin.cashiers.index', array_merge(['role' => $role, 'status' => 'rejected'])) }}"
       class="px-3 py-1.5 rounded-full text-xs font-medium transition-all {{ $currentStatus === 'rejected' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        Rejected
    </a>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-3">
                                @if($user->profile_picture)
                                    <img src="{{ $user->profile_picture }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-700">{{ substr($user->name, 0, 1) }}</div>
                                @endif
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-6 text-gray-500">{{ $user->email }}</td>
                        <td class="py-3.5 px-6 text-gray-500">{{ $user->phone ?? '—' }}</td>
                        <td class="py-3.5 px-6">
                            @if($user->branch?->name)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded text-xs font-medium text-gray-600">
                                    <i class="fas fa-store text-[10px]"></i> {{ $user->branch->name }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            @php
                                $statusStyles = [
                                    'pending' => 'bg-amber-50 text-amber-700',
                                    'approved' => 'bg-emerald-50 text-emerald-700',
                                    'active' => 'bg-blue-50 text-blue-700',
                                    'rejected' => 'bg-red-50 text-red-700',
                                ];
                                $style = $statusStyles[$user->status] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $style }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('super-admin.cashiers.show', $user->id) }}" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if($user->status === 'pending')
                                    <form action="{{ route('super-admin.cashiers.approve', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Approve">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('super-admin.cashiers.reject', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Reject">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-user-check text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No {{ $role === 'branch_admin' ? 'branch admins' : 'cashiers' }} found</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $users->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
