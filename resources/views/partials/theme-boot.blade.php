{{--
    Theme bootstrap — MUST be the first thing in <head>, before any stylesheet.

    Dark is the default for the whole website (unlike the mobile app, which
    defaults to light). The saved choice lives in localStorage; the attribute is
    written before the first paint so there is never a flash of the wrong theme
    and no re-render delay. Kept dependency-free and wrapped in try/catch so a
    browser with storage disabled still lands on the dark default.

    The key is shared with the toggle (partials/theme-toggle.blade.php):
    wcp-theme = 'dark' | 'light'.
--}}
<script>
    (function () {
        var KEY = 'wcp-theme';
        var theme = 'dark'; // website default
        try {
            var saved = window.localStorage.getItem(KEY);
            if (saved === 'light' || saved === 'dark') {
                theme = saved;
            }
        } catch (error) {
            /* storage unavailable (private mode / blocked cookies): keep the default */
        }
        document.documentElement.setAttribute('data-theme', theme);
    })();
</script>
