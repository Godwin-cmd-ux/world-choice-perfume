{{--
    Theme toggle — the dark/light switch.

    Include once per layout (or once per header, plus once in a mobile menu):
    every `[data-theme-toggle]` button on the page is wired by one script, and
    all of them repaint together. The script is emitted once (@once), at the
    first include — usually the desktop header — but it delegates clicks from
    the document, so a switch that appears later in the markup (the phone's,
    inside the collapsed mobile menu) is still live.

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
                document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                    button.setAttribute('aria-label', 'Switch to ' + next.toLowerCase());
                });
            }

            function flip() {
                var next = current() === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try {
                    window.localStorage.setItem(KEY, next);
                } catch (error) {
                    /* storage unavailable: the switch still works for this page */
                }
                paint();
            }

            /* One delegated listener for every switch on the page, present or
               future. Binding per button only wired the switches that were in
               the DOM when this script ran — the public layout emits the desktop
               one first and the phone's, inside the collapsed mobile menu, much
               later — which left the mobile switch dead. */
            document.addEventListener('click', function (event) {
                var target = event.target;
                var button = target && target.closest ? target.closest('[data-theme-toggle]') : null;
                if (!button) {
                    return;
                }
                event.preventDefault();
                flip();
            });

            /* The icon and label are painted once, here and again when the rest
               of the markup (the mobile menu) has been parsed. */
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', paint);
            }
            paint();
        })();
    </script>
@endonce
