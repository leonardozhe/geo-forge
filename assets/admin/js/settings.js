/**
 * GEO Forge — Settings JS.
 *
 * Health Check button next to the API key field. POSTs to REST endpoint
 * and shows inline ✅/❌ status.
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeSettings || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    var i18n = cfg.i18n || {};

    var btn = document.getElementById('geo-forge-health-btn');
    var statusEl = document.getElementById('geo-forge-health-status');
    if (!btn || !statusEl) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        statusEl.textContent = '…';

        fetch(restRoot + 'health-check', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                if (body && body.ok) {
                    statusEl.textContent = '✅ Connected';
                    statusEl.className = 'geo-forge-status geo-forge-status-ok';
                } else {
                    var msg = (body && body.error && body.error.message) || 'Connection failed — check your API key.';
                    statusEl.textContent = '❌ ' + msg;
                    statusEl.className = 'geo-forge-status geo-forge-status-err';
                }
            })
            .catch(function () {
                statusEl.textContent = '❌ Network error.';
                statusEl.className = 'geo-forge-status geo-forge-status-err';
            })
            .finally(function () {
                btn.disabled = false;
            });
    });
})();
