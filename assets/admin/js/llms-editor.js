/**
 * GEO Forge — llms.txt editor JS.
 *
 * Save + Regenerate buttons, both via REST.
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeLlms || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

    var textarea = document.getElementById('geo-forge-llms-content');
    var saveBtn = document.getElementById('geo-forge-save-llms');
    var regenBtn = document.getElementById('geo-forge-regen-llms');
    var statusEl = document.getElementById('geo-forge-editor-status');

    if (!textarea || !saveBtn) return;

    function showStatus(message, isError) {
        statusEl.querySelector('p').textContent = message;
        statusEl.className = 'notice ' + (isError ? 'notice-error' : 'notice-success');
        statusEl.style.display = 'block';
        setTimeout(function () { statusEl.style.display = 'none'; }, 4000);
    }

    function restFetch(path, opts) {
        opts = opts || {};
        return fetch(restRoot + path, {
            method: opts.method || 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            },
            body: opts.body
        }).then(function (r) {
            return r.json().then(function (body) { return { ok: r.ok, body: body }; });
        });
    }

    saveBtn.addEventListener('click', function () {
        saveBtn.disabled = true;
        restFetch('well-known/llms-txt', {
            method: 'POST',
            body: JSON.stringify({ content: textarea.value })
        })
            .then(function (res) {
                if (res.ok && res.body.success) {
                    showStatus('Saved.', false);
                } else {
                    var msg = (res.body && res.body.error && res.body.error.message) || 'Save failed.';
                    showStatus(msg, true);
                }
            })
            .catch(function () { showStatus('Network error.', true); })
            .finally(function () { saveBtn.disabled = false; });
    });

    if (regenBtn) {
        regenBtn.addEventListener('click', function () {
            if (!window.confirm('Regenerate llms.txt from current store data? Your edits will be overwritten.')) {
                return;
            }
            regenBtn.disabled = true;
            restFetch('well-known/llms-txt/regenerate', { method: 'POST' })
                .then(function (res) {
                    if (res.ok && res.body.success) {
                        textarea.value = res.body.content || '';
                        showStatus('Regenerated.', false);
                    } else {
                        var msg = (res.body && res.body.error && res.body.error.message) || 'Regenerate failed.';
                        showStatus(msg, true);
                    }
                })
                .catch(function () { showStatus('Network error.', true); })
                .finally(function () { regenBtn.disabled = false; });
        });
    }
})();
