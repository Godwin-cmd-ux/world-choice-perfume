{{-- Shared monochrome theme (deep shiny black + white) — MGFlow-inspired, overrides all chromatic Tailwind palettes --}}
<script>
    (function () {
        var mono = {
            '50': '#F7F7F7',
            '100': '#EEEEEE',
            '200': '#E4E4E4',
            '300': '#CFCFCF',
            '400': '#9C9C9C',
            '500': '#4A4A4A',
            '600': '#111111',
            '700': '#111111',
            '800': '#0A0A0A',
            '900': '#000000',
            '950': '#000000'
        };
        var palettes = ['green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'red', 'orange', 'amber', 'yellow', 'lime'];
        var colors = {};
        palettes.forEach(function (p) { colors[p] = Object.assign({ DEFAULT: '#111111' }, mono); });

        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    },
                    colors: colors
                }
            }
        };
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body {
        font-size: 14px;
        line-height: 20px;
        color: #111111;
    }

    /* Deep shiny black: glossy vertical sheen on the dark button shades */
    .bg-green-600, .bg-green-700, .bg-emerald-600, .bg-emerald-700, .bg-teal-600, .bg-teal-700,
    .bg-cyan-600, .bg-cyan-700, .bg-sky-600, .bg-sky-700, .bg-blue-600, .bg-blue-700,
    .bg-indigo-600, .bg-indigo-700, .bg-violet-600, .bg-violet-700, .bg-purple-600, .bg-purple-700,
    .bg-fuchsia-600, .bg-fuchsia-700, .bg-pink-600, .bg-pink-700, .bg-rose-600, .bg-rose-700,
    .bg-red-600, .bg-red-700, .bg-orange-600, .bg-orange-700, .bg-amber-600, .bg-amber-700,
    .bg-yellow-600, .bg-yellow-700, .bg-lime-600, .bg-lime-700 {
        background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.02) 45%, rgba(0, 0, 0, 0.18) 100%);
    }

    /* Black/white scrollbars */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: #F7F7F7; }
    ::-webkit-scrollbar-thumb { background: #CFCFCF; border-radius: 6px; }
    ::-webkit-scrollbar-thumb:hover { background: #111111; }

    ::selection { background: #111111; color: #FFFFFF; }

    textarea, input, select { color-scheme: light; }
</style>