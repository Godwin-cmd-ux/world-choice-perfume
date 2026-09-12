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

    {{-- Visits per branch + purchases --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-sm"><i class="fas fa-map-marker-alt mr-1 text-amber-600"></i> Last Visit by Branch</h3>
                <p class="text-[10px] text-gray-400">Most recent date this client appeared at each branch (from walk-in sales).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="text-left py-3 px-4">Branch</th>
                        <th class="text-left px-4">Location</th>
                        <th class="text-left px-4">Last Visit</th>
                    </tr></thead>
                    <tbody>
                        @forelse($branchVisits as $visit)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium text-gray-700">
                                    <i class="fas fa-store mr-1 text-gray-400"></i>{{ $visit->branch->name }}
                                </td>
                                <td class="px-4 text-gray-500">{{ $visit->branch->address ?? '—' }}</td>
                                <td class="px-4">
                                    @if($visit->last_visit)
                                        <span class="text-gray-600">{{ \Carbon\Carbon::parse($visit->last_visit)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') }}</span>
                                        <span class="text-[10px] text-gray-400 block">{{ \Carbon\Carbon::parse($visit->last_visit)->setTimezone('Africa/Dar_es_Salaam')->diffForHumans() }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-gray-400">No walk-in visits recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="font-semibold text-sm"><i class="fas fa-receipt mr-1 text-amber-600"></i> Recent Purchases</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="text-left py-3 px-4">Sale #</th>
                        <th class="text-left px-4">Branch</th>
                        <th class="text-right px-4">Total</th>
                        <th class="text-left px-4">Date</th>
                    </tr></thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium text-gray-700">{{ $sale->sale_number }}</td>
                                <td class="px-4 text-gray-500">{{ $sale->branch_name ?? '—' }}</td>
                                <td class="px-4 text-right font-medium text-green-600">TZS {{ number_format($sale->total) }}</td>
                                <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-gray-400">No purchases recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection