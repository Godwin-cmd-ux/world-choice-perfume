{{--
    Theme bootstrap — MUST be the first thing in <head>, before any stylesheet.

    Dark is the default for the whole website (unlike the mobile app, which
    defaults to light). Public pages can pass forceDark=true to ignore any saved
    theme preference and stay dark. Otherwise, the saved choice lives in
    localStorage. The attribute is written before first paint to avoid a flash
    of the wrong theme. Kept dependency-free and safe when storage is disabled.

    The key is shared with the toggle (partials/theme-toggle.blade.php):
    wcp-theme = 'dark' | 'light'.
    Pass ['forceDark' => true] when the page must always use the dark theme.
--}}
<script>
    (function () {
        var KEY = 'wcp-theme';
        var theme = 'dark'; // website default
        if (!@json((bool) ($forceDark ?? false))) {
            try {
                var saved = window.localStorage.getItem(KEY);
                if (saved === 'light' || saved === 'dark') {
                    theme = saved;
                }
            } catch (error) {
                /* storage unavailable (private mode / blocked cookies): keep the default */
            }
        }
        document.documentElement.setAttribute('data-theme', theme);
    })();
</script>
