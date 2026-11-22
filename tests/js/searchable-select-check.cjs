/*
 * Behavioural check for resources/views/partials/searchable-select.blade.php
 *
 *   node tests/js/searchable-select-check.cjs
 *
 * The PHPUnit suite proves the picker is rendered; this proves the typed query
 * behind it: spaces are separate terms, so "ocean blue" still finds
 * "Blue Ocean Perfume" and "Reef 33" stays out of the way.
 */
const fs = require('fs');
const path = require('path');

const partial = fs.readFileSync(
  path.join(__dirname, '..', '..', 'resources', 'views', 'partials', 'searchable-select.blade.php'),
  'utf8'
);
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];

function makeOption(label) {
  return {
    label,
    style: { display: '' },
    getAttribute(name) {
      return name === 'data-ss-search' ? label.toLowerCase() : null;
    },
  };
}

const listeners = {};
const document = {
  addEventListener(type, fn) { listeners[type] = fn; },
  querySelectorAll() { return []; },
};
const window = {};

new Function('document', 'window', script)(document, window);

if (typeof listeners.input !== 'function') {
  throw new Error('The partial did not register its input handler.');
}

function filter(query, labels) {
  const options = labels.map(makeOption);
  const box = { querySelectorAll: () => options };
  const search = {
    value: query,
    closest: () => search,
  };
  search.closest = (selector) => (selector === '[data-ss-search]' ? search : box);

  listeners.input({ target: search });

  return options
    .filter((option) => option.style.display !== 'none')
    .map((option) => option.label);
}

function assertEqual(actual, expected, message) {
  const a = JSON.stringify(actual);
  const e = JSON.stringify(expected);
  if (a !== e) {
    throw new Error(`${message}\n  expected ${e}\n  actual   ${a}`);
  }
}

const catalogue = ['Blue Ocean Perfume', 'Ocean Blue', 'Reef 33'];

assertEqual(filter('ocean blue', catalogue), ['Blue Ocean Perfume', 'Ocean Blue'], 'spaces must be separate search terms');
assertEqual(filter('reef 33', catalogue), ['Reef 33'], 'a phrase must find its own product');
assertEqual(filter('blue', catalogue), ['Blue Ocean Perfume', 'Ocean Blue'], 'a single word still works');
assertEqual(filter('', catalogue), catalogue, 'an empty query shows every option');
assertEqual(filter('desert', catalogue), [], 'a miss shows nothing');

console.log('searchable-select-check: 5 scenarios passed');
