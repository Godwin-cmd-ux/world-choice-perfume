{{-- Live search: a GET filter form answers while the user types.
     Typing is debounced, then the form submits itself; after the reload the
     same field is focused again with the caret at the end, so the person can
     keep typing without clicking back into the box. Forms that already filter
     in the browser (the sale picker, QR product list) are untouched — they
     never submit. --}}
<script>
(function () {
    'use strict';

    var SEARCH_FIELDS = ['q', 'search', 'cq'];
    var DELAY = 450;
    var FOCUS_KEY = 'wcp-live-search-focus';

    function searchInputIn(form) {
        var inputs = form.querySelectorAll('input[type="search"], input[type="text"]');
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.disabled || input.readOnly) continue;
            if (input.type === 'search' || SEARCH_FIELDS.indexOf(input.name) !== -1) return input;
        }
        return null;
    }

    function rememberFocus(input) {
        try {
            sessionStorage.setItem(FOCUS_KEY, JSON.stringify({
                field: input.name,
                path: window.location.pathname,
                at: Date.now()
            }));
        } catch (e) { /* private mode — the search still runs, focus is not restored */ }
    }

    Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
        var input = searchInputIn(form);
        if (!input) return;

        var timer = null;
        var lastSubmitted = input.value;

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                // Only search when the text actually changed, so regaining
                // focus or moving the caret never triggers a request.
                if (input.value === lastSubmitted) return;
                lastSubmitted = input.value;
                rememberFocus(input);
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }, DELAY);
        });
    });

    // Restore the caret after an auto-submitted search reloaded the page.
    document.addEventListener('DOMContentLoaded', function () {
        var saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(FOCUS_KEY) || 'null');
            sessionStorage.removeItem(FOCUS_KEY);
        } catch (e) { return; }
        if (!saved || saved.path !== window.location.pathname || !saved.field) return;

        var input = document.querySelector('input[name="' + saved.field + '"]');
        if (!input) return;
        input.focus();
        var end = input.value.length;
        try { input.setSelectionRange(end, end); } catch (e) { /* type=search in old WebKit */ }
    });
})();
</script>
