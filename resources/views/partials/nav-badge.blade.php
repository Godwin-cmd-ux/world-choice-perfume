@php
    $badgeCount = (int) (($navCounts[$key] ?? 0));
@endphp
<span data-nav-count="{{ $key }}"
      class="ml-auto {{ $class ?? 'bg-amber-500' }} text-white text-[10px] font-bold px-2 py-0.5 rounded-full {{ $badgeCount > 0 ? '' : 'hidden' }}"
      aria-label="{{ $badgeCount }} new">{{ $badgeCount }}</span>
