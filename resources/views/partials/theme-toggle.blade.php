{{--
    Theme toggle — the dark/light switch.

    Include once per layout (or once per header, plus once in a mobile menu):
    every `[data-theme-toggle]` button on the page is wired by one script, and
    all of them repaint together.

    Options:
        @include('partials.theme-toggle', ['variant' => 'gold'])    // dark chrome (public nav)
        @include('partials.theme-toggle')                           // neutral (staff header)

    It reads and writes the same localStorage key as
    partials/theme-boot.blade.php ('wcp-theme'), so the choice survives
    navigation and the next page paints in the right mode before it appears.
--}}
@php
    $themeToggleGold = ($variant ?? 'neutral') === 'gold';
@endphp
<button type="button" data-theme-toggle
        title="Switch between dark and light mode"
        aria-label="Switch between dark and light mode"
        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition
               {{ $themeToggleGold
                    ? 'border-gold-500/30 text-gold-400 hover:bg-gold-500/10'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <i data-theme-icon class="fas fa-moon" aria-hidden="true"></i>
    <span data-theme-label class="hidden sm:inline">Dark mode</span>
</button>

@once
    <script>
        (function () {
            var KEY = 'wcp-theme';
            var root = document.documentElement;
            var buttons = document.querySelectorAll('[data-theme-toggle]');
            if (!buttons.length) {
                return;
            }

            function current() {
                return root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            }

            /* Every toggle on the page shows the mode that is running, and names
               the one a click would switch to. */
            function paint() {
                var theme = current();
                var next = theme === 'dark' ? 'Light mode' : 'Dark mode';
                document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
                    icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
                });
                document.querySelectorAll('[data-theme-label]').forEach(function (label) {
                    label.textContent = theme === 'dark' ? 'Dark mode' : 'Light mode';
                });
                buttons.forEach(function (button) {
                    button.setAttribute('aria-label', 'Switch to ' + next.toLowerCase());
                });
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var next = current() === 'dark' ? 'light' : 'dark';
                    root.setAttribute('data-theme', next);
                    try {
                        window.localStorage.setItem(KEY, next);
                    } catch (error) {
                        /* storage unavailable: the switch still works for this page */
                    }
                    paint();
                });
            });

            paint();
        })();
    </script>
@endonce
