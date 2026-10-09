/**
 * The app's two complete color palettes.
 *
 * LIGHT is the default mode; DARK is the original deep-black + gold look from
 * the website (resources/views/layouts/public.blade.php — Tailwind config).
 * `lib/theme.ts` exposes a LIVE object that starts on the light palette and
 * swaps in place when the user toggles, so every `COLORS.x` read — in
 * StyleSheet entries or inline JSX — follows the active mode.
 *
 * Design rules that keep the runtime value-swap safe (see theme.ts):
 *  - every token exists in both palettes with identical keys;
 *  - values of "mode-specific" tokens are unique within each palette, so a
 *    colour value always means exactly one token in one direction;
 *  - pure white/black are NOT used for mode-specific tokens (text is
 *    #FAFAFA on dark) so literal whites — QR codes, button labels on brand
 *    gold — are never caught by the palette swap;
 *  - tokens whose value is the same in both modes (solid brand gold, muted
 *    grays, translucent status tints) are simply never swapped;
 *  - staff module accents are single-valued (see MODULE_ACCENTS below) and
 *    never appear in the swap maps.
 */

/** Light mode — the default. Warm ivory page, white-ish cards, darkened gold text. */
export const LIGHT_COLORS = {
  /** page background */
  bg: '#F4F2EC',
  /** raised sections / hero base */
  bgRaised: '#FAF9F5',
  /** cards */
  surface: '#FCFBF8',
  /** inputs, nested surfaces */
  surfaceHigh: '#ECEAE3',
  /** borders */
  border: '#D6D2C7',

  /** brand gold for text/accents — darkened so it reads on ivory */
  gold: '#8A6A15',
  /** solid brand-gold FILL (buttons, badges with near-black content) — stays bright in both modes */
  goldFill: '#C9A12B',
  /** pressed/darker gold — also the readable "eyebrow/small gold text" tone on light backgrounds */
  goldDark: '#6F5410',
  /** "light gold" text variant → darkened for light backgrounds */
  goldLight: '#7C6012',
  /** "bright gold" text variant → darkened for light backgrounds */
  goldBright: '#856510',
  /** deep gold wash (avatars, tinted areas) → pale gold in light mode */
  goldDeep: '#F1E4C2',

  text: '#141414',
  /** secondary text */
  textSecondary: '#4B5563',
  /** muted/placeholder text — same value in both modes */
  textMuted: '#6B7280',

  danger: '#DC2626',
  dangerBg: 'rgba(239, 68, 68, 0.10)',
  dangerBorder: 'rgba(239, 68, 68, 0.30)',
  success: '#047857',
  successBg: 'rgba(16, 185, 129, 0.10)',
  successBorder: 'rgba(16, 185, 129, 0.30)',
  warning: '#B45309',
  info: '#2563EB',

  /** gold at low alpha for chips/halos — same translucent tints in both modes */
  goldSoft: 'rgba(200, 160, 42, 0.12)',
  goldBorder: 'rgba(200, 160, 42, 0.30)',
  goldHalo: 'rgba(200, 160, 42, 0.06)',
};

/** Dark mode — the original look, verbatim (text is #FAFAFA instead of pure #FFFFFF so white stays out of the swap maps). */
export const DARK_COLORS: typeof LIGHT_COLORS = {
  /** dark-950 — page background */
  bg: '#050505',
  /** dark-900 — raised sections / hero base */
  bgRaised: '#0D0D0D',
  /** dark-800 — cards */
  surface: '#1A1A1A',
  /** dark-700 — inputs, nested surfaces */
  surfaceHigh: '#303030',
  /** dark-600 — borders */
  border: '#424242',

  /** gold-500 — the brand gold (buttons, accents) */
  gold: '#C8A02A',
  /** solid brand-gold fill — one shade off #C8A02A so the swap can tell it apart from the text gold */
  goldFill: '#C9A12B',
  /** gold-600 — pressed/darker gold, and the small-gold-text tone on dark */
  goldDark: '#A68523',
  /** gold-300 — light gold text */
  goldLight: '#FFD040',
  /** gold-400 — bright gold text */
  goldBright: '#FFC107',
  /** hero end colour (#42340e, gold-900) for tinted areas */
  goldDeep: '#42340E',

  text: '#FAFAFA',
  /** gray-400 — secondary text */
  textSecondary: '#9CA3AF',
  /** gray-500 — muted/placeholder text */
  textMuted: '#6B7280',

  danger: '#F87171',
  dangerBg: 'rgba(239, 68, 68, 0.10)',
  dangerBorder: 'rgba(239, 68, 68, 0.30)',
  success: '#34D399',
  successBg: 'rgba(16, 185, 129, 0.10)',
  successBorder: 'rgba(16, 185, 129, 0.30)',
  warning: '#FCD34D',
  info: '#60A5FA',

  /** gold at low alpha for chips/halos */
  goldSoft: 'rgba(200, 160, 42, 0.12)',
  goldBorder: 'rgba(200, 160, 42, 0.30)',
  goldHalo: 'rgba(200, 160, 42, 0.06)',
};

/**
 * Staff module accents (main/light/soft/border). Deliberately single-valued:
 * `main`/`light` are bright fills/chips behind near-black content, and the
 * staff drawers are dark chrome in both app modes — so accents are NOT part
 * of the swap maps (none of these values may collide with a palette value
 * that does swap; validated).
 */
export type ModuleAccent = {
  main: string;
  light: string;
  soft: string;
  border: string;
};

export const MODULE_ACCENTS = {
  /** Graphic Designer — purple-600/purple-500 */
  gd: {
    main: '#A855F7',
    light: '#C084FC',
    soft: 'rgba(168, 85, 247, 0.12)',
    border: 'rgba(168, 85, 247, 0.30)',
  },
  /** Stock Manager — emerald-500/400 (#4ADE80 green-400, kept clear of the emerald swap values) */
  sm: {
    main: '#10B981',
    light: '#4ADE80',
    soft: 'rgba(16, 185, 129, 0.12)',
    border: 'rgba(16, 185, 129, 0.30)',
  },
  /** Customer Care — sky-500/400 */
  cc: {
    main: '#38BDF8',
    light: '#7DD3FC',
    soft: 'rgba(56, 189, 248, 0.12)',
    border: 'rgba(56, 189, 248, 0.30)',
  },
  /** Branch Admin — amber-500/400 */
  ba: {
    main: '#F59E0B',
    light: '#FBBF24',
    soft: 'rgba(245, 158, 11, 0.12)',
    border: 'rgba(245, 158, 11, 0.30)',
  },
  /** Seller — cyan-500/400 */
  seller: {
    main: '#06B6D4',
    light: '#22D3EE',
    soft: 'rgba(6, 182, 212, 0.12)',
    border: 'rgba(6, 182, 212, 0.30)',
  },
  /** Cashier — amber-600/400 */
  cashier: {
    main: '#D97706',
    light: '#FBBF24',
    soft: 'rgba(217, 119, 6, 0.12)',
    border: 'rgba(217, 119, 6, 0.30)',
  },
};
