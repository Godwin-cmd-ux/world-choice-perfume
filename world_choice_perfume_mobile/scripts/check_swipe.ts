/**
 * Checks the tab-swipe arithmetic (lib/swipe.ts).
 *
 * The gesture itself can only be felt on a phone, but the decisions it makes —
 * did the finger ask for the next tab, and is there a next tab to go to — are
 * plain arithmetic, so they are pinned here. The cases below are the ones a
 * user would notice immediately if they broke: the first and last tab staying
 * put, a scroll never changing tabs, and a diagonal but sideways swipe still
 * counting.
 *
 * Run: npx tsc scripts/check_swipe.ts --outDir .tmp-check --module commonjs \
 *        --target es2020 --skipLibCheck && node .tmp-check/scripts/check_swipe.js
 */
import { SWIPE, swipeDirection, swipeTarget } from '../lib/swipe';

let fail = 0;

function same(what: string, got: unknown, want: unknown) {
  if (got !== want) {
    console.error(`FAIL ${what}: got ${String(got)}, want ${String(want)}`);
    fail++;
  }
}

// --- swipeDirection: which way did the finger ask to go? ---------------------

// A deliberate drag each way.
same('drag left  → next tab', swipeDirection({ dx: -120, dy: 12, vx: -400 }), 1);
same('drag right → previous tab', swipeDirection({ dx: 120, dy: -12, vx: 400 }), -1);

// A flick: fast, and still far enough to be a swipe rather than a tap.
same('flick left (fast, 30 dp)', swipeDirection({ dx: -30, dy: 4, vx: -900 }), 1);
same('flick right (fast, 30 dp)', swipeDirection({ dx: 30, dy: 4, vx: 900 }), -1);

// Too short and too slow: a tap that slid is not a swipe.
same('12 dp nudge, slow', swipeDirection({ dx: -12, dy: 2, vx: -120 }), 0);
same('20 dp nudge, slow', swipeDirection({ dx: 20, dy: 2, vx: 200 }), 0);

// Vertical scrolling never changes tabs, however fast it is.
same('long scroll', swipeDirection({ dx: 4, dy: -320, vx: -200 }), 0);
same('scroll with sideways drift', swipeDirection({ dx: -80, dy: 100, vx: -900 }), 0);
same('scroll flicked sideways', swipeDirection({ dx: -70, dy: 70, vx: -1200 }), 0);

// Diagonal, but still clearly a sideways swipe: a thumb does not travel flat.
same('diagonal swipe (70/30)', swipeDirection({ dx: -70, dy: 30, vx: -200 }), 1);
same('diagonal swipe (30/70)', swipeDirection({ dx: -30, dy: 70, vx: -200 }), 0);

// The dominance rule is what separates the two, so pin it.
if (SWIPE.dominance <= 1) {
  console.error(`FAIL dominance must be above 1 to mean "more sideways than not", got ${SWIPE.dominance}`);
  fail++;
}

// --- swipeTarget: is there a tab to step to? ---------------------------------

const BAR = ['index', 'shop', 'track', 'contacts', 'news', 'staff-access'];

same('home → right', swipeTarget({ index: 0, routeNames: BAR }, 1), 'shop');
same('home → left stops at home', swipeTarget({ index: 0, routeNames: BAR }, -1), null);
same('track → right', swipeTarget({ index: 2, routeNames: BAR }, 1), 'contacts');
same('track → left', swipeTarget({ index: 2, routeNames: BAR }, -1), 'shop');
same('staff → left', swipeTarget({ index: 5, routeNames: BAR }, -1), 'news');
same('staff → right stops at staff', swipeTarget({ index: 5, routeNames: BAR }, 1), null);

// A navigator that has not reported its state yet must not throw or jump.
same('no state at all', swipeTarget(undefined, 1), null);
same('state without route names', swipeTarget({ index: 0 }, 1), null);
same('index past the end', swipeTarget({ index: 9, routeNames: BAR }, 1), null);

console.log(fail ? `${fail} SWIPE CHECK(S) FAILED` : 'SWIPE CHECKS PASS');
process.exit(fail ? 1 : 0);
