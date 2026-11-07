/**
 * Live sidebar counts.
 *
 * Every 15 seconds the page asks for the signed-in person's counts, updates
 * the badges, and — on a list/dashboard screen — reloads once when one of the
 * counts that page shows has increased. Reloading is deliberately paused while
 * a form has unsubmitted text or a dialog is open, and the scroll position is
 * restored afterwards. No page is refreshed just because a different module
 * changed.
 */
(function () {
    'use strict';

    var root = document.getElementById('live-updates');
    if (!root || typeof window.fetch !== 'function') {
        return;
    }

    var url = root.getAttribute('data-url');
    var keys = (root.getAttribute('data-keys') || '').split(',').filter(function (key) {
        return key !== '';
    });
    var initial = {};
    try {
        initial = JSON.parse(root.getAttribute('data-counts') || '{}') || {};
    } catch (e) {
        initial = {};
    }

    var refresh = root.getAttribute('data-refresh') === '1';
    var baseline = {};
    keys.forEach(function (key) {
        baseline[key] = Number(initial[key] || 0);
    });

    var loadedAt = Date.now();
    var polling = false;

    function setBadge(key, count) {
        var nodes = document.querySelectorAll('[data-nav-count="' + key + '"]');

        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            if (count > 0) {
                node.textContent = count > 99 ? '99+' : String(count);
                node.classList.remove('hidden');
                node.setAttribute('aria-label', count + ' new');
            } else {
                node.textContent = '0';
                node.classList.add('hidden');
            }
        }
    }

    /**
     * True when reloading would interrupt work: a field is focused, a form has
     * unsaved text, a file is attached, or a dialog is open.
     */
    function isBusy() {
        var active = document.activeElement;
        if (active && active.matches && active.matches('input, textarea, select, [contenteditable="true"]')) {
            return true;
        }

        if (document.querySelector('dialog[open], [role="dialog"][aria-hidden="false"]')) {
            return true;
        }

        // Selects are covered by the active-element check above. Comparing a
        // select's default is unreliable (a filter starting on a non-first
        // option would look dirty forever), so only text fields are scanned.
        var fields = document.querySelectorAll('form input, form textarea');

        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            var type = (field.type || '').toLowerCase();

            if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
                continue;
            }

            if (type === 'file') {
                if (field.files && field.files.length) {
                    return true;
                }
                continue;
            }

            if (type === 'checkbox' || type === 'radio') {
                if (field.checked !== field.defaultChecked) {
                    return true;
                }
                continue;
            }

            if (field.value !== field.defaultValue) {
                return true;
            }
        }

        return false;
    }

    function poll() {
        if (polling || document.hidden) {
            return;
        }

        polling = true;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                var counts = data.counts || {};

                Object.keys(counts).forEach(function (key) {
                    setBadge(key, Number(counts[key] || 0));
                });

                if (!refresh) {
                    return;
                }

                var changed = false;

                keys.forEach(function (key) {
                    var next = Number(counts[key] || 0);

                    if (next > baseline[key]) {
                        changed = true;
                    } else {
                        // A decrease is safe to accept; an increase is kept as
                        // pending until the page is free to reload.
                        baseline[key] = next;
                    }
                });

                if (!changed || Date.now() - loadedAt < 8000 || isBusy()) {
                    return;
                }

                try {
                    sessionStorage.setItem('wcp-live-scroll', String(window.scrollY));
                } catch (e) {
                    // Private mode: reload still works, only the scroll is lost.
                }

                window.location.reload();
            })
            .catch(function () {
                // Offline or signed out; the next poll retries.
            })
            .then(function () {
                polling = false;
            });
    }

    window.setInterval(poll, 15000);
    window.setTimeout(poll, 2500);

    window.addEventListener('load', function () {
        try {
            var scroll = sessionStorage.getItem('wcp-live-scroll');

            if (scroll !== null) {
                sessionStorage.removeItem('wcp-live-scroll');
                window.scrollTo(0, parseInt(scroll, 10) || 0);
            }
        } catch (e) {
            // Nothing to restore.
        }
    });
})();
