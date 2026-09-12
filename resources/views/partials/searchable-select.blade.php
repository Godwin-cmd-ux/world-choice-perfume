{{-- Searchable dropdown select component --}}
@php
    $selectedValue = $selected ?? old($name, '');
    $selectedLabel = '';
    if ($selectedValue !== '') {
        foreach ($options as $opt) {
            if ((string) $opt['value'] === (string) $selectedValue) {
                $selectedLabel = $opt['label'];
                break;
            }
        }
    }
@endphp

<div class="relative" data-searchable-select>
    <input type="hidden" name="{{ $name }}" value="{{ $selectedValue }}" data-ss-hidden required>

    <button type="button" data-ss-trigger
        class="w-full flex items-center justify-between gap-2 px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-sm text-left focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 {{ $selectedLabel ? 'text-gray-800' : 'text-gray-400' }}">
        <span data-ss-label>{{ $selectedLabel ?: $placeholder }}</span>
        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
    </button>

    <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden" data-ss-dropdown>
        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Type to search..." data-ss-search autocomplete="off"
                    class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
        <ul data-ss-list class="max-h-64 overflow-y-auto py-1">
            @forelse($options as $opt)
                <li>
                    <button type="button" data-ss-option="{{ $opt['value'] }}" data-ss-label="{{ $opt['label'] }}" data-ss-search="{{ strtolower($opt['label']) }}"
                        class="w-full text-left px-4 py-2 text-sm transition {{ (string) $opt['value'] === (string) $selectedValue ? 'bg-emerald-50 text-emerald-800 font-medium' : 'text-gray-700 hover:bg-emerald-50' }}">
                        {{ $opt['label'] }}
                    </button>
                </li>
            @empty
                <li><p class="px-4 py-3 text-sm text-gray-400">No options available.</p></li>
            @endforelse
        </ul>
    </div>

    @if(!empty($error))
        <p class="text-xs text-red-600 mt-1">{{ $error }}</p>
    @endif
</div>

<script>
(function () {
    if (window._searchableSelectBound) return;
    window._searchableSelectBound = true;

    function closeAll(except) {
        document.querySelectorAll('[data-searchable-select]').forEach(function (box) {
            if (box === except) return;
            box.querySelector('[data-ss-dropdown]').classList.add('hidden');
        });
    }

    document.addEventListener('click', function (e) {
        var box = e.target.closest('[data-searchable-select]');
        if (!box) {
            closeAll();
            return;
        }
        var dropdown = box.querySelector('[data-ss-dropdown]');
        var search = box.querySelector('[data-ss-search]');

        if (e.target.closest('[data-ss-trigger]') && box.contains(e.target.closest('[data-ss-trigger]'))) {
            var isOpen = !dropdown.classList.contains('hidden');
            closeAll();
            if (!isOpen) {
                dropdown.classList.remove('hidden');
                search.value = '';
                box.querySelectorAll('[data-ss-option]').forEach(function (o) { o.style.display = ''; });
                search.focus();
            }
            e.stopPropagation();
            return;
        }

        if (e.target.closest('[data-ss-option]') && box.contains(e.target.closest('[data-ss-option]'))) {
            var option = e.target.closest('[data-ss-option]');
            var value = option.getAttribute('data-ss-option');
            var label = option.getAttribute('data-ss-label');
            box.querySelector('[data-ss-hidden]').value = value;
            var labelEl = box.querySelector('[data-ss-label]');
            labelEl.textContent = label;
            box.querySelector('[data-ss-trigger]').classList.add('text-gray-800');
            box.querySelector('[data-ss-trigger]').classList.remove('text-gray-400');
            box.querySelectorAll('[data-ss-option]').forEach(function (o) {
                o.classList.remove('bg-emerald-50', 'text-emerald-800', 'font-medium');
                o.classList.add('text-gray-700', 'hover:bg-emerald-50');
            });
            option.classList.add('bg-emerald-50', 'text-emerald-800', 'font-medium');
            dropdown.classList.add('hidden');
            e.stopPropagation();
        }
    });

    document.addEventListener('input', function (e) {
        var search = e.target.closest('[data-ss-search]');
        if (!search) return;
        var box = search.closest('[data-searchable-select]');
        var q = search.value.toLowerCase().trim();
        box.querySelectorAll('[data-ss-option]').forEach(function (o) {
            var hay = o.getAttribute('data-ss-search') || '';
            o.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });
})();
</script>