/*
 * Behavioural check for resources/views/partials/swipe-nav.blade.php
 *
 *   node tests/js/swipe-nav-check.cjs
 *
 * Runs the partial's own script against a hand-rolled DOM (no jsdom needed) and
 * asserts what a swipe actually does: direction, the ends of the tab list, and
 * every case the gesture is supposed to leave alone — an open dialog, a
 * sideways-scrolling strip, a form field, a drag that is really a scroll.
 *
 * It is not part of the PHPUnit suite (there is no JS runner here); it is the
 * script to run after touching the partial.
 */
const fs = require('fs');
const path = require('path');

const partial = fs.readFileSync(
  path.join(__dirname, '..', '..', 'resources', 'views', 'partials', 'swipe-nav.blade.php'),
  'utf8'
);
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];

const TABS = [
  { label: 'Home', url: 'https://site.test/' },
  { label: 'Shop', url: 'https://site.test/products' },
  { label: 'Track Order', url: 'https://site.test/orders/track' },
  { label: 'Contact', url: 'https://site.test/#contact' },
  { label: 'News', url: 'https://site.test/news' },
  { label: 'Staff', url: 'https://site.test/login' },
];

/* ---------------------------------------------------------------- DOM --- */
function makeEl(tag, opts = {}) {
  const el = {
    tagName: tag.toUpperCase(),
    attributes: opts.attributes || {},
    style: Object.assign({ display: 'block', visibility: 'visible', opacity: '1', overflowX: 'visible' }, opts.style),
    scrollWidth: opts.scrollWidth || 100,
    clientWidth: opts.clientWidth || 100,
    rect: opts.rect || { width: 100, height: 100 },
    children: opts.children || [],
    parentElement: null,
    _classes: new Set((opts.className || '').split(' ').filter(Boolean)),
  };

  el.classList = {
    contains: (c) => el._classes.has(c),
    add: (c) => el._classes.add(c),
    toggle: (c, on) => (on ? el._classes.add(c) : el._classes.delete(c)),
  };

  el.getAttribute = (name) => (name in el.attributes ? el.attributes[name] : null);
  el.setAttribute = (name, value) => { el.attributes[name] = String(value); };
  el.getBoundingClientRect = () => el.rect;

  el.child = (child) => { child.parentElement = el; el.children.push(child); return child; };
  el.appendChild = (child) => el.child(child);

  el.closest = (selector) => {
    const parts = selector.split(',').map((s) => s.trim());
    for (let node = el; node; node = node.parentElement) {
      const hit = parts.some((part) => {
        if (part.startsWith('[') && part.endsWith(']')) {
          const attr = part.slice(1, -1).split('=')[0];
          return attr in node.attributes;
        }
        return node.tagName === part.toUpperCase();
      });
      if (hit) return node;
    }
    return null;
  };

  return el;
}

function runScenario(name, { index, target, dx, dy, duration = 200, dialogHidden = true, blockers = [] }) {
  const holder = makeEl('div', {
    attributes: {
      'data-swipe-index': String(index),
      'data-swipe-tabs': JSON.stringify(TABS),
    },
  });

  // The staff-code modal is hidden by default, exactly as the layout renders it.
  const staffModal = makeEl('div', { className: dialogHidden ? 'hidden' : '' });
  staffModal.attributes = { id: 'staffLoginModal' };
  if (dialogHidden) staffModal._classes.add('hidden');

  const navigated = { url: null };
  const prefetched = [];

  const listeners = {};
  const document = {
    body: makeEl('body'),
    head: makeEl('head'),
    addEventListener: (type, fn) => { (listeners[type] = listeners[type] || []).push(fn); },
    querySelector: (selector) => (selector === '[data-swipe-nav]' ? holder : null),
    querySelectorAll: (selector) => (selector === '[data-swipe-block]' ? blockers : []),
    getElementById: (id) => (id === 'staffLoginModal' ? staffModal : null),
    createElement: (tag) => {
      const el = makeEl(tag);
      if (tag === 'link') {
        Object.defineProperty(el, 'href', {
          set(value) { prefetched.push(value); },
          get() { return prefetched[prefetched.length - 1]; },
        });
      }
      return el;
    },
  };

  const window = {
    getComputedStyle: (el) => el.style,
    getSelection: () => '',
    location: {
      get href() { return navigated.url; },
      set href(value) { navigated.url = value; },
    },
  };

  new Function('window', 'document', script)(window, document);

  const fire = (type, touch) => {
    (listeners[type] || []).forEach((fn) => fn({
      target,
      touches: touch ? [touch] : [],
      key: '',
      preventDefault() {},
    }));
  };

  const start = Date.now();
  const RealNow = Date.now;
  Date.now = () => start;                       // freeze touchstart
  fire('touchstart', { clientX: 200, clientY: 300 });
  Date.now = () => start + duration;            // then move forward in time
  fire('touchmove', { clientX: 200 + dx, clientY: 300 + dy });
  fire('touchend', null);
  Date.now = RealNow;

  return { name, navigated: navigated.url, prefetched };
}

/* ------------------------------------------------------------- cases --- */
const plain = makeEl('div');
const input = makeEl('input');
const strip = makeEl('div', { style: { overflowX: 'auto' }, scrollWidth: 900, clientWidth: 300 });
const stripChild = strip.child(makeEl('div'));
strip.parentElement = makeEl('body');
input.parentElement = makeEl('body');
plain.parentElement = makeEl('body');

const visibleBlocker = makeEl('div', { rect: { width: 300, height: 200 } });
const hiddenBlocker = makeEl('div', { rect: { width: 0, height: 0 } });

const cases = [
  ['home: finger left goes to the next tab', { index: 0, target: plain, dx: -120, dy: 5 }, TABS[1].url],
  ['shop: finger left goes to track order', { index: 1, target: plain, dx: -90, dy: 10 }, TABS[2].url],
  ['staff: finger right goes back to news', { index: 5, target: plain, dx: 90, dy: -10 }, TABS[4].url],
  ['home: finger right has nothing behind it', { index: 0, target: plain, dx: 120, dy: 0 }, null],
  ['staff: finger left has nothing after it', { index: 5, target: plain, dx: -120, dy: 0 }, null],
  ['a page outside the tab set does not swipe', { index: -1, target: plain, dx: -120, dy: 0 }, null],
  ['a drag too short to be a swipe', { index: 0, target: plain, dx: -30, dy: 0 }, null],
  ['a slow drag is a scroll, not a swipe', { index: 0, target: plain, dx: -120, dy: 0, duration: 1500 }, null],
  ['a mostly-vertical drag is a scroll', { index: 0, target: plain, dx: -90, dy: 200 }, null],
  ['a drag starting in a form field belongs to the field', { index: 0, target: input, dx: -120, dy: 0 }, null],
  ['a drag starting in a sideways strip belongs to the strip', { index: 0, target: stripChild, dx: -120, dy: 0 }, null],
  ['an open staff-code dialog owns the screen', { index: 0, target: plain, dx: -120, dy: 0, dialogHidden: false }, null],
  ['a visible overlay owns the screen', { index: 0, target: plain, dx: -120, dy: 0, blockers: [visibleBlocker] }, null],
  ['a hidden overlay does not block', { index: 0, target: plain, dx: -120, dy: 0, blockers: [hiddenBlocker] }, TABS[1].url],
];

let failures = 0;
for (const [name, options, expected] of cases) {
  const result = runScenario(name, options);
  const ok = result.navigated === expected;
  if (!ok) {
    failures++;
    console.log(`FAIL  ${name}\n      expected ${expected}\n      got      ${result.navigated}`);
  } else {
    console.log(`ok    ${name} -> ${result.navigated === null ? 'stayed put' : result.navigated}`);
  }

  // every gesture that navigates also prefetches the page it is heading to
  if (expected && result.prefetched.length !== 1) {
    failures++;
    console.log(`FAIL  ${name}: prefetch ${result.prefetched.length} link(s), expected 1`);
  }
}

console.log(failures === 0 ? '\nall swipe cases behave' : `\n${failures} case(s) wrong`);
process.exit(failures === 0 ? 0 : 1);
