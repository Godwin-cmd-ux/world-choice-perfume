{{--
    Swipe between the pre-login pages.

    On a phone the public site's destinations behave like tabs: Home, Shop,
    Track Order, Contact, News, Staff. This turns a horizontal swipe into the
    same move a tap on the tab makes, in both directions, in the order those
    tabs are listed — finger to the left reveals the next one, finger to the
    right goes back.

    What it deliberately does NOT take over:

      * a page with a dialog open — the staff-code modal owns the screen, and
        its own touch handling must not be interrupted;
      * anything horizontally scrollable under the finger (the shop's filter
        strip, a thumbnail gallery). That gesture already means "scroll this
        strip", and stealing it is what makes swipe navigation feel broken;
      * form fields and existing text selections — a drag across an input is
        the visitor selecting what they typed;
      * single-axis drags that are really scrolls: the gesture only counts when
        it is mostly horizontal, long enough, and quick. Diagonal and slow
        drags are left to the browser.

    The next page is prefetched as soon as the direction is decided, so the
    navigation lands on an already-fetched document instead of a spinner.
--}}
@php
    // The pre-login destinations, in tab order. `current` says which one the
    // visitor is standing on; a page that is not one of the six (a brand page,
    // Twende Dukani) leaves every entry false, and swiping does nothing there
    // rather than guessing where the visitor "should" have come from.
    $swipeTabs = [
        [
            'label' => 'Home',
            'url' => route('home'),
            'current' => request()->routeIs('home'),
        ],
        [
            'label' => 'Shop',
            'url' => route('customer.products.index'),
            'current' => request()->routeIs('customer.products.*'),
        ],
        [
            'label' => 'Track Order',
            'url' => route('customer.orders.track'),
            'current' => request()->routeIs('customer.orders.track*'),
        ],
        [
            'label' => 'Contact',
            'url' => route('home').'#contact',
            'current' => false,
        ],
        [
            'label' => 'News',
            'url' => route('customer.news'),
            'current' => request()->routeIs('customer.news'),
        ],
        [
            'label' => 'Staff',
            'url' => route('login'),
            'current' => request()->routeIs('login'),
        ],
    ];

    $swipeIndex = -1;
    foreach ($swipeTabs as $i => $tab) {
        if ($tab['current']) {
            $swipeIndex = $i;
            break;
        }
    }

    // Encoded quote-free (HEX flags) so the JSON can sit in an HTML attribute
    // without a single character of it needing to be escaped — and so what the
    // page ships is exactly what JSON.parse reads back.
    $swipeTabsJson = json_encode(
        array_map(fn ($tab) => ['label' => $tab['label'], 'url' => $tab['url']], $swipeTabs),
        JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp

<div data-swipe-nav data-swipe-index="{{ $swipeIndex }}" data-swipe-tabs='{{ $swipeTabsJson }}' hidden></div>

<script>
    (function () {
        var holder = document.querySelector('[data-swipe-nav]');
        if (!holder) {
            return;
        }

        var index = parseInt(holder.getAttribute('data-swipe-index'), 10);
        var tabs = [];

        try {
            tabs = JSON.parse(holder.getAttribute('data-swipe-tabs')) || [];
        } catch (error) {
            tabs = [];
        }

        // A page outside the tab set has nowhere to swipe to, and a device
        // without touch never fires the events this listens for.
        if (isNaN(index) || index < 0 || tabs.length < 2) {
            return;
        }

        var MIN_DISTANCE = 60;   // px of travel before a gesture counts
        var MAX_OFF_AXIS = 0.5;  // vertical drift allowed, as a share of travel
        var MAX_DURATION = 900;  // a slower drag is a scroll, not a flick

        var startX = 0;
        var startY = 0;
        var startTime = 0;
        var travelX = 0;
        var travelY = 0;
        var tracking = false;
        var prefetchedUrl = null;

        function visible(element) {
            var style = window.getComputedStyle(element);
            if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) === 0) {
                return false;
            }
            var rect = element.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }

        function dialogOpen() {
            var staffModal = document.getElementById('staffLoginModal');
            if (staffModal && !staffModal.classList.contains('hidden')) {
                return true;
            }
            var blockers = document.querySelectorAll('[data-swipe-block]');
            for (var i = 0; i < blockers.length; i++) {
                if (visible(blockers[i])) {
                    return true;
                }
            }
            return false;
        }

        /* A strip that scrolls sideways under the finger, or that has scrolled,
           owns the gesture: the swipes we are adding are for the page itself. */
        function scrollsSideways(element) {
            for (var node = element; node && node !== document.body; node = node.parentElement) {
                var style = window.getComputedStyle(node);
                if ((style.overflowX === 'auto' || style.overflowX === 'scroll')
                    && node.scrollWidth > node.clientWidth + 4) {
                    return true;
                }
            }
            return false;
        }

        function ignoresGesture(target) {
            if (!target || !target.closest) {
                return false;
            }
            if (target.closest('input, textarea, select, [contenteditable="true"], [data-no-swipe]')) {
                return true;
            }
            return scrollsSideways(target);
        }

        function tabAt(step) {
            var next = index + step;
            return (next < 0 || next >= tabs.length) ? null : tabs[next];
        }

        /* The browser starts fetching the moment the direction is clear, so the
           release lands on a document that is already on its way. */
        function prefetch(tab) {
            if (!tab || tab.url === prefetchedUrl) {
                return;
            }
            prefetchedUrl = tab.url;
            var link = document.createElement('link');
            link.rel = 'prefetch';
            link.setAttribute('as', 'document');
            link.href = tab.url;
            document.head.appendChild(link);
        }

        document.addEventListener('touchstart', function (event) {
            tracking = false;

            if (event.touches.length !== 1 || dialogOpen()) {
                return;
            }
            if (window.getSelection && String(window.getSelection())) {
                return;
            }

            var touch = event.touches[0];
            if (ignoresGesture(event.target)) {
                return;
            }

            tracking = true;
            startX = touch.clientX;
            startY = touch.clientY;
            startTime = Date.now();
            travelX = 0;
            travelY = 0;
            prefetchedUrl = null;
        }, { passive: true });

        document.addEventListener('touchmove', function (event) {
            if (!tracking || event.touches.length !== 1) {
                return;
            }

            var touch = event.touches[0];
            travelX = touch.clientX - startX;
            travelY = touch.clientY - startY;

            if (Math.abs(travelX) < MIN_DISTANCE || Math.abs(travelY) > Math.abs(travelX) * MAX_OFF_AXIS) {
                return;
            }

            // Finger left = next tab, finger right = previous.
            prefetch(tabAt(travelX < 0 ? 1 : -1));
        }, { passive: true });

        document.addEventListener('touchend', function () {
            if (!tracking) {
                return;
            }
            tracking = false;

            if (Math.abs(travelX) < MIN_DISTANCE || Math.abs(travelY) > Math.abs(travelX) * MAX_OFF_AXIS) {
                return;
            }
            if (Date.now() - startTime > MAX_DURATION) {
                return;
            }

            var tab = tabAt(travelX < 0 ? 1 : -1);
            if (!tab) {
                return;  // already on the first or the last tab
            }

            window.location.href = tab.url;
        }, { passive: true });

        // A gesture that turns into a page scroll (or a browser gesture) is not
        // a swipe any more — drop it rather than navigating on release.
        document.addEventListener('touchcancel', function () {
            tracking = false;
        }, { passive: true });
    })();
</script>
