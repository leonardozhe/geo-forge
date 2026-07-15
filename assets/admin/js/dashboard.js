/**
 * GEO Forge — Dashboard JS
 * Scan button -> POST /geo-forge/v1/scan -> auto-refresh on success.
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeDashboard || {};
    var root = cfg.restRoot || '', nonce = cfg.restNonce || '';

    var btn = document.getElementById('geo-forge-scan-btn');
    var statusEl = document.getElementById('geo-forge-scan-status');
    if (!btn) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        statusEl.textContent = 'Scanning... please wait 15-90s';
        statusEl.style.color = '#64748b';
        var errBox = document.getElementById('geo-forge-error');
        if (errBox) errBox.style.display = 'none';

        fetch(root + 'scan', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (body) {
            btn.disabled = false;
            if (body && body.success) {
                statusEl.textContent = '✅ Scan complete — refreshing...';
                statusEl.style.color = '#16a34a';
                setTimeout(function () { location.reload(); }, 800);
            } else {
                var msg = (body && body.error && body.error.message) || 'Scan failed.';
                statusEl.textContent = '❌ ' + msg;
                statusEl.style.color = '#dc2626';
                if (errBox) { errBox.innerHTML = '<p>' + msg + '</p>'; errBox.style.display = 'block'; }
            }
        })
        .catch(function () {
            btn.disabled = false;
            statusEl.textContent = '❌ Network error or timeout.';
            statusEl.style.color = '#dc2626';
        });
    });
})();
