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

{{-- Refined competitive admin skin : 100% monochrome, polished surfaces --}}
<style>
    h1, h2, h3, h4, h5 { letter-spacing: -0.01em; }

    ::placeholder { color: #9AA0A6; opacity: 1; }

    /* ---- Page backdrop & main layout shell ---- */
    body.bg-gray-50, body.bg-gray-100 { background-color: #F2F3F5; }

    .main-content > header {
        background: linear-gradient(180deg, #FFFFFF 0%, #FBFBFC 100%);
        border-bottom: 1px solid #E7E8EA;
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02), 0 2px 6px rgba(0, 0, 0, 0.04);
    }
    .main-content > header h1,
    .main-content > header h2 { font-weight: 600; color: #111111; }

    @media (max-width: 1024px) {
        .main-content > header { position: sticky; top: 0; z-index: 30; }
    }

    /* ---- Sidebar : deep shiny black, crisp white active pill ---- */
    #sidebar {
        background-image: linear-gradient(180deg, #161616 0%, #0E0E0E 45%, #050505 100%);
        box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.06);
    }
    #sidebar .border-t, #sidebar .border-b { border-color: rgba(255, 255, 255, 0.08); }
    #sidebar nav a { position: relative; }
    #sidebar nav a.bg-amber-600,
    #sidebar nav a.bg-emerald-600,
    #sidebar nav a.bg-blue-600,
    #sidebar nav a.bg-purple-600,
    #sidebar nav a.bg-green-600,
    #sidebar nav a.bg-cyan-600 {
        background: #FFFFFF;
        color: #111111;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45);
    }
    #sidebar nav a.bg-amber-600 i,
    #sidebar nav a.bg-emerald-600 i,
    #sidebar nav a.bg-blue-600 i,
    #sidebar nav a.bg-purple-600 i,
    #sidebar nav a.bg-green-600 i,
    #sidebar nav a.bg-cyan-600 i { color: #111111; }
    #sidebar nav a.bg-amber-600::before,
    #sidebar nav a.bg-emerald-600::before,
    #sidebar nav a.bg-blue-600::before,
    #sidebar nav a.bg-purple-600::before {
        content: '';
        position: absolute;
        left: 0;
        top: 25%;
        bottom: 25%;
        width: 3px;
        border-radius: 0 2px 2px 0;
        background: #111111;
    }

    /* ---- Cards : refined edge, soft elevation ---- */
    .rounded-xl.border.border-gray-200 {
        border-color: #E7E8EA;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 1px 1px rgba(16, 24, 40, 0.03);
    }
    .rounded-xl.border.border-gray-200.hover\:shadow-md:hover {
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.10), 0 3px 8px rgba(16, 24, 40, 0.06);
        border-color: #D6D7D9;
        transform: translateY(-2px);
        border-radius: 14px;
    }
    .hover\:shadow-md { transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease; }

    /* ---- Icon tiles (KPI chips) ---- */
    [class*="w-12"][class*="h-12"][class*="rounded-xl"] {
        background: linear-gradient(145deg, #FFFFFF 0%, #F0F1F3 100%);
        box-shadow: inset 0 1px 0 #FFFFFF, 0 1px 2px rgba(16, 24, 40, 0.10);
        border: 1px solid #EBECEE;
    }

    /* ---- Tables : clean header, gentle zebra ---- */
    thead tr { background: #F8F9FB; }
    thead th {
        font-weight: 600;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        color: #6C737D;
    }
    thead th:first-child { border-top-left-radius: 0; }
    tbody tr { transition: background-color 0.12s ease; }
    tbody tr:not([class*="bg-"]):nth-child(even) { background-color: #FCFCFD; }
    tbody tr:hover { background-color: #F4F5F7; }

    /* Ensure hover always wins over zebra / status rows */
    tbody tr:hover { background-color: #F4F5F7 !important; }

    /* ---- Buttons : unified motion, glossy-black sheen on dark ---- */
    button, a { transition: all 0.15s ease; }
    .bg-green-600:hover, .bg-green-700:hover,
    .bg-emerald-600:hover, .bg-emerald-700:hover,
    .bg-teal-600:hover, .bg-teal-700:hover,
    .bg-cyan-600:hover, .bg-cyan-700:hover,
    .bg-sky-600:hover, .bg-sky-700:hover,
    .bg-blue-600:hover, .bg-blue-700:hover,
    .bg-indigo-600:hover, .bg-indigo-700:hover,
    .bg-violet-600:hover, .bg-violet-700:hover,
    .bg-purple-600:hover, .bg-purple-700:hover,
    .bg-fuchsia-600:hover, .bg-fuchsia-700:hover,
    .bg-pink-600:hover, .bg-pink-700:hover,
    .bg-rose-600:hover, .bg-rose-700:hover,
    .bg-red-600:hover, .bg-red-700:hover,
    .bg-orange-600:hover, .bg-orange-700:hover,
    .bg-amber-600:hover, .bg-amber-700:hover,
    .bg-yellow-600:hover, .bg-yellow-700:hover,
    .bg-lime-600:hover, .bg-lime-700:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.28);
        filter: brightness(1.08);
    }

    /* focus with deep black ring on controls */
    input:focus, select:focus, textarea:focus {
        border-color: #111111 !important;
    }

    @media print {
        .rounded-xl.border.border-gray-200 { box-shadow: none !important; }
        body { background: #FFFFFF !important; }
        tbody tr:not([class*="bg-"]):nth-child(even) { background: #FFFFFF !important; }
    }
</style>