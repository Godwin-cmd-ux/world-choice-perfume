@extends('layouts.app')
@section('title', 'Client Record — ' . ($customer->name ?? 'Client'))
@section('header', 'Client Record')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Contact details --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4 mb-4">
                @if($customer->name)
                    <div class="w-12 h-12 rounded-full bg-amber-600 text-white flex items-center justify-center text-lg font-bold">
                        {{ substr($customer->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $customer->name ?? 'Unnamed Client' }}</h3>
                    <p class="text-xs text-gray-400">Customer ID: {{ $customer->id }}</p>
                </div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex items-start gap-3">
                    <i class="fas fa-phone text-gray-400 mt-0.5"></i>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Phone</p>
                        <p class="font-medium">{{ $customer->phone ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fab fa-whatsapp text-gray-400 mt-0.5"></i>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">WhatsApp</p>
                        <p class="font-medium">{{ $customer->whatsapp ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-envelope text-gray-400 mt-0.5"></i>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Email</p>
                        <p class="font-medium">{{ $customer->email ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-calendar-plus text-gray-400 mt-0.5"></i>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">Registered</p>
                        <p class="font-medium">{{ $customer->created_at ? \Carbon\Carbon::parse($customer->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold text-sm mb-1"><i class="fas fa-receipt mr-1 text-amber-600"></i> Summary</h3>
            <p class="text-[10px] text-gray-400 mb-4">{{ $selectedName }}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Transactions</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $transactions->count() }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Total Spent</p>
                    <p class="text-lg font-semibold text-green-600">{{ number_format($totalSpent) }}</p>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('customer-care.customers.index') }}"
               class="flex-1 text-center px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="{{ route('customer-care.sales.create') }}"
               class="flex-1 text-center px-4 py-2 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-shopping-cart mr-1"></i> New Sale
            </a>
        </div>
    </div>

    {{-- Transactions for the selected branch --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Branch switcher: only branches this client actually transacted at --}}
        @if($branchTabs)
            <div class="bg-white rounded-xl shadow px-4 pt-4 pb-0">
                <h3 class="font-semibold text-sm mb-1"><i class="fas fa-map-marker-alt mr-1 text-amber-600"></i> Transactions by Branch</h3>
                <p class="text-[10px] text-gray-400 mb-3">Pick a branch to see what this client bought there.</p>
                <div class="flex items-center gap-1 border-b border-gray-200 -mb-px overflow-x-auto">
                    <a href="{{ route('customer-care.customers.show', $customer->id) }}?branch=all"
                       class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $selected === 'all' ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <i class="fas fa-store mr-1"></i> All Branches
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $selected === 'all' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $branchTabs->sum('count') }}</span>
                    </a>
                    @foreach($branchTabs as $tab)
                        <a href="{{ route('customer-care.customers.show', $customer->id) }}?branch={{ $tab->id }}"
                           class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $selected === (string) $tab->id ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                           title="Last visit {{ $tab->last_visit ? \Carbon\Carbon::parse($tab->last_visit)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}">
                            {{ $tab->name }}
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $selected === (string) $tab->id ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $tab->count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-sm"><i class="fas fa-box-open mr-1 text-amber-600"></i> Items Purchased</h3>
                    <p class="text-[10px] text-gray-400">Every product this client has bought at {{ $selectedName }}.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="text-left py-3 px-4">Item</th>
                        <th class="text-left px-4">Brand</th>
                        <th class="text-center px-4">Times Bought</th>
                        <th class="text-right px-4">Qty</th>
                        <th class="text-right px-4">Total Spent</th>
                        <th class="text-left px-4">Last Bought</th>
                    </tr></thead>
                    <tbody>
                        @forelse($itemSummary as $item)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium text-gray-700">{{ $item->name }}</td>
                                <td class="px-4 text-gray-500">{{ $item->brand ?? '—' }}</td>
                                <td class="px-4 text-center text-gray-600">{{ $item->times }}</td>
                                <td class="px-4 text-right text-gray-600">{{ $item->quantity }}</td>
                                <td class="px-4 text-right font-medium text-green-600">TZS {{ number_format($item->spent) }}</td>
                                <td class="px-4 text-gray-500">
                                    {{ $item->last_bought ? \Carbon\Carbon::parse($item->last_bought)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-gray-400">No items purchased here yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-sm"><i class="fas fa-receipt mr-1 text-amber-600"></i> Transaction History</h3>
                <p class="text-[10px] text-gray-400">Oldest first at the top, latest at the bottom{{ $branchTabs ? ' — ' . $selectedName : '' }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="text-left py-3 px-4">#</th>
                        <th class="text-left px-4">Sale #</th>
                        <th class="text-left px-4">Items</th>
                        @if($selected === 'all')
                            <th class="text-left px-4">Branch</th>
                        @endif
                        <th class="text-center px-4">Type</th>
                        <th class="text-right px-4">Total</th>
                        <th class="text-left px-4">Date</th>
                    </tr></thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            @php
                                $itemLine = collect($txn->items)->map(fn ($i) => $i->name . ' ×' . $i->quantity);
                                $extraItems = max(collect($txn->items)->count() - 3, 0);
                            @endphp
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 text-xs text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-4 font-medium">
                                    @if($txn->branch_id === (int) (auth()->user()->branch_id ?? 0))
                                        <a href="{{ route('customer-care.sales.show', $txn->id) }}"
                                           class="text-blue-600 hover:text-blue-800 underline">{{ $txn->sale_number }}</a>
                                    @else
                                        <span class="text-gray-700">{{ $txn->sale_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 text-gray-600">
                                    {{ $itemLine->take(3)->implode(', ') ?: '—' }}
                                    @if($extraItems > 0)<span class="text-xs text-gray-400"> +{{ $extraItems }} more</span>@endif
                                </td>
                                @if($selected === 'all')
                                    <td class="px-4 text-gray-500">{{ $txn->branch_name ?? '—' }}</td>
                                @endif
                                <td class="px-4 text-center">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full {{ ($txn->sale_type ?? 'walk-in') === 'online' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ ucfirst($txn->sale_type ?? 'walk-in') }}
                                    </span>
                                </td>
                                <td class="px-4 text-right font-medium text-green-600">TZS {{ number_format($txn->total) }}</td>
                                <td class="px-4 text-gray-500">
                                    <span class="text-gray-600">{{ \Carbon\Carbon::parse($txn->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</span>
                                    <span class="text-[10px] text-gray-400 block">{{ \Carbon\Carbon::parse($txn->created_at)->setTimezone('Africa/Dar_es_Salaam')->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <i class="fas fa-receipt text-3xl mb-2 block"></i>
                                    No transactions{{ $branchTabs ? ' at ' . $selectedName : '' }} yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->isNotEmpty())
                <div class="px-4 py-3 bg-gray-50 border-t text-xs text-gray-500 flex items-center justify-between">
                    <span>{{ $transactions->count() }} {{ \Illuminate\Support\Str::plural('transaction', $transactions->count()) }} — oldest first</span>
                    <span class="font-medium text-green-600">TZS {{ number_format($totalSpent) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
