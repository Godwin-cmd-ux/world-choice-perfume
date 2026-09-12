@extends('layouts.app')
@section('title', 'Clients')
@section('header', 'Client Records')

@section('header-actions')
<a href="{{ route('customer-care.customers.create') }}"
   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-medium transition">
    <i class="fas fa-user-plus"></i> New Customer
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" action="{{ route('customer-care.customers.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px] relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="{{ old('q', $q) }}" placeholder="Search by name, phone or whatsapp..."
                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-search mr-1"></i> Search
        </button>
        @if($q)
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
                <th class="text-left px-4">Email</th>
                <th class="text-left px-4">Registered</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-700">{{ $customer->name ?? 'Unnamed' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->whatsapp ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->email ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->created_at ? \Carbon\Carbon::parse($customer->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</td>
                        <td class="px-4 text-center">
                            <a href="{{ route('customer-care.customers.show', $customer->id) }}"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium">
                                <i class="fas fa-eye"></i> View Record
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-users text-3xl mb-2 block"></i>
                            {{ $q ? 'No clients match your search.' : 'No clients recorded yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection