{{--
    The three Orders tabs, shared by every role that owns an Orders page.

    Expects:
      $tabRoute   route name for the index, e.g. 'cashier.orders.index'
      $tab        the active tab slug: pending | progress | completed
      $counts     counts keyed by the same three slugs

    The labels are read from the workflow service so the wording cannot drift
    between roles: Pending Orders / My Orders On Progress / My Completed Orders.

    Any other query string already on the URL is carried across, so a search or
    a filter stays applied while switching tabs.
    Optional:
      $tabLabels   override the labels, keyed by slug. Super Admin uses this
                   because it is not isolated, so "My Orders On Progress" would
                   be a lie on a tab that lists every staff member's orders.
--}}
@php
    $tabIcons = [
        'pending' => 'fa-clock',
        'progress' => 'fa-box-open',
        'completed' => 'fa-circle-check',
    ];
    $tabLabels = $tabLabels ?? \App\Services\OrderWorkflowService::TAB_LABELS;
    $keepQuery = request()->except('tab');
@endphp

<nav aria-label="Order status tabs" class="border-b border-gray-200 mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="flex items-center gap-1 min-w-max">
        @foreach(array_keys(\App\Services\OrderWorkflowService::TABS) as $slug)
            @php
                $active = $tab === $slug;
                $label = $tabLabels[$slug];
            @endphp
            <a href="{{ route($tabRoute, array_merge($keepQuery, ['tab' => $slug])) }}"
               @if($active) aria-current="page" @endif
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $active ? 'border-amber-600 text-amber-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas {{ $tabIcons[$slug] ?? 'fa-list' }} mr-1"></i>{{ $label }}
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $active ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $counts[$slug] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
</nav>
