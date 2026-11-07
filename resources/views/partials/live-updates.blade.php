{{-- Live counts: the badges are server-rendered first, then this script
     refreshes them and reloads list/dashboard pages when their own module
     receives new data. --}}
<div id="live-updates"
     data-url="{{ route('live.nav-counts') }}"
     data-keys="{{ implode(',', $liveKeys ?? []) }}"
     data-counts='@json($navCounts ?? [])'
     data-refresh="{{ ($liveRefresh ?? false) ? '1' : '0' }}"
     hidden></div>
<script src="{{ asset('js/live-updates.js') }}?v=1" defer></script>
