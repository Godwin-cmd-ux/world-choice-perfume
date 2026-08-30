@extends('layouts.app')
@section('title', 'Branches')

@section('header', 'Branch Management')
@section('subtitle', 'Manage all store locations')

@section('header-actions')
    <a href="{{ route('super-admin.branches.create') }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
        <i class="fas fa-plus text-xs"></i> New Branch
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</th>
                    <th class="text-left py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Address</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Cashiers</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-center py-3 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-3">
                                @if($branch->profile_picture)
                                    <img src="{{ $branch->profile_picture }}" class="w-9 h-9 rounded-lg object-cover">
                                @else
                                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center">
                                        <i class="fas fa-store text-amber-600 text-sm"></i>
                                    </div>
                                @endif
                                <div class="font-medium text-gray-900">{{ $branch->name }}</div>
                            </div>
                        </td>
                        <td class="py-3.5 px-6 text-gray-500">{{ $branch->address ?? '—' }}</td>
                        <td class="py-3.5 px-6 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 bg-gray-100 rounded-full text-xs font-semibold text-gray-700">{{ $branch->cashiers_count }}</span>
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            @if($branch->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('super-admin.branches.edit', $branch->id) }}" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                @if($branch->is_active)
                                    <form action="{{ route('super-admin.branches.destroy', $branch->id) }}" method="POST" class="inline" onsubmit="return confirm('Deactivate this branch?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Deactivate">
                                            <i class="fas fa-ban text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-store text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500 mb-2">No branches yet</p>
                                <a href="{{ route('super-admin.branches.create') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">Create your first branch →</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
