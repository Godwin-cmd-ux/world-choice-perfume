@extends('layouts.app')
@section('title', 'Clients')
@section('header', 'Client Records — ' . $tabName)

@section('header-actions')
<a href="{{ route('customer-care.customers.create') }}"
   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-medium transition">
    <i class="fas fa-user-plus"></i> New Customer
</a>
@endsection

@section('content')
@php $showCounts = $counts !== []; @endphp

{{-- Branch tabs — built from the branches table, so new branches appear on their own --}}
<div class="border-b border-gray-200 mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="flex items-center gap-1 min-w-max">
        <a href="{{ route('customer-care.customers.index', array_filter(['q' => $q])) }}"
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $tab === 0 ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-users mr-1"></i> All Customers
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $tab === 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $totalCustomers }}</span>
        </a>
        @foreach($tabs as $t)
            <a href="{{ route('customer-care.customers.index', array_filter(['q' => $q, 'branch' => $t->id])) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $tab === $t->id ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
               @if($showCounts) title="{{ $t->count }} {{ \Illuminate\Support\Str::plural('client', $t->count) }} with transactions at this branch" @endif>
                <i class="fas fa-store mr-1"></i> {{ $t->name }}
                @if($showCounts)
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $tab === $t->id ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $t->count }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" action="{{ route('customer-care.customers.index') }}" class="flex flex-wrap items-center gap-3">
        @if($tab !== 0)
            <input type="hidden" name="branch" value="{{ $tab }}">
        @endif
        <div class="flex-1 min-w-[200px] relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="{{ old('q', $q) }}"
                placeholder="Search by name, phone or whatsapp{{ $tab === 0 ? '' : ' in this branch' }}..."
                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="px-4 py-2  hover:opacity-90 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-search mr-1"></i> Search
        </button>
        @if($q || $tab !== 0)
            <a href="{{ route('customer-care.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-times mr-1"></i> Clear
            </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Name</th>
                <th class="text-left px-4">Phone</th>
                <th class="text-left px-4">WhatsApp</th>
                <th class="text-left px-4">Branches Visited</th>
                <th class="text-left px-4">Last Visit</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    @php
                        $visit = $visits[(int) $customer->id] ?? ['last_visit' => null, 'branches' => []];
                        $recordUrl = route('customer-care.customers.show', $customer->id)
                            . ($tab === 0 ? '' : '?branch=' . $tab);
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-700">{{ $customer->name ?? 'Unnamed' }}</div>
                            @if($customer->email)
                                <div class="text-xs text-gray-400">{{ $customer->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->whatsapp ?? '—' }}</td>
                        <td class="px-4">
                            @php $visited = array_keys($visit['branches']); @endphp
                            @if($visited)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($visited as $branchId)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">
                                            <i class="fas fa-store text-[10px] text-gray-400"></i>
                                            {{ $branchNames[$branchId] ?? 'Branch #' . $branchId }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400">No visits yet</span>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">
                            @if($visit['last_visit'])
                                <span class="text-gray-600">{{ \Carbon\Carbon::parse($visit['last_visit'])->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') }}</span>
                                <span class="text-[10px] text-gray-400 block">{{ \Carbon\Carbon::parse($visit['last_visit'])->setTimezone('Africa/Dar_es_Salaam')->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            <a href="{{ $recordUrl }}"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium">
                                <i class="fas fa-eye"></i> View Record
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-users text-3xl mb-2 block"></i>
                            @if($q)
                                No clients match your search{{ $tab === 0 ? '' : ' in this branch' }}.
                            @elseif($tab === 0)
                                No clients recorded yet.
                            @else
                                No clients have transacted at this branch yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->isNotEmpty())
        <div class="px-4 py-3 bg-gray-50 border-t text-xs text-gray-500">
            @if($tab === 0)
                Showing {{ $customers->count() }} of {{ $totalCustomers }} clients
                @if($q)(matching your search)@endif.
            @elseif($truncated)
                Showing the {{ $customers->count() }} most recent visitors at {{ $tabName }}.
            @else
                Showing {{ $customers->count() }} clients with transactions at {{ $tabName }}@if($q) (matching your search)@endif.
            @endif
        </div>
    @endif
</div>
@endsection
