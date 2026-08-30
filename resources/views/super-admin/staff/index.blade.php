@extends('layouts.app')
@section('title', 'Staff Management')
@section('header', 'Staff Management')
@section('header-actions')
    <span class="text-sm text-gray-500">{{ $users->count() }} staff members</span>
@endsection

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..." class="px-3 py-2 border rounded-lg text-sm flex-1 min-w-[200px]">
        <select name="role" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Roles</option>
            <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            <option value="branch_admin" {{ request('role') === 'branch_admin' ? 'selected' : '' }}>Branch Admin</option>
            <option value="cashier" {{ request('role') === 'cashier' ? 'selected' : '' }}>Cashier</option>
        </select>
        <select name="status" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button type="submit" class="bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

{{-- Staff Table --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Staff</th>
                    <th class="text-left px-4">Role</th>
                    <th class="text-left px-4">Branch</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Joined</th>
                    <th class="text-center px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                @if($user->profile_picture)
                                    <img src="{{ $user->profile_picture }}" alt="" class="w-9 h-9 rounded-full object-cover">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-amber-500 flex items-center justify-center text-white text-sm font-bold">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('super-admin.staff.show', $user->id) }}" class="font-medium text-gray-900 hover:text-amber-700">{{ $user->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if(($user->role ?? '') === 'super_admin') bg-purple-100 text-purple-800
                                @elseif(($user->role ?? '') === 'branch_admin') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800 @endif">
                                {{ str_replace('_', ' ', ucfirst($user->role ?? '')) }}
                            </span>
                        </td>
                        <td class="px-4 text-gray-500 text-xs">{{ $user->branch?->name ?? '—' }}</td>
                        <td class="px-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if(($user->status ?? '') === 'active') bg-green-100 text-green-800
                                @elseif(($user->status ?? '') === 'pending') bg-yellow-100 text-yellow-800
                                @elseif(($user->status ?? '') === 'blocked') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($user->status ?? 'unknown') }}
                            </span>
                        </td>
                        <td class="px-4 text-xs text-gray-500">{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M d, Y') : '—' }}</td>
                        <td class="px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('super-admin.staff.show', $user->id) }}" class="text-blue-600 hover:text-blue-800" title="View Details"><i class="fas fa-eye"></i></a>
                                @if(($user->role ?? '') !== 'super_admin')
                                    <form method="POST" action="{{ route('super-admin.staff.toggle-status', $user->id) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        <button type="submit" class="{{ ($user->status ?? '') === 'blocked' ? 'text-green-600 hover:text-green-800' : 'text-red-600 hover:text-red-800' }}" title="{{ ($user->status ?? '') === 'blocked' ? 'Unblock' : 'Block' }}">
                                            <i class="fas fa-{{ ($user->status ?? '') === 'blocked' ? 'unlock' : 'ban' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-users text-3xl mb-2 block"></i>
                            No staff found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
