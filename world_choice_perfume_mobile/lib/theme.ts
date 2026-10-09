/**
 * Design tokens taken from the World Choice Perfume website
 * (resources/views/layouts/public.blade.php — the Tailwind config it ships):
 * deep-black surfaces (dark-950…dark-600) with the muted luxury gold scale
 * (gold-500 #C8A02A etc.). Using the exact values keeps the app instantly
 * recognisable as the brand behind worldchoiceperfume.com.
 *
 * ── Two modes ────────────────────────────────────────────────────────────
 * The app ships LIGHT (default) and DARK palettes (lib/palettes.ts).
 * `COLORS` below is a LIVE object: it starts on the light palette and is
 * swapped in place by `toggleTheme()` / `hydrateTheme()`, so every
 * `COLORS.x` read follows the active mode.
 *
 * Because StyleSheet entries bake their values at module load, `create` is
 * wrapped below: every style sheet that contains a palette colour is
 * registered, and toggling swaps the stored values entry-by-entry (the
 * per-entry objects are frozen in dev, so whole entries are replaced).
 * Components then re-render through `useTheme()` — React re-renders the
 * root layout and everything below it on a mode change.
 *
 * Palettes are engineered so the value swap is exact: a colour value means
 * one token in one direction (validated — no ambiguous keys, clean
 * round-trips, pure white/black excluded so literals like QR backgrounds
 * and button labels never flip). Staff module accents are single-valued
 * and never swap — the staff drawers are dark chrome in both modes.
 */
import { useSyncExternalStore } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { StyleSheet } from 'react-native';
import {
  DARK_COLORS,
  LIGHT_COLORS,
  MODULE_ACCENTS,
  type ModuleAccent,
} from './palettes';

export type ThemeMode = 'light' | 'dark';

/** Where the user's choice is remembered between launches. */
const THEME_STORAGE_KEY = 'world-choice-perfume/theme-mode';

/* ------------------------------------------------------------------ *
 * Live palette — LIGHT is the default mode.
 * ------------------------------------------------------------------ */

export const COLORS: typeof LIGHT_COLORS = { ...LIGHT_COLORS };

/**
 * Module accents — added alongside (never in place of) the gold scale so
 * every staff module keeps its own identity. Deliberately NOT mode-aware:
 * they are bright fills behind near-black content and the staff drawers
 * are dark chrome in both modes (see lib/palettes.ts).
 */
export const GD_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.gd };
export const SM_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.sm };
export const CC_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.cc };
export const BA_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.ba };
export const SELLER_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.seller };
export const CASHIER_ACCENT: ModuleAccent = { ...MODULE_ACCENTS.cashier };

/** Soft gold elevation used on primary buttons (website: shadow-gold-500/25). Always the brand gold, in both modes. */
export const GOLD_SHADOW = {
  shadowColor: DARK_COLORS.goldFill,
  shadowOpacity: 0.25,
  shadowRadius: 12,
  shadowOffset: { width: 0, height: 6 },
  elevation: 6,
} as const;

/** Corner radii used across the website (rounded-xl = 12, rounded-2xl = 16). */
export const RADIUS = {
  md: 12,
  lg: 16,
  pill: 999,
} as const;

/** Horizontal page gutter — comfortable margins on every screen size. */
export const GUTTER = 24;

/* ------------------------------------------------------------------ *
 * Style registry — wraps StyleSheet.create so palette colours inside
 * module-level style sheets can be swapped when the mode changes.
 * ------------------------------------------------------------------ */

type StyleBag = Record<string, unknown>;

const registeredStyles: StyleBag[] = [];

const ALL_TOKEN_VALUES = new Set<string>([
  ...Object.values(LIGHT_COLORS),
  ...Object.values(DARK_COLORS),
  ...Object.values(MODULE_ACCENTS).flatMap((accent) => Object.values(accent)),
]);

/** Only style sheets that actually contain palette colours are worth tracking. */
function hasTokenValue(obj: unknown): boolean {
  if (!obj || typeof obj !== 'object') return false;
  for (const key of Object.keys(obj)) {
    const entry = (obj as Record<string, unknown>)[key];
    if (!entry || typeof entry !== 'object') continue;
    for (const prop of Object.keys(entry)) {
      const value = (entry as Record<string, unknown>)[prop];
      if (typeof value === 'string' && ALL_TOKEN_VALUES.has(value)) return true;
    }
  }
  return false;
}

type CreateFn = typeof StyleSheet.create;
const originalCreate: CreateFn = StyleSheet.create;
const patchedCreate = ((obj: unknown) => {
  const result = originalCreate(obj as Parameters<CreateFn>[0]) as unknown;
  if (hasTokenValue(obj)) registeredStyles.push(result as StyleBag);
  return result;
}) as CreateFn;
StyleSheet.create = patchedCreate;

/** One value in one direction: dark value → light value (and the reverse map). */
const TO_LIGHT = new Map<string, string>();
const TO_DARK = new Map<string, string>();

function addPair(dark: string, light: string, token: string): void {
  if (dark === light) return;
  const existingLight = TO_LIGHT.get(dark);
  const existingDark = TO_DARK.get(light);
  if (
    (existingLight !== undefined && existingLight !== light) ||
    (existingDark !== undefined && existingDark !== dark)
  ) {
    // Should never happen — lib/palettes.ts is validated for this.
    console.warn(`[theme] ambiguous colour pair for ${token} (${dark} ↔ ${light}); skipping`);
    return;
  }
  TO_LIGHT.set(dark, light);
  TO_DARK.set(light, dark);
}

for (const key of Object.keys(LIGHT_COLORS) as (keyof typeof LIGHT_COLORS)[]) {
  addPair(DARK_COLORS[key], LIGHT_COLORS[key], `COLORS.${key}`);
}

/**
 * Swap every registered style sheet to the given direction. Entries are
 * frozen in dev, so each changed entry is replaced with a remapped copy —
 * components pick the values up on their next render.
 */
function remapStyles(map: Map<string, string>): void {
  for (const bag of registeredStyles) {
    for (const key of Object.keys(bag)) {
      const entry = bag[key];
      if (!entry || typeof entry !== 'object' || Array.isArray(entry)) continue;
      const source = entry as Record<string, unknown>;
      const next: Record<string, unknown> = {};
      let changed = false;
      for (const prop of Object.keys(source)) {
        const value = source[prop];
        const mapped = typeof value === 'string' ? map.get(value) : undefined;
        if (mapped !== undefined) {
          next[prop] = mapped;
          changed = true;
        } else {
          next[prop] = value;
        }
      }
      if (changed) bag[key] = next;
    }
  }
}

/* ------------------------------------------------------------------ *
 * Mode store — one source of truth; components subscribe via useTheme().
 * ------------------------------------------------------------------ */

let mode: ThemeMode = 'light';
const listeners = new Set<() => void>();

function notify(): void {
  for (const listener of listeners) listener();
}

function applyMode(next: ThemeMode, persist: boolean): void {
  if (next === mode) return;

  remapStyles(next === 'light' ? TO_LIGHT : TO_DARK);
  Object.assign(COLORS, next === 'light' ? LIGHT_COLORS : DARK_COLORS);

  mode = next;
  if (persist) {
    AsyncStorage.setItem(THEME_STORAGE_KEY, next).catch(() => {
      // A failed write only means the choice won't survive a restart.
    });
  }
  notify();
}

/** The current mode (stable snapshot for useSyncExternalStore). */
export function getThemeMode(): ThemeMode {
  return mode;
}

/** Flip between light and dark — the ThemeToggle buttons call this. */
export function toggleTheme(): void {
  applyMode(mode === 'light' ? 'dark' : 'light', true);
}

/**
 * Restore the saved mode after launch. Light stays the default when
 * nothing is stored yet (first run, or storage unavailable).
 */
export async function hydrateTheme(): Promise<void> {
  try {
    const stored = await AsyncStorage.getItem(THEME_STORAGE_KEY);
    if (stored === 'light' || stored === 'dark') applyMode(stored, false);
  } catch {
    // Nothing to restore — keep the default light mode.
  }
}

/**
 * Subscribe a component to the theme. A toggle re-renders every subscriber,
 * and the root layout subscribes too, so the tree below it re-renders and
 * inline `COLORS.x` / `ACCENT.x` reads pick up the new palette.
 */
export function useTheme(): { mode: ThemeMode; isDark: boolean; toggle: () => void } {
  const current = useSyncExternalStore(
    (listener) => {
      listeners.add(listener);
      return () => {
        listeners.delete(listener);
      };
    },
    getThemeMode,
    getThemeMode,
  );
  return { mode: current, isDark: current === 'dark', toggle: toggleTheme };
}
