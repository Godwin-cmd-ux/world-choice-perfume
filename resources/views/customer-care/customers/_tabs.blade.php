{{--}}
  Branch tabs of the client records screen — shared by the Clients tab
  (customers/index.blade.php) and the Loyal tab (customers/loyal.blade.php),
  so the strip sits in the same place and both tabs read as one screen.

  Expects: $q, $tab (0 = All Customers), $tabs, $counts, $totalCustomers,
           $loyalCount, $view ('all' | 'loyal')
  Branch links always lead back to the Clients tab; the Loyal link is the
  only one that carries $view.
*--}}
@php $showCounts = $counts !== []; @endphp

<div class="border-b border-gray-200 mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="flex items-center gap-1 min-w-max">
        <a href="{{ route('customer-care.customers.index', array_filter(['q' => $q])) }}"
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $view === 'all' && $tab === 0 ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-users mr-1"></i> All Customers
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $view === 'all' && $tab === 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $totalCustomers }}</span>
        </a>
        @foreach($tabs as $t)
            <a href="{{ route('customer-care.customers.index', array_filter(['q' => $q, 'branch' => $t->id])) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $view === 'all' && $tab === $t->id ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
               @if($showCounts) title="{{ $t->count }} {{ \Illuminate\Support\Str::plural('client', $t->count) }} with transactions at this branch" @endif>
                <i class="fas fa-store mr-1"></i> {{ $t->name }}
                @if($showCounts)
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $view === 'all' && $tab === $t->id ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $t->count }}</span>
                @endif
            </a>
        @endforeach
        {{-- The new tab: every client customer care has flagged as loyal. --}}
        <a href="{{ route('customer-care.customers.loyal', array_filter(['q' => $q])) }}"
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $view === 'loyal' ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-star mr-1"></i> Loyal Customers
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $view === 'loyal' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $loyalCount }}</span>
        </a>
    </div>
</div>
