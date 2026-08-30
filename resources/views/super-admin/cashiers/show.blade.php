@extends('layouts.app')
@section('title', $cashier->name . ' — Details')

@section('header', 'User Details')
@section('subtitle', $cashier->name)

@section('header-actions')
    <a href="{{ route('super-admin.cashiers.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 font-medium">
        <i class="fas fa-arrow-left text-xs"></i> Back to list
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-6 border-b border-gray-100">
            <div class="flex items-center gap-5">
                @if($cashier->profile_picture)
                    <img src="{{ $cashier->profile_picture }}" class="w-16 h-16 rounded-xl object-cover border-2 border-gray-100">
                @else
                    <div class="w-16 h-16 rounded-xl bg-amber-100 flex items-center justify-center text-2xl font-bold text-amber-700">
                        {{ substr($cashier->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $cashier->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $cashier->email }}</p>
                    @php
                        $statusStyles = [
                            'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                            'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                            'active' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                            'rejected' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                        ];
                        $style = $statusStyles[$cashier->status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $style }} mt-2">
                        {{ ucfirst($cashier->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="px-6 py-5">
            <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Phone</p>
                    <p class="text-gray-900">{{ $cashier->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Branch</p>
                    <p class="text-gray-900">{{ $cashier->branch?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Role</p>
                    <p class="text-gray-900 capitalize">{{ str_replace('_', ' ', $cashier->role) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Registered</p>
                    <p class="text-gray-900">
                        @if($cashier->created_at && is_object($cashier->created_at))
                            {{ \Carbon\Carbon::parse($cashier->created_at)->format('M d, Y') }}
                        @else
                            {{ $cashier->created_at ?? '—' }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @if($cashier->status === 'pending')
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                <form action="{{ route('super-admin.cashiers.approve', $cashier->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                        <i class="fas fa-check text-xs"></i> Approve
                    </button>
                </form>
                <form action="{{ route('super-admin.cashiers.reject', $cashier->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                        <i class="fas fa-times text-xs"></i> Reject
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
