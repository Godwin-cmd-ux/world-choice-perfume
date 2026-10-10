{{--
    Website theme modes — DARK is the DEFAULT, light is opt-in.

    partials/theme-boot.blade.php sets <html data-theme="dark|light"> from
    localStorage ('wcp-theme') before the first paint, so there is never a flash
    of the wrong theme; partials/theme-toggle.blade.php flips it. This file only
    describes what each mode looks like.

    Two skins live in the same codebase: the staff area (and the standalone
    order pages) is authored LIGHT — bg-white, text-gray-700 — while the public
    site is authored DARK — bg-dark-950, text-gray-300. So the two directions
    are:

      * dark  (default) — every light-authored utility that is actually used
                          gets a dark value, wrapped in @media screen so a
                          printed report still comes out dark-on-white,
      * light (opt-in)  — the dark-authored public palette gets light values.

    Each rule below exists because the class is used somewhere in
    resources/views; nothing generic is recoloured, so both modes keep the
    layout and hierarchy the site already had.

    Brand buttons are NOT part of either mode: they are #F89A1E with white
    labels in dark and light alike, exactly as asked.
--}}
<style>
    /* ================================================================== *
     * 1. Brand buttons — #F89A1E in every mode, white labels.
     *
     * Solid-fill buttons are the site's actions (submit / save / create /
     * new / confirm / send / track / reset) and its filled secondary
     * buttons, including the gold gradient CTAs on the public pages.
     * Destructive buttons stay red; tinted chips, steppers, tabs and the
     * sidebar's navigation pills are not buttons and keep their colours.
     * ================================================================== */
    :is(button, a):is(.bg-emerald-500, .bg-emerald-600, .bg-emerald-700, .bg-green-500, .bg-green-600, .bg-green-700, .bg-amber-400, .bg-amber-500, .bg-amber-600, .bg-amber-700, .bg-amber-800, .bg-blue-500, .bg-blue-600, .bg-blue-700, .bg-cyan-500, .bg-cyan-600, .bg-cyan-700, .bg-yellow-400, .bg-yellow-500, .bg-yellow-600, .bg-gold-400, .bg-gold-500, .bg-gold-600, .bg-orange-500, .bg-orange-600, .bg-gray-600, .bg-gray-700, .bg-gray-800, .bg-gray-900, .bg-gray-200), :is(button, a)[class*="bg-gradient-to-"] {
        background-color: #F89A1E !important;
        background-image: none !important;
        border-color: #F89A1E !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 12px rgba(248, 154, 30, 0.28);
        transition: filter 0.15s ease, box-shadow 0.15s ease;
        cursor: pointer;
    }
    :is(button, a):is(.bg-emerald-500, .bg-emerald-600, .bg-emerald-700, .bg-green-500, .bg-green-600, .bg-green-700, .bg-amber-400, .bg-amber-500, .bg-amber-600, .bg-amber-700, .bg-amber-800, .bg-blue-500, .bg-blue-600, .bg-blue-700, .bg-cyan-500, .bg-cyan-600, .bg-cyan-700, .bg-yellow-400, .bg-yellow-500, .bg-yellow-600, .bg-gold-400, .bg-gold-500, .bg-gold-600, .bg-orange-500, .bg-orange-600, .bg-gray-600, .bg-gray-700, .bg-gray-800, .bg-gray-900, .bg-gray-200), :is(button, a)[class*="bg-gradient-to-"] :is(span, i, svg, .fas, .far, .fab) {
        color: #FFFFFF !important;
    }
    :is(button, a):is(.bg-emerald-500, .bg-emerald-600, .bg-emerald-700, .bg-green-500, .bg-green-600, .bg-green-700, .bg-amber-400, .bg-amber-500, .bg-amber-600, .bg-amber-700, .bg-amber-800, .bg-blue-500, .bg-blue-600, .bg-blue-700, .bg-cyan-500, .bg-cyan-600, .bg-cyan-700, .bg-yellow-400, .bg-yellow-500, .bg-yellow-600, .bg-gold-400, .bg-gold-500, .bg-gold-600, .bg-orange-500, .bg-orange-600, .bg-gray-600, .bg-gray-700, .bg-gray-800, .bg-gray-900, .bg-gray-200), :is(button, a)[class*="bg-gradient-to-"]:hover,
    :is(button, a):is(.bg-emerald-500, .bg-emerald-600, .bg-emerald-700, .bg-green-500, .bg-green-600, .bg-green-700, .bg-amber-400, .bg-amber-500, .bg-amber-600, .bg-amber-700, .bg-amber-800, .bg-blue-500, .bg-blue-600, .bg-blue-700, .bg-cyan-500, .bg-cyan-600, .bg-cyan-700, .bg-yellow-400, .bg-yellow-500, .bg-yellow-600, .bg-gold-400, .bg-gold-500, .bg-gold-600, .bg-orange-500, .bg-orange-600, .bg-gray-600, .bg-gray-700, .bg-gray-800, .bg-gray-900, .bg-gray-200), :is(button, a)[class*="bg-gradient-to-"]:disabled {
        background-color: #F89A1E !important;
        filter: brightness(1.07);
    }

    /* The sidebar's active item (and the cross-branch drawer's, which draws the
       same dark chrome on the same page) stays a white pill, so a selected
       navigation item does not read as a brand button. */
    #sidebar nav a.bg-amber-600, nav[class*="from-gray-900"] a.bg-amber-600, #sidebar nav a.bg-emerald-600, nav[class*="from-gray-900"] a.bg-emerald-600, #sidebar nav a.bg-blue-600, nav[class*="from-gray-900"] a.bg-blue-600, #sidebar nav a.bg-purple-600, nav[class*="from-gray-900"] a.bg-purple-600, #sidebar nav a.bg-green-600, nav[class*="from-gray-900"] a.bg-green-600, #sidebar nav a.bg-cyan-600, nav[class*="from-gray-900"] a.bg-cyan-600 {
        background: #FFFFFF !important;
        background-image: none !important;
        color: #111111 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45) !important;
        filter: none !important;
    }
    #sidebar nav a.bg-amber-600 :is(i, span), nav[class*="from-gray-900"] a.bg-amber-600 :is(i, span), #sidebar nav a.bg-emerald-600 :is(i, span), nav[class*="from-gray-900"] a.bg-emerald-600 :is(i, span), #sidebar nav a.bg-blue-600 :is(i, span), nav[class*="from-gray-900"] a.bg-blue-600 :is(i, span), #sidebar nav a.bg-purple-600 :is(i, span), nav[class*="from-gray-900"] a.bg-purple-600 :is(i, span), #sidebar nav a.bg-green-600 :is(i, span), nav[class*="from-gray-900"] a.bg-green-600 :is(i, span), #sidebar nav a.bg-cyan-600 :is(i, span), nav[class*="from-gray-900"] a.bg-cyan-600 :is(i, span) {
        color: #111111 !important;
    }

    /* ================================================================== *
     * 2. Mode plumbing — native controls, selection, scrollbars.
     * ================================================================== */
    html[data-theme="dark"] { color-scheme: dark; background-color: #0B0B0B; }
    html[data-theme="light"] { color-scheme: light; background-color: #F7F7F4; }
    html[data-theme="dark"] ::selection { background: #F89A1E; color: #111111; }
    html[data-theme="light"] ::selection { background: #111111; color: #FFFFFF; }
    html[data-theme="dark"] ::-webkit-scrollbar-track { background: #111111; }
    html[data-theme="dark"] ::-webkit-scrollbar-thumb { background: #3A3A3A; }
    html[data-theme="light"] ::-webkit-scrollbar-track { background: #EDEDE9; }
    html[data-theme="light"] ::-webkit-scrollbar-thumb { background: #C8A02A; }
    html[data-theme="dark"] ::placeholder { color: #7C8592; }
    html[data-theme="dark"] textarea,
    html[data-theme="dark"] input,
    html[data-theme="dark"] select { color-scheme: dark; }

    /* ================================================================== *
     * 3. DARK (default) — the light staff skin, inverted.
     *
     * Screen-only: a printed report must stay dark-on-white whatever mode
     * the browser is in.
     * ================================================================== */
    @media screen {
        html[data-theme="dark"] body { background-color: #0B0B0B; color: #EDEDED; }
        html[data-theme="dark"] body:is(.bg-gray-50, .bg-gray-100) { background-color: #0B0B0B !important; }

        /* masthead */
        html[data-theme="dark"] .main-content > header {
            background: linear-gradient(180deg, #171717 0%, #111111 100%);
            border-bottom-color: #262626;
            box-shadow: none;
        }
        html[data-theme="dark"] .main-content > header :is(h1, h2) { color: #F5F6F7; }

        /* cards */
        html[data-theme="dark"] .rounded-xl.border.border-gray-200 {
            border-color: #272727;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        html[data-theme="dark"] .rounded-xl.border.border-gray-200.hover\:shadow-md:hover {
            border-color: #3A3A3A;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55);
        }
        html[data-theme="dark"] [class*="w-12"][class*="h-12"][class*="rounded-xl"]:not([class*="bg-"]) {
            background: linear-gradient(145deg, #1C1C1C 0%, #151515 100%);
            border-color: #2A2A2A;
            box-shadow: inset 0 1px 0 #242424, 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        /* tables */
        html[data-theme="dark"] thead tr { background: #171717; }
        html[data-theme="dark"] thead th { color: #9CA3AF; }
        html[data-theme="dark"] tbody tr:not([class*="bg-"]):nth-child(even) { background-color: #131313 !important; }
        html[data-theme="dark"] tbody tr:hover { background-color: #1C1C1C !important; }

        /* controls */
        html[data-theme="dark"] input:focus,
        html[data-theme="dark"] select:focus,
        html[data-theme="dark"] textarea:focus { border-color: #F89A1E !important; }

        html[data-theme="dark"] .bg-amber-100 { background-color: #3A2A0B; }
        html[data-theme="dark"] .bg-amber-50 { background-color: #2A1E08; }
        html[data-theme="dark"] .bg-amber-50\/70 { background-color: rgba(251, 191, 36, 0.14); }
        html[data-theme="dark"] .bg-blue-100 { background-color: #0E2745; }
        html[data-theme="dark"] .bg-blue-50 { background-color: #0B1B33; }
        html[data-theme="dark"] .bg-cyan-100 { background-color: #0A333B; }
        html[data-theme="dark"] .bg-cyan-50 { background-color: #08242A; }
        html[data-theme="dark"] .bg-emerald-100 { background-color: #0E3324; }
        html[data-theme="dark"] .bg-emerald-50 { background-color: #0A231A; }
        html[data-theme="dark"] .bg-emerald-50\/40 { background-color: rgba(52, 211, 153, 0.08); }
        html[data-theme="dark"] .bg-gray-100 { background-color: #1A1A1A; }
        html[data-theme="dark"] .bg-gray-200 { background-color: #242424; }
        html[data-theme="dark"] .bg-gray-300 { background-color: #2E2E2E; }
        html[data-theme="dark"] .bg-gray-50 { background-color: #101010; }
        html[data-theme="dark"] .bg-gray-50\/60 { background-color: rgba(255, 255, 255, 0.05); }
        html[data-theme="dark"] .bg-green-100 { background-color: #0F3320; }
        html[data-theme="dark"] .bg-green-50 { background-color: #0B2418; }
        html[data-theme="dark"] .bg-indigo-100 { background-color: #171A45; }
        html[data-theme="dark"] .bg-indigo-50 { background-color: #111334; }
        html[data-theme="dark"] .bg-orange-100 { background-color: #3A1F0A; }
        html[data-theme="dark"] .bg-orange-50 { background-color: #2A1608; }
        html[data-theme="dark"] .bg-purple-100 { background-color: #2A1545; }
        html[data-theme="dark"] .bg-purple-50 { background-color: #1E0F33; }
        html[data-theme="dark"] .bg-red-100 { background-color: #3A1818; }
        html[data-theme="dark"] .bg-red-50 { background-color: #2A1212; }
        html[data-theme="dark"] .bg-red-50\/40 { background-color: rgba(248, 113, 113, 0.08); }
        html[data-theme="dark"] .bg-rose-100 { background-color: #3A1620; }
        html[data-theme="dark"] .bg-sky-100 { background-color: #0A2C40; }
        html[data-theme="dark"] .bg-white { background-color: #141414; }
        html[data-theme="dark"] .bg-yellow-100 { background-color: #3A3109; }
        html[data-theme="dark"] .bg-yellow-50 { background-color: #2A2308; }
        html[data-theme="dark"] .border-amber-100 { border-color: #3A2A0B; }
        html[data-theme="dark"] .border-amber-200 { border-color: #2A1E08; }
        html[data-theme="dark"] .border-blue-200 { border-color: #0B1B33; }
        html[data-theme="dark"] .border-emerald-100 { border-color: #0E3324; }
        html[data-theme="dark"] .border-emerald-200 { border-color: #0A231A; }
        html[data-theme="dark"] .border-gray-100 { border-color: #1F1F1F; }
        html[data-theme="dark"] .border-gray-200 { border-color: #272727; }
        html[data-theme="dark"] .border-gray-300 { border-color: #333333; }
        html[data-theme="dark"] .border-gray-400 { border-color: #454545; }
        html[data-theme="dark"] .border-green-100 { border-color: #0F3320; }
        html[data-theme="dark"] .border-green-200 { border-color: #0B2418; }
        html[data-theme="dark"] .border-indigo-200 { border-color: #111334; }
        html[data-theme="dark"] .border-orange-200 { border-color: #2A1608; }
        html[data-theme="dark"] .border-red-100 { border-color: #3A1818; }
        html[data-theme="dark"] .border-red-200 { border-color: #2A1212; }
        html[data-theme="dark"] .divide-gray-100 > :not([hidden]) ~ :not([hidden]) { border-color: #1F1F1F; }
        html[data-theme="dark"] .text-amber-400 { color: #FBBF24; }
        html[data-theme="dark"] .text-amber-500 { color: #FBBF24; }
        html[data-theme="dark"] .text-amber-600 { color: #FBBF24; }
        html[data-theme="dark"] .text-amber-700 { color: #FCD34D; }
        html[data-theme="dark"] .text-amber-800 { color: #FDE68A; }
        html[data-theme="dark"] .text-amber-900 { color: #FEF3C7; }
        html[data-theme="dark"] .text-black { color: #F5F6F7; }
        html[data-theme="dark"] .text-blue-300 { color: #93C5FD; }
        html[data-theme="dark"] .text-blue-400 { color: #60A5FA; }
        html[data-theme="dark"] .text-blue-500 { color: #60A5FA; }
        html[data-theme="dark"] .text-blue-600 { color: #60A5FA; }
        html[data-theme="dark"] .text-blue-700 { color: #93C5FD; }
        html[data-theme="dark"] .text-blue-800 { color: #BFDBFE; }
        html[data-theme="dark"] .text-cyan-400 { color: #22D3EE; }
        html[data-theme="dark"] .text-cyan-600 { color: #22D3EE; }
        html[data-theme="dark"] .text-cyan-700 { color: #67E8F9; }
        html[data-theme="dark"] .text-cyan-800 { color: #A5F3FC; }
        html[data-theme="dark"] .text-emerald-400 { color: #34D399; }
        html[data-theme="dark"] .text-emerald-500 { color: #34D399; }
        html[data-theme="dark"] .text-emerald-600 { color: #34D399; }
        html[data-theme="dark"] .text-emerald-700 { color: #6EE7B7; }
        html[data-theme="dark"] .text-emerald-800 { color: #A7F3D0; }
        html[data-theme="dark"] .text-gray-500 { color: #8B93A1; }
        html[data-theme="dark"] .text-gray-600 { color: #A3ABB8; }
        html[data-theme="dark"] .text-gray-700 { color: #C7CDD6; }
        html[data-theme="dark"] .text-gray-800 { color: #E3E6EA; }
        html[data-theme="dark"] .text-gray-900 { color: #F5F6F7; }
        html[data-theme="dark"] .text-green-400 { color: #4ADE80; }
        html[data-theme="dark"] .text-green-500 { color: #4ADE80; }
        html[data-theme="dark"] .text-green-600 { color: #4ADE80; }
        html[data-theme="dark"] .text-green-700 { color: #86EFAC; }
        html[data-theme="dark"] .text-green-800 { color: #BBF7D0; }
        html[data-theme="dark"] .text-indigo-700 { color: #A5B4FC; }
        html[data-theme="dark"] .text-orange-500 { color: #FB923C; }
        html[data-theme="dark"] .text-orange-600 { color: #FB923C; }
        html[data-theme="dark"] .text-orange-700 { color: #FDBA74; }
        html[data-theme="dark"] .text-pink-400 { color: #F472B6; }
        html[data-theme="dark"] .text-purple-300 { color: #D8B4FE; }
        html[data-theme="dark"] .text-purple-400 { color: #C084FC; }
        html[data-theme="dark"] .text-purple-500 { color: #C084FC; }
        html[data-theme="dark"] .text-purple-600 { color: #C084FC; }
        html[data-theme="dark"] .text-purple-700 { color: #D8B4FE; }
        html[data-theme="dark"] .text-purple-800 { color: #E9D5FF; }
        html[data-theme="dark"] .text-red-300 { color: #FCA5A5; }
        html[data-theme="dark"] .text-red-400 { color: #F87171; }
        html[data-theme="dark"] .text-red-500 { color: #F87171; }
        html[data-theme="dark"] .text-red-600 { color: #F87171; }
        html[data-theme="dark"] .text-red-700 { color: #FCA5A5; }
        html[data-theme="dark"] .text-red-800 { color: #FECACA; }
        html[data-theme="dark"] .text-rose-700 { color: #FDA4AF; }
        html[data-theme="dark"] .text-sky-700 { color: #7DD3FC; }
        html[data-theme="dark"] .text-slate-600 { color: #A8AFBA; }
        html[data-theme="dark"] .text-teal-400 { color: #2DD4BF; }
        html[data-theme="dark"] .text-yellow-600 { color: #FDE047; }
        html[data-theme="dark"] .text-yellow-700 { color: #FEF08A; }
        html[data-theme="dark"] .text-yellow-800 { color: #FEF9C3; }
        html[data-theme="dark"] .focus\:border-gray-500:focus { border-color: #555555; }
        html[data-theme="dark"] .hover\:bg-amber-100:hover { background-color: #3A2A0B; }
        html[data-theme="dark"] .hover\:bg-blue-50:hover { background-color: #0B1B33; }
        html[data-theme="dark"] .hover\:bg-cyan-100:hover { background-color: #0A333B; }
        html[data-theme="dark"] .hover\:bg-emerald-100:hover { background-color: #0E3324; }
        html[data-theme="dark"] .hover\:bg-emerald-50:hover { background-color: #0A231A; }
        html[data-theme="dark"] .hover\:bg-gray-100:hover { background-color: #1A1A1A; }
        html[data-theme="dark"] .hover\:bg-gray-200:hover { background-color: #242424; }
        html[data-theme="dark"] .hover\:bg-gray-300:hover { background-color: #2E2E2E; }
        html[data-theme="dark"] .hover\:bg-gray-50:hover { background-color: #101010; }
        html[data-theme="dark"] .hover\:bg-green-100:hover { background-color: #0F3320; }
        html[data-theme="dark"] .hover\:bg-red-50:hover { background-color: #2A1212; }
        html[data-theme="dark"] .hover\:bg-white:hover { background-color: #141414; }
        html[data-theme="dark"] .hover\:border-gray-300:hover { border-color: #333333; }
        html[data-theme="dark"] .hover\:border-gray-500:hover { border-color: #555555; }
        html[data-theme="dark"] .hover\:text-amber-700:hover { color: #FCD34D; }
        html[data-theme="dark"] .hover\:text-amber-800:hover { color: #FDE68A; }
        html[data-theme="dark"] .hover\:text-amber-900:hover { color: #FEF3C7; }
        html[data-theme="dark"] .hover\:text-blue-600:hover { color: #60A5FA; }
        html[data-theme="dark"] .hover\:text-blue-700:hover { color: #93C5FD; }
        html[data-theme="dark"] .hover\:text-blue-800:hover { color: #BFDBFE; }
        html[data-theme="dark"] .hover\:text-cyan-700:hover { color: #67E8F9; }
        html[data-theme="dark"] .hover\:text-emerald-600:hover { color: #34D399; }
        html[data-theme="dark"] .hover\:text-emerald-700:hover { color: #6EE7B7; }
        html[data-theme="dark"] .hover\:text-emerald-800:hover { color: #A7F3D0; }
        html[data-theme="dark"] .hover\:text-gray-600:hover { color: #A3ABB8; }
        html[data-theme="dark"] .hover\:text-gray-700:hover { color: #C7CDD6; }
        html[data-theme="dark"] .hover\:text-gray-900:hover { color: #F5F6F7; }
        html[data-theme="dark"] .hover\:text-green-400:hover { color: #4ADE80; }
        html[data-theme="dark"] .hover\:text-green-700:hover { color: #86EFAC; }
        html[data-theme="dark"] .hover\:text-pink-400:hover { color: #F472B6; }
        html[data-theme="dark"] .hover\:text-purple-800:hover { color: #E9D5FF; }
        html[data-theme="dark"] .hover\:text-red-400:hover { color: #F87171; }
        html[data-theme="dark"] .hover\:text-red-600:hover { color: #F87171; }
        html[data-theme="dark"] .hover\:text-red-700:hover { color: #FCA5A5; }
        html[data-theme="dark"] .hover\:text-red-800:hover { color: #FECACA; }
        html[data-theme="dark"] .hover\:text-sky-700:hover { color: #7DD3FC; }

        /* the mobile drawer scrim */
        html[data-theme="dark"] .sidebar-overlay { background: rgba(0, 0, 0, 0.65); }
    }

    /* ================================================================== *
     * 4. LIGHT (opt-in) — the public dark skin, inverted.
     *
     * Scoped to `.wcp-dark-skin`, the marker on the pages that are authored
     * dark (the public layout and the two tracked-order pages). The staff
     * chrome — black sidebar, black drawers — is dark on purpose in both
     * modes and must keep its white text.
     * ================================================================== */
    html[data-theme="light"] body.wcp-dark-skin { background-color: #F7F7F4; color: #111111; }

    html[data-theme="light"] .wcp-dark-skin.bg-dark-700, html[data-theme="light"] .wcp-dark-skin .bg-dark-700 { background-color: #E9E7E0; }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-800, html[data-theme="light"] .wcp-dark-skin .bg-dark-800 { background-color: #F2F1ED; }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-800\/50, html[data-theme="light"] .wcp-dark-skin .bg-dark-800\/50 { background-color: rgba(242, 241, 237, 0.85); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-900, html[data-theme="light"] .wcp-dark-skin .bg-dark-900 { background-color: #FFFFFF; }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-900\/50, html[data-theme="light"] .wcp-dark-skin .bg-dark-900\/50 { background-color: rgba(255, 255, 255, 0.8); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-900\/80, html[data-theme="light"] .wcp-dark-skin .bg-dark-900\/80 { background-color: rgba(255, 255, 255, 0.94); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-900\/98, html[data-theme="light"] .wcp-dark-skin .bg-dark-900\/98 { background-color: rgba(255, 255, 255, 0.98); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-950, html[data-theme="light"] .wcp-dark-skin .bg-dark-950 { background-color: #F7F7F4; }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-950\/25, html[data-theme="light"] .wcp-dark-skin .bg-dark-950\/25 { background-color: rgba(247, 247, 244, 0.6); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-950\/80, html[data-theme="light"] .wcp-dark-skin .bg-dark-950\/80 { background-color: rgba(247, 247, 244, 0.94); }
    html[data-theme="light"] .wcp-dark-skin.bg-dark-950\/90, html[data-theme="light"] .wcp-dark-skin .bg-dark-950\/90 { background-color: rgba(247, 247, 244, 0.94); }
    html[data-theme="light"] .wcp-dark-skin.bg-white\/10, html[data-theme="light"] .wcp-dark-skin .bg-white\/10 { background-color: rgba(17, 17, 17, 0.06); }
    html[data-theme="light"] .wcp-dark-skin.bg-white\/20, html[data-theme="light"] .wcp-dark-skin .bg-white\/20 { background-color: rgba(17, 17, 17, 0.1); }
    html[data-theme="light"] .wcp-dark-skin.bg-zinc-800\/50, html[data-theme="light"] .wcp-dark-skin .bg-zinc-800\/50 { background-color: rgba(242, 241, 237, 0.85); }
    html[data-theme="light"] .wcp-dark-skin.bg-zinc-900\/80, html[data-theme="light"] .wcp-dark-skin .bg-zinc-900\/80 { background-color: rgba(255, 255, 255, 0.94); }
    html[data-theme="light"] .wcp-dark-skin.border-dark-600, html[data-theme="light"] .wcp-dark-skin .border-dark-600 { border-color: #DDD9D0; }
    html[data-theme="light"] .wcp-dark-skin.border-dark-700, html[data-theme="light"] .wcp-dark-skin .border-dark-700 { border-color: #E5E2DA; }
    html[data-theme="light"] .wcp-dark-skin.border-dark-800, html[data-theme="light"] .wcp-dark-skin .border-dark-800 { border-color: #EDEAE3; }
    html[data-theme="light"] .wcp-dark-skin.text-gold-300, html[data-theme="light"] .wcp-dark-skin .text-gold-300 { color: #6F5410; }
    html[data-theme="light"] .wcp-dark-skin.text-gold-400, html[data-theme="light"] .wcp-dark-skin .text-gold-400 { color: #8A6A15; }
    html[data-theme="light"] .wcp-dark-skin.text-gold-400\/50, html[data-theme="light"] .wcp-dark-skin .text-gold-400\/50 { color: rgba(138, 106, 21, 0.7); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-400\/60, html[data-theme="light"] .wcp-dark-skin .text-gold-400\/60 { color: rgba(138, 106, 21, 0.75); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-400\/70, html[data-theme="light"] .wcp-dark-skin .text-gold-400\/70 { color: rgba(122, 94, 18, 0.85); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-400\/80, html[data-theme="light"] .wcp-dark-skin .text-gold-400\/80 { color: rgba(122, 94, 18, 0.9); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-500\/20, html[data-theme="light"] .wcp-dark-skin .text-gold-500\/20 { color: rgba(124, 96, 18, 0.45); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-500\/40, html[data-theme="light"] .wcp-dark-skin .text-gold-500\/40 { color: rgba(124, 96, 18, 0.6); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-500\/60, html[data-theme="light"] .wcp-dark-skin .text-gold-500\/60 { color: rgba(124, 96, 18, 0.8); }
    html[data-theme="light"] .wcp-dark-skin.text-gold-500\/70, html[data-theme="light"] .wcp-dark-skin .text-gold-500\/70 { color: rgba(110, 84, 16, 0.85); }
    html[data-theme="light"] .wcp-dark-skin.text-gray-200, html[data-theme="light"] .wcp-dark-skin .text-gray-200 { color: #2A2A2A; }
    html[data-theme="light"] .wcp-dark-skin.text-gray-300, html[data-theme="light"] .wcp-dark-skin .text-gray-300 { color: #3F3F46; }
    html[data-theme="light"] .wcp-dark-skin.text-gray-400, html[data-theme="light"] .wcp-dark-skin .text-gray-400 { color: #52525B; }
    html[data-theme="light"] .wcp-dark-skin.text-gray-500, html[data-theme="light"] .wcp-dark-skin .text-gray-500 { color: #6B7280; }
    html[data-theme="light"] .wcp-dark-skin.text-white, html[data-theme="light"] .wcp-dark-skin .text-white { color: #111111; }
    html[data-theme="light"] .wcp-dark-skin.group:hover .group-hover\:text-gold-400, html[data-theme="light"] .wcp-dark-skin .group:hover .group-hover\:text-gold-400 { color: #8A6A15; }
    html[data-theme="light"] .wcp-dark-skin.hover\:bg-dark-700:hover, html[data-theme="light"] .wcp-dark-skin .hover\:bg-dark-700:hover { background-color: #E9E7E0; }
    html[data-theme="light"] .wcp-dark-skin.hover\:bg-dark-800:hover, html[data-theme="light"] .wcp-dark-skin .hover\:bg-dark-800:hover { background-color: #F2F1ED; }
    html[data-theme="light"] .wcp-dark-skin.hover\:bg-white\/20:hover, html[data-theme="light"] .wcp-dark-skin .hover\:bg-white\/20:hover { background-color: rgba(17, 17, 17, 0.1); }
    html[data-theme="light"] .wcp-dark-skin.hover\:bg-white\/5:hover, html[data-theme="light"] .wcp-dark-skin .hover\:bg-white\/5:hover { background-color: rgba(17, 17, 17, 0.04); }
    html[data-theme="light"] .wcp-dark-skin.hover\:text-gold-300:hover, html[data-theme="light"] .wcp-dark-skin .hover\:text-gold-300:hover { color: #6F5410; }
    html[data-theme="light"] .wcp-dark-skin.hover\:text-gold-400:hover, html[data-theme="light"] .wcp-dark-skin .hover\:text-gold-400:hover { color: #8A6A15; }
    html[data-theme="light"] .wcp-dark-skin.hover\:text-gray-300:hover, html[data-theme="light"] .wcp-dark-skin .hover\:text-gray-300:hover { color: #3F3F46; }
    html[data-theme="light"] .wcp-dark-skin.hover\:text-white:hover, html[data-theme="light"] .wcp-dark-skin .hover\:text-white:hover { color: #111111; }

    html[data-theme="light"] .wcp-dark-skin .hero-gradient, html[data-theme="light"] .wcp-dark-skin.hero-gradient {
        background: linear-gradient(135deg, #FFFFFF 0%, #F7F3E8 45%, #F0E3C2 100%);
    }
    /* the two tracked-order pages paint the same dark gradient as the hero */
    html[data-theme="light"] .wcp-dark-skin.page-gradient {
        background: linear-gradient(135deg, #FFFFFF 0%, #F7F3E8 45%, #F0E3C2 100%);
        background-attachment: fixed;
    }
    html[data-theme="light"] .wcp-dark-skin .gold-text, html[data-theme="light"] .wcp-dark-skin.gold-text {
        background: linear-gradient(135deg, #A68523, #7C6012, #C8A02A);
        -webkit-background-clip: text;
        background-clip: text;
    }
    html[data-theme="light"] .wcp-dark-skin .card-hover:hover {
        box-shadow: 0 25px 50px -12px rgba(17, 17, 17, 0.18);
    }
    html[data-theme="light"] .wcp-dark-skin .shimmer {
        background: linear-gradient(90deg, transparent, rgba(200, 160, 42, 0.14), transparent);
    }
</style>
