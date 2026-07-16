/**
 * GEO Forge — Logs page JS.
 *
 * Handles the "Clear all logs" button. POSTs to the REST endpoint,
 * reloads the page on success (table is server-rendered).
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeLogs || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    var i18n = cfg.i18n || {};

    var btn = document.getElementById('geo-forge-clear-logs');
    var statusEl = document.getElementById('geo-forge-clear-status');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        if (!window.confirm(i18n.confirmClear || 'Clear all log entries?')) {
            return;
        }

        btn.disabled = true;
        statusEl.textContent = '';

        fetch(restRoot + 'logs/clear', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                if (body && body.success) {
                    statusEl.textContent = '✅ ' + (i18n.cleared || 'Logs cleared.');
                    statusEl.style.color = '#00a32a';
                    setTimeout(function () { window.location.reload(); }, 600);
                } else {
                    var msg = (body && body.error && body.error.message) || 'Failed.';
                    statusEl.textContent = '❌ ' + msg;
                    statusEl.style.color = '#d63638';
                    btn.disabled = false;
                }
            })
            .catch(function () {
                statusEl.textContent = '❌ ' + (i18n.unknownError || 'Unknown error.');
                statusEl.style.color = '#d63638';
                btn.disabled = false;
            });
    });
})();
