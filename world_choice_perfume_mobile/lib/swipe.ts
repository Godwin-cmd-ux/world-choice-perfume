/**
 * The arithmetic behind the tab swipe — no React, no native modules, so it can
 * be reasoned about (and checked) on its own. components/swipetabs.tsx is the
 * gesture that feeds it.
 */

/** How far a finger has to travel before it counts as a swipe, in dp. */
export const SWIPE = {
  /** Travelled before the pan takes the drag from the list underneath it. */
  activate: 18,
  /** Travelled up or down before the drag belongs to the page instead. */
  fail: 18,
  /** Travelled for a slow, deliberate swipe to count. */
  distance: 56,
  /** …or flicked this fast (dp/s), after at least `flick` of travel. */
  velocity: 650,
  flick: 24,
  /**
   * How much further sideways than up-and-down a drag must travel to count.
   * A thumb rarely drags on a perfect horizontal, and the 1.5 ratio keeps a
   * natural diagonal swipe working while a genuine scroll stays a scroll.
   */
  dominance: 1.5,
} as const;

/**
 * The direction a finished drag points at: 1 for the next tab, -1 for the
 * previous one, 0 when the drag does not ask for either.
 *
 * A drag that went at least as far up or down as it did sideways is a scroll
 * and never a tab change — that is what keeps the tall pages scrollable, even
 * when a scroll takes a thumb slightly sideways with it. A short nudge (a tap
 * that slid) is 0 as well: only a deliberate 56 dp drag or a quick flick
 * counts.
 */
export function swipeDirection(sample: { dx: number; dy: number; vx: number }): -1 | 0 | 1 {
  const dx = Math.abs(sample.dx);

  // Mostly sideways, or it is a scroll rather than a swipe.
  if (dx < SWIPE.dominance * Math.abs(sample.dy)) return 0;

  const deliberate = dx >= SWIPE.distance;
  const flicked = Math.abs(sample.vx) >= SWIPE.velocity && dx >= SWIPE.flick;

  if (!deliberate && !flicked) return 0;

  // Finger to the left → the tab to the right of this one slides in, matching
  // the order the bar is drawn in.
  return sample.dx < 0 ? 1 : -1;
}

/**
 * The route `direction` steps to, or null at the ends of the bar: Home does not
 * swipe back past itself and Staff does not swipe on past itself.
 */
export function swipeTarget(
  state: { index?: number; routeNames?: readonly string[] } | undefined,
  direction: -1 | 1,
): string | null {
  const names = state?.routeNames ?? [];
  const next = (state?.index ?? 0) + direction;

  if (next < 0 || next >= names.length) return null;

  return names[next] ?? null;
}
