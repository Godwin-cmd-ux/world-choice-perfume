@extends('layouts.app')
@section('title', 'Loyal Customers')
@section('header', 'Loyal Customers')

@section('header-actions')
<a href="{{ route('customer-care.customers.create') }}"
   class="inline-flex items-center gap-2 px-4 py-2 bg-white border hover:bg-gray-50 text-amber-700 border-amber-300 rounded-lg text-sm font-medium transition">
    <i class="fas fa-user-plus"></i> New Customer
</a>
{{-- The requested top-right action: opens the picker that searches available
     customers and ticks one or more to add as loyal. --}}
<a href="{{ route('customer-care.customers.loyal', array_filter(['q' => $q, 'create' => $create ? null : 1])) }}"
   class="inline-flex items-center gap-2 px-4 py-2 {{ $create ? 'bg-gray-100 hover:bg-gray-200 text-gray-600' : 'bg-amber-700 hover:bg-amber-800 text-white' }} rounded-lg text-sm font-medium transition">
    <i class="fas {{ $create ? 'fa-times' : 'fa-star' }}"></i> {{ $create ? 'Close Picker' : 'Create Loyal' }}
</a>
@endsection

@section('content')
@include('customer-care.customers._tabs')

{{-- The list's own search — it filters the loyal list on this page. --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" action="{{ route('customer-care.customers.loyal') }}" class="flex flex-wrap items-center gap-3">
        @if($create)
            <input type="hidden" name="create" value="1">
        @endif
        <div class="flex-1 min-w-[200px] relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="{{ old('q', $q) }}"
                placeholder="Search loyal clients by name, phone or whatsapp..."
                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <button type="submit" style="background-color: #F89A1E;" class="px-4 py-2 hover:opacity-90 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-search mr-1"></i> Search
        </button>
        @if($q)
            <a href="{{ route('customer-care.customers.loyal', array_filter(['create' => $create ? 1 : null])) }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-times mr-1"></i> Clear
            </a>
        @endif
    </form>
</div>

@if($create)
{{-- Picker — search the available customers, tick one or more, add them in
     one action. Clients who are already loyal are filtered out server-side. --}}
<div class="bg-white rounded-xl shadow border border-amber-200 p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 class="text-sm font-semibold text-gray-700"><i class="fas fa-star text-amber-500 mr-1"></i> Add loyal customers</h2>
        <span class="text-xs text-gray-400">Already-loyal clients are left out automatically.</span>
    </div>

    <form method="GET" action="{{ route('customer-care.customers.loyal') }}" class="flex flex-wrap items-center gap-3 mb-4">
        <input type="hidden" name="create" value="1">
        <div class="flex-1 min-w-[200px] relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="cq" value="{{ $cq }}"
                placeholder="Search customers by name, phone or whatsapp…"
                class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
        </div>
        <button type="submit" class="px-4 py-2 border rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
            <i class="fas fa-search mr-1"></i> Search
        </button>
    </form>

    <form method="POST" action="{{ route('customer-care.customers.loyal.store') }}">
        @csrf
        @if($candidates->isEmpty())
            <p class="text-sm text-gray-400 py-5 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                @if($cq)
                    No customers match that search — the matches may already be loyal.
                @else
                    Every recent customer is already marked as loyal. Search above to look further back.
                @endif
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="w-10 px-4 py-2"></th>
                        <th class="text-left py-2 px-4">Name</th>
                        <th class="text-left px-4">Phone</th>
                        <th class="text-left px-4">WhatsApp</th>
                        <th class="text-left px-4">Customer since</th>
                    </tr></thead>
                    <tbody>
                        @foreach($candidates as $candidate)
                            <tr class="border-t hover:bg-amber-50 cursor-pointer"
                                onclick="this.querySelector('input[type=checkbox]').click()">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $candidate->id }}"
                                        class="w-4 h-4 rounded text-amber-600" onclick="event.stopPropagation()">
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-700">{{ $candidate->name ?? 'Unnamed' }}</td>
                                <td class="px-4 text-gray-500">{{ $candidate->phone ?? '—' }}</td>
                                <td class="px-4 text-gray-500">{{ $candidate->whatsapp ?? '—' }}</td>
                                <td class="px-4 text-gray-500">
                                    {{ $candidate->created_at ? \Carbon\Carbon::parse($candidate->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t mt-1">
                <span class="text-xs text-gray-400">Tick every regular you want to add — one click adds them all.</span>
                <button type="submit" style="background-color: #F89A1E;" class="px-4 py-2 hover:opacity-90 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-star mr-1"></i> Add selected as Loyal
                </button>
            </div>
        @endif
    </form>
</div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="text-left py-3 px-4">Name</th>
                <th class="text-left px-4">Phone</th>
                <th class="text-left px-4">WhatsApp</th>
                <th class="text-left px-4">Loyal Since</th>
                <th class="text-center px-4">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $row)
                    @php $customer = $row->customer; @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-700">
                                <i class="fas fa-star text-amber-500 text-xs mr-1.5"></i>{{ $customer->name ?? 'Unnamed' }}
                            </div>
                            @if($customer->email)
                                <div class="text-xs text-gray-400">{{ $customer->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 text-gray-500">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 text-gray-500">{{ $customer->whatsapp ?? '—' }}</td>
                        <td class="px-4 text-gray-500">
                            @if($row->assigned_at)
                                <span class="text-gray-600">{{ \Carbon\Carbon::parse($row->assigned_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') }}</span>
                                <span class="text-[10px] text-gray-400 block">{{ \Carbon\Carbon::parse($row->assigned_at)->setTimezone('Africa/Dar_es_Salaam')->diffForHumans() }}</span>
                            @else
                                —
                            @endif
                            @if($row->assigned_by_name)
                                <span class="text-[10px] text-gray-400 block">by {{ $row->assigned_by_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('customer-care.customers.show', $customer->id) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    <i class="fas fa-eye"></i> View Record
                                </a>
                                <form method="POST" action="{{ route('customer-care.customers.loyal.remove', $customer->id) }}"
                                      class="inline" onsubmit="return confirm('Remove this client from loyal customers?')">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                        <i class="fas fa-star-half-alt mr-1"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fas fa-star text-3xl mb-2 block text-amber-300"></i>
                            @if($q)
                                No loyal clients match your search.
                            @else
                                No loyal customers yet — press <b>Create Loyal</b> at the top right to flag your regulars.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->isNotEmpty())
        <div class="px-4 py-3 bg-gray-50 border-t text-xs text-gray-500">
            Showing {{ $rows->count() }} of {{ $loyalCount }} loyal {{ \Illuminate\Support\Str::plural('client', $loyalCount) }}@if($q) matching your search @endif.
        </div>
    @endif
</div>
@endsection
