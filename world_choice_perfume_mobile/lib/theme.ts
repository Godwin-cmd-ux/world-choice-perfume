/**
 * Design tokens taken from the World Choice Perfume website
 * (resources/views/layouts/public.blade.php — the Tailwind config it ships):
 * deep-black surfaces (dark-950…dark-600) with the muted luxury gold scale
 * (gold-500 #C8A02A etc.). Using the exact values keeps the app instantly
 * recognisable as the brand behind worldchoiceperfume.com.
 */
export const COLORS = {
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
  /** gold-600 — pressed/darker gold */
  goldDark: '#A68523',
  /** gold-300 — light gold text */
  goldLight: '#FFD040',
  /** gold-400 — bright gold text */
  goldBright: '#FFC107',
  /** hero end colour (#42340e, gold-900) for tinted areas */
  goldDeep: '#42340E',

  text: '#FFFFFF',
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
} as const;

/**
 * Graphic Designer module accent — the purple-600/purple-500 the website's
 * /graphic-designer sidebar and headings use. Added alongside (never in
 * place of) the gold scale so both staff modules keep their own identity.
 */
export const GD_ACCENT = {
  /** purple-600 — GD eyebrow/active tint */
  main: '#A855F7',
  /** purple-500 — lighter accent */
  light: '#C084FC',
  /** purple at low alpha for chips/halos */
  soft: 'rgba(168, 85, 247, 0.12)',
  border: 'rgba(168, 85, 247, 0.30)',
} as const;

/**
 * Stock Manager module accent — the emerald-500/600 the website's
 * /stock-manager sidebar and active links use. Added alongside (never in
 * place of) the gold scale and GD purple so each staff module keeps its own
 * identity.
 */
export const SM_ACCENT = {
  /** emerald-500 — SM eyebrow/active tint */
  main: '#10B981',
  /** emerald-400 — lighter accent */
  light: '#34D399',
  /** emerald at low alpha for chips/halos */
  soft: 'rgba(16, 185, 129, 0.12)',
  border: 'rgba(16, 185, 129, 0.30)',
} as const;

/** Soft gold elevation used on primary buttons (website: shadow-gold-500/25). */
export const GOLD_SHADOW = {
  shadowColor: COLORS.gold,
  shadowOpacity: 0.25,
  shadowRadius: 12,
  shadowOffset: { width: 0, height: 6 },
  elevation: 6,
} as const;

/** Corner radii used across the website (rounded-xl = 12, rounded-2xl = 16). */
/** Customer Care accent — sky blue, the third staff module's identity. */
export const CC_ACCENT = {
  main: '#38BDF8',
  light: '#7DD3FC',
  soft: 'rgba(56, 189, 248, 0.12)',
  border: 'rgba(56, 189, 248, 0.30)',
};

/**
 * Branch Admin accent — the amber-500/400 the website's /branch-admin
 * sidebar and active links use (bg-amber-600). Added alongside (never in
 * place of) the gold scale and the other module accents.
 */
export const BA_ACCENT = {
  main: '#F59E0B',
  light: '#FBBF24',
  soft: 'rgba(245, 158, 11, 0.12)',
  border: 'rgba(245, 158, 11, 0.30)',
};

/**
 * Seller accent — the cyan-500/400 the website's /seller sidebar and
 * active links use (bg-cyan-600, text-cyan-400). Added alongside (never in
 * place of) the gold scale and the other module accents.
 */
export const SELLER_ACCENT = {
  main: '#06B6D4',
  light: '#22D3EE',
  soft: 'rgba(6, 182, 212, 0.12)',
  border: 'rgba(6, 182, 212, 0.30)',
};

/**
 * Cashier accent — the amber-600/400 the website's /cashier sidebar and
 * active links use (bg-amber-600, text-amber-400). Same amber family as
 * Branch Admin, but the cashier's own amber-600 shade, kept as its own
 * token alongside (never in place of) the gold scale and other accents.
 */
export const CASHIER_ACCENT = {
  main: '#D97706',
  light: '#FBBF24',
  soft: 'rgba(217, 119, 6, 0.12)',
  border: 'rgba(217, 119, 6, 0.30)',
};

export const RADIUS = {
  md: 12,
  lg: 16,
  pill: 999,
} as const;

/** Horizontal page gutter — comfortable margins on every screen size. */
export const GUTTER = 24;
