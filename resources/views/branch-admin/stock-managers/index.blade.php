@extends('layouts.app')
@section('title', 'Stock Managers')
@section('header', 'Stock Managers')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4">Name</th>
                    <th class="text-left px-4">Email</th>
                    <th class="text-left px-4">Phone</th>
                    <th class="text-left px-4">Status</th>
                    <th class="text-left px-4">Joined</th>
                    <th class="text-left px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockManagers as $manager)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">
                            <div class="flex items-center gap-3">
                                @if($manager->profile_picture)
                                    <img src="{{ $manager->profile_picture }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($manager->name, 0, 1) }}
                                    </div>
                                @endif
                                {{ $manager->name }}
                            </div>
                        </td>
                        <td class="px-4 text-gray-500">{{ $manager->email }}</td>
                        <td class="px-4 text-gray-500">{{ $manager->phone ?? '-' }}</td>
                        <td class="px-4">
                            @if($manager->status === 'pending')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-700">Pending</span>
                            @elseif($manager->status === 'approved' || $manager->status === 'active')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">{{ ucfirst($manager->status) }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">{{ ucfirst($manager->status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($manager->created_at)->format('M d, Y') }}</td>
                        <td class="px-4">
                            @if($manager->status === 'pending')
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('branch-admin.stock-managers.approve', $manager->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full hover:bg-green-200 font-medium">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('branch-admin.stock-managers.reject', $manager->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full hover:bg-red-200 font-medium">
                                            <i class="fas fa-times mr-1"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">No stock managers registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
