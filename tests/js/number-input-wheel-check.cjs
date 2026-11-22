/*
 * Behavioural check for resources/views/partials/number-input-wheel.blade.php
 *
 *   node tests/js/number-input-wheel-check.cjs
 *
 * The guard must stop a wheel from changing the number being typed, but it must
 * not freeze the page: scrolling anywhere else still works.
 */
const fs = require('fs');
const path = require('path');

const partial = fs.readFileSync(
  path.join(__dirname, '..', '..', 'resources', 'views', 'partials', 'number-input-wheel.blade.php'),
  'utf8'
);
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];

let wheelHandler = null;
let wheelOptions = null;
const document = {
  activeElement: null,
  addEventListener(type, fn, options) {
    if (type === 'wheel') {
      wheelHandler = fn;
      wheelOptions = options;
    }
  },
};
const window = {};

new Function('document', 'window', script)(document, window);

if (typeof wheelHandler !== 'function') {
  throw new Error('The partial did not register a wheel handler.');
}
if (!wheelOptions || wheelOptions.passive !== false) {
  throw new Error('The wheel handler must be non-passive so it can call preventDefault().');
}

function field(kinds) {
  // The real selector is one comma-separated string, e.g.
  // input[type="number"], input[inputmode="decimal"].
  return { matches: (selector) => kinds.some((kind) => selector.includes(kind)) };
}

function wheel(target) {
  let prevented = false;
  wheelHandler({ target, preventDefault: () => { prevented = true; } });
  return prevented;
}

const number = field(['type="number"']);
const decimal = field(['inputmode="decimal"']);
const text = field([]);

const focusedNumber = () => { document.activeElement = number; return wheel(number); };
const unfocusedNumber = () => { document.activeElement = text; return wheel(number); };
const focusedDecimal = () => { document.activeElement = decimal; return wheel(decimal); };
const focusedText = () => { document.activeElement = text; return wheel(text); };

const scenarios = [
  ['focused number field', focusedNumber(), true],
  ['focused decimal inputmode field', focusedDecimal(), true],
  ['number field without focus', unfocusedNumber(), false],
  ['ordinary text field', focusedText(), false],
];

for (const [name, actual, expected] of scenarios) {
  if (actual !== expected) {
    throw new Error(`${name}: expected preventDefault=${expected}, got ${actual}`);
  }
}

console.log('number-input-wheel-check: 4 scenarios passed');
