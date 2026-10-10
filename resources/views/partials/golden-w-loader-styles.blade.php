{{--
    World Choice Perfume — Golden Signature W loading animation (styles only).

    Included once per layout <head>, exactly like partials/theme.blade.php,
    because no layout loads a Vite bundle (both use the Tailwind CDN).

    Deliberately background-free: nothing here sets a background-color, a
    border, or a backdrop, so the loader can never change how a page looks
    outside of the mark itself. The two page themes the site actually has —
    the dark public pages and the light staff pages — are untouched, and the
    wordmark inherits the surrounding colour so each context keeps its own
    text colours.

    Cost: only opacity/transform/mask-position animate, in CSS, with no JS, no
    timers and no DOM churn. The sweep stops the moment the element is removed,
    and prefers-reduced-motion leaves a still, fully visible mark.
--}}
<style>
    /* ---- Golden Signature W ------------------------------------------------ */
    .gw-loader {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: inherit; /* follows whichever theme surrounds it */
        animation: gw-appear 0.32s ease-out both; /* PHASE 1: the W settles in */
    }

    .gw-loader__mark {
        position: relative;
        display: block;
        width: var(--gw-size, 40px);
        height: var(--gw-size, 40px);
        flex: none;
    }

    .gw-loader__mark img {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
    }

    /* The resting champagne-gold mark, with a slow breath. The breath is a plain
       opacity animation on purpose: it is the one cue guaranteed to move in every
       browser, so the loader never looks frozen if a mask sweep is unsupported.
       The faint shadow gives its silver facets an edge on the light staff pages
       and is invisible on dark ones. */
    .gw-loader__base {
        filter: drop-shadow(0 1px 1px rgba(17, 17, 17, 0.22));
        animation: gw-breathe 2.1s ease-in-out infinite;
    }

    /* PHASE 2: the luminous band. mask-position slides the band across the mark,
       so the light only ever appears where the mark's own pixels are — a
       travelling reflection, not a bar laid over a letter. */
    .gw-loader__glow {
        filter: brightness(1.45) saturate(1.05);
        -webkit-mask-image: linear-gradient(102deg, transparent 44%, rgba(255, 255, 255, 0.96) 50%, transparent 56%);
        mask-image: linear-gradient(102deg, transparent 44%, rgba(255, 255, 255, 0.96) 50%, transparent 56%);
        -webkit-mask-size: 250% 100%;
        mask-size: 250% 100%;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        animation: gw-sweep 2.1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    }

    .gw-loader__brand {
        font-size: 0.625rem;
        font-weight: 600;
        line-height: 1;
        letter-spacing: 0.32em;
        text-transform: uppercase;
        opacity: 0.75;
    }

    /* ---- Variants: same animation, different presence ---------------------- */
    .gw-loader--inline {
        --gw-size: 20px;
        gap: 0;
    }
    .gw-loader--compact {
        --gw-size: 40px;
    }
    .gw-loader--page {
        /* Responsive: scales with the viewport instead of being desktop-sized. */
        --gw-size: clamp(84px, 22vw, 128px);
        gap: 0.9rem;
    }
    .gw-loader--page .gw-loader__brand {
        font-size: 0.7rem;
        letter-spacing: 0.38em;
    }

    @keyframes gw-appear {
        from { opacity: 0; transform: scale(0.94); }
        to   { opacity: 1; transform: none; }
    }

    @keyframes gw-breathe {
        0%, 100% { opacity: 0.93; }
        50%      { opacity: 1; }
    }

    /* Sweep across, then PHASE 3/4: the light leaves the mark and the cycle
       rests before repeating — no jump, because 55% and 100% are the same
       (off-mark) position. */
    @keyframes gw-sweep {
        0%         { -webkit-mask-position: 100% 0; mask-position: 100% 0; }
        55%, 100%  { -webkit-mask-position: 0 0;    mask-position: 0 0; }
    }

    /* ---- Reduced motion: still branded, no movement ------------------------ */
    @media (prefers-reduced-motion: reduce) {
        .gw-loader,
        .gw-loader__base,
        .gw-loader__glow {
            animation: none;
        }
        .gw-loader__glow {
            display: none; /* the mark stays, the travelling light does not */
        }
    }
</style>
