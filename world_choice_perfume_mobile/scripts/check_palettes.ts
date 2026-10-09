import { DARK_COLORS, LIGHT_COLORS, MODULE_ACCENTS } from '../lib/palettes';

type P = typeof LIGHT_COLORS;
const keys = Object.keys(LIGHT_COLORS) as (keyof P)[];
let fail = 0;

// 1. Identical key sets.
const dKeys = Object.keys(DARK_COLORS);
if (keys.length !== dKeys.length || !keys.every((k) => dKeys.includes(k))) {
  console.error('KEY MISMATCH', keys, dKeys);
  fail++;
}

// 2. Per-palette uniqueness for mode-specific (differing) tokens.
for (const [name, pal] of [['LIGHT', LIGHT_COLORS], ['DARK', DARK_COLORS]] as const) {
  const seen = new Map<string, string>();
  for (const k of keys) {
    if (LIGHT_COLORS[k] === DARK_COLORS[k]) continue; // static token
    const v = pal[k];
    if (seen.has(v)) { console.error(`${name}: ${k} shares value with ${seen.get(v)}: ${v}`); fail++; }
    seen.set(v, k);
    if (v === '#FFFFFF' || v === '#000000') {
      console.error(`${name}: mode-specific token ${k} uses pure ${v}`); fail++;
    }
  }
}

// 3. Swap maps: TO_LIGHT keys (dark values) unique, round-trip clean.
const toLight = new Map<string, string>();
const toDark = new Map<string, string>();
for (const k of keys) {
  const d = DARK_COLORS[k]; const l = LIGHT_COLORS[k];
  if (d === l) continue;
  if (toLight.has(d)) { console.error(`TO_LIGHT key collision: ${d} (${k} vs ${toLight.get(d)})`); fail++; }
  if (toDark.has(l)) { console.error(`TO_DARK key collision: ${l} (${k} vs ${toDark.get(l)})`); fail++; }
  toLight.set(d, l); toDark.set(l, d);
}
for (const [d, l] of toLight) if (toDark.get(l) !== d) { console.error(`round-trip broken: ${d}`); fail++; }

// 4. Static (same-value) tokens must never be map keys.
for (const k of keys) {
  if (LIGHT_COLORS[k] !== DARK_COLORS[k]) continue;
  if (toLight.has(LIGHT_COLORS[k]) || toDark.has(LIGHT_COLORS[k])) {
    console.error(`static token ${k} value is a swap key`); fail++;
  }
}

// 5. Module accents must never collide with swap keys (drawers stay dark in both modes).
for (const [mod, accent] of Object.entries(MODULE_ACCENTS)) {
  for (const [prop, v] of Object.entries(accent)) {
    if (typeof v !== 'string') continue;
    if (toLight.has(v)) { console.error(`ACCENT ${mod}.${prop} (${v}) would flip →light`); fail++; }
    if (toDark.has(v)) { console.error(`ACCENT ${mod}.${prop} (${v}) would flip →dark`); fail++; }
    for (const k of keys) {
      if (v !== LIGHT_COLORS[k]) continue;
      if (LIGHT_COLORS[k] === DARK_COLORS[k]) continue; // sharing a static value is fine
      console.error(`ACCENT ${mod}.${prop} equals palette ${k} (${v})`); fail++;
    }
  }
}

console.log(fail === 0 ? 'PASS: all palette invariants hold' : `FAIL: ${fail} issue(s)`);
process.exit(fail === 0 ? 0 : 1);
