@extends('layouts.app')
@section('title', 'Customer Care Dashboard')
@section('header', 'Customer Care Dashboard')
@section('subtitle', 'Sales • Orders • Clients')
@section('content')

@if(($pendingOrders ?? 0) > 0)
    <a href="{{ route('customer-care.orders.index', ['status' => 'pending']) }}" class="block mb-8 bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 hover:bg-amber-100 transition flex items-center justify-between gap-3">
        <span class="flex items-center gap-3">
            <span class="w-10 h-10 bg-amber-500 text-white rounded-lg flex items-center justify-center"><i class="fas fa-clock"></i></span>
            <span>
                <span class="block font-semibold text-gray-800">Pending Orders</span>
                <span class="block text-sm text-gray-500">{{ $pendingOrders }} order(s) waiting — go to orders to process them</span>
            </span>
        </span>
        <span class="flex items-center gap-2 text-amber-700 font-bold">{{ $pendingOrders }} <i class="fas fa-arrow-right"></i></span>
    </a>
@endif

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-emerald-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Sales</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalSalesCount }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Revenue</p>
                <p class="text-lg font-semibold text-emerald-700">TZS {{ number_format($totalRevenue) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fa fa-truck text-blue-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $orders->count() }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Value</p>
                <p class="text-lg font-semibold text-blue-700">TZS {{ number_format($ordersTotal) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-purple-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Clients</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $clientsTotal }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">With Phone</p>
                <p class="text-lg font-semibold text-purple-700">{{ $clientsWithPhone }}</p>
            </div>
        </div>
    </div>

    @if($isHq)
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-amber-700 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Inquiries</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $inquiriesCount }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Unread</p>
                <p class="text-lg font-semibold text-red-600">{{ $unreadInquiries }}</p>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    {{-- Today's revenue highlight --}}
    <div class="lg:col-span-3 bg-white rounded-xl shadow p-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-day text-amber-700 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Today’s Sales Revenue</p>
                <p class="text-2xl font-bold text-gray-800">TZS {{ number_format($todayRevenue) }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-6">
            <div class="text-center">
                <p class="text-lg font-semibold text-emerald-700">TZS {{ number_format($dailySummary['daily_sales']) }}</p>
                <p class="text-xs text-gray-500">Daily Sales (Paid)</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold text-red-600">TZS {{ number_format($dailySummary['daily_expenses']) }}</p>
                <p class="text-xs text-gray-500">Daily Expenses</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold {{ ($dailySummary['actual_sales'] ?? 0) >= 0 ? 'text-amber-600' : 'text-red-600' }}">TZS {{ number_format($dailySummary['actual_sales']) }}</p>
                <p class="text-xs text-gray-500">Actual Sales (Sales − Expenses)</p>
            </div>
            <div class="text-sm text-gray-500">
                {{ date('l, F d, Y') }} • {{ date('h:i A') }}
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6">

    {{-- Sales panel --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-semibold"><i class="fas fa-shopping-cart text-emerald-700 mr-2"></i>Sales</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('customer-care.sales.index') }}" class="text-sm text-emerald-700 hover:underline">View All Sales</a>
                <span class="text-xs text-gray-400">|</span>
                <a href="{{ route('customer-care.sales.create') }}" class="text-sm text-emerald-700 hover:underline">New Sale</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4">Sale</th>
                        <th class="text-left px-4">Customer</th>
                        <th class="text-left px-4">Cashier</th>
                        <th class="text-left px-4">Items</th>
                        <th class="text-right px-4">Total</th>
                        <th class="text-left px-4">Status</th>
                        <th class="text-left px-4">Branch</th>
                        <th class="text-left px-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">
                                <a href="{{ route('customer-care.sales.show', $sale->id) }}" class="hover:text-emerald-700">{{ $sale->sale_number ?? 'N/A' }}</a>
                            </td>
                            <td class="px-4 text-gray-700">{{ $sale->customer?->name ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ $sale->cashier?->name ?? '—' }}</td>
                            <td class="px-4">
                                @php
                                    $names = collect($sale->items ?? [])->pluck('product.name')->filter()->values();
                                    $shown = $names->take(2)->implode(', ');
                                    $extra = $names->count() - 2;
                                @endphp
                                @if($shown)
                                    {{ $shown }}@if($extra > 0)<span class="text-xs text-gray-400"> +{{ $extra }}</span>@endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 text-right font-medium text-emerald-700">TZS {{ number_format($sale->total) }}</td>
                            <td class="px-4">
                                <span class="px-2 py-0.5 rounded-full text-xs capitalize
                                    @if(($sale->payment_status ?? '') === 'paid') bg-emerald-100 text-emerald-800
                                    @elseif(($sale->payment_status ?? '') === 'cancelled') bg-red-100 text-red-800
                                    @else bg-amber-100 text-amber-800 @endif">
                                    {{ $sale->payment_status ?? 'pending' }}
                                </span>
                            </td>
                            <td class="px-4 text-gray-500">{{ $sale->branch?->name ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-gray-400">No sales yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Orders panel --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-semibold"><i class="fa fa-truck text-blue-700 mr-2"></i>Orders</h3>
            <a href="{{ route('customer-care.orders.index') }}" class="text-sm text-blue-700 hover:underline">View All Orders</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4">Order</th>
                        <th class="text-left px-4">Customer</th>
                        <th class="text-left px-4">Cashier</th>
                        <th class="text-right px-4">Total</th>
                        <th class="text-center px-4">Status</th>
                        <th class="text-left px-4">Items</th>
                        <th class="text-left px-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">
                                <a href="{{ route('customer-care.orders.show', $order->id) }}" class="hover:text-blue-700">{{ $order->order_number ?? 'N/A' }}</a>
                            </td>
                            <td class="px-4 text-gray-700">{{ $order->customer?->name ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ $order->cashier?->name ?? '—' }}</td>
                            <td class="px-4 text-right font-medium text-blue-700">TZS {{ number_format($order->total) }}</td>
                            <td class="px-4 text-center">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ match($order->status) { 'pending' => 'bg-yellow-100 text-yellow-700', 'assigned' => 'bg-blue-100 text-blue-700', 'ready' => 'bg-green-100 text-green-700', 'completed' => 'bg-purple-100 text-purple-700', 'served' => 'bg-green-100 text-green-800 font-bold', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-gray-100' } }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4">
                                @php
                                    $itemCount = collect($order->items ?? [])->count();
                                @endphp
                                {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}
                            </td>
                            <td class="px-4 text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-400">No orders yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Clients panel --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-semibold"><i class="fas fa-users text-purple-700 mr-2"></i>Clients</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('customer-care.customers.index') }}" class="text-sm text-purple-700 hover:underline">View All Clients</a>
                <span class="text-xs text-gray-400">|</span>
                <a href="{{ route('customer-care.customers.create') }}" class="text-sm text-purple-700 hover:underline">New Client</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4">Name</th>
                        <th class="text-left px-4">Phone</th>
                        <th class="text-left px-4">WhatsApp</th>
                        <th class="text-left px-4">Email</th>
                        <th class="text-left px-4">Total Purchases</th>
                        <th class="text-left px-4">Registered</th>
                        <th class="text-center px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        @php
                            $purchases = $sales->where('customer_id', $client->id)->count();
                        @endphp
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium text-gray-700">{{ $client->name ?? 'Unnamed' }}</td>
                            <td class="px-4 text-gray-500">{{ $client->phone ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ $client->whatsapp ?? '—' }}</td>
                            <td class="px-4 text-gray-500">{{ $client->email ?? '—' }}</td>
                            <td class="px-4 text-gray-700">{{ $purchases }}</td>
                            <td class="px-4 text-gray-500">{{ $client->created_at ? \Carbon\Carbon::parse($client->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y') : '—' }}</td>
                            <td class="px-4 text-center">
                                <a href="{{ route('customer-care.customers.show', $client->id) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-400">No clients yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Inquiries (HQ only) --}}
    @if($isHq)
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-semibold"><i class="fas fa-envelope text-amber-700 mr-2"></i>Recent Inquiries</h3>
            <a href="{{ route('customer-care.inquiries.index') }}" class="text-sm text-amber-700 hover:underline">View All Inquiries</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4">Subject</th>
                        <th class="text-left px-4">From</th>
                        <th class="text-center px-4">Status</th>
                        <th class="text-left px-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInquiries as $i)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <a href="{{ route('customer-care.inquiries.show', $i->id) }}" class="font-medium hover:text-amber-700">{{ $i->subject ?? '—' }}</a>
                            </td>
                            <td class="px-4 text-gray-500">{{ $i->name ?? $i->user?->name ?? 'Customer' }}</td>
                            <td class="px-4 text-center">
                                @if(($i->is_read ?? false))
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Read</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-600">Unread</span>
                                @endif
                            </td>
                            <td class="px-4 text-xs text-gray-500">{{ $i->created_at ? \Carbon\Carbon::parse($i->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-gray-400">No inquiries yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
