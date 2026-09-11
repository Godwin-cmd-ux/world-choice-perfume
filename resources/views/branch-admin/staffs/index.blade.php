@extends('layouts.app')
@section('title', 'Staffs')
@section('header', 'Staffs')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="bg-white rounded-xl shadow px-4 py-3 flex flex-wrap items-center gap-3">
        <label class="text-sm font-medium text-gray-600">Role</label>
        <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All roles</option>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
            @endforeach
        </select>
        <label class="text-sm font-medium text-gray-600">Status</label>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All statuses</option>
            @foreach(['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        @if(request('role') || request('status'))
            <a href="{{ route('branch-admin.staffs.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-times mr-1"></i>Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Staff</th>
                <th class="text-left px-4">Role</th>
                <th class="text-right px-4">Total Sales</th>
                <th class="text-center px-4">Status</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($staff as $member)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <p class="font-medium">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500">{{ $member->email }} @if($member->phone) &middot; {{ $member->phone }} @endif</p>
                        </td>
                        <td class="px-4">
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">{{ ucwords(str_replace('_', ' ', $member->role)) }}</span>
                        </td>
                        <td class="px-4 text-right font-medium">TZS {{ number_format($member->total_sales) }}</td>
                        <td class="px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ match($member->status) { 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-yellow-100 text-yellow-700' } }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="px-4 text-center">
                            @if($member->status !== 'approved')
                                <form action="{{ route('branch-admin.staffs.approve', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline mr-2 font-medium"><i class="fas fa-check mr-1"></i>Approve</button>
                                </form>
                            @endif
                            @if($member->status !== 'rejected')
                                <form action="{{ route('branch-admin.staffs.reject', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline font-medium" onclick="return confirm('Reject {{ $member->name }}?')"><i class="fas fa-times mr-1"></i>Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No staff found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection