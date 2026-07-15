/**
 * GEO Forge — Settings JS.
 *
 * Adds a "Health Check" button next to the API key field that POSTs to
 * the REST endpoint and shows an inline ok/error status.
 *
 * Reads config from window.GeoForgeSettings (localized by Admin.php).
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeSettings || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    var i18n = cfg.i18n || {};

    // Build the button after the API key row.
    var keyInput = document.getElementById('geo_forge_api_key');
    if (!keyInput) return;

    var row = keyInput.closest('tr');
    if (!row) return;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'button button-secondary';
    btn.textContent = 'Health Check';
    btn.style.marginLeft = '8px';

    var statusEl = document.createElement('span');
    statusEl.className = 'geo-forge-muted';
    statusEl.style.marginLeft = '8px';
    statusEl.setAttribute('aria-live', 'polite');

    var wrapper = document.createElement('span');
    wrapper.appendChild(btn);
    wrapper.appendChild(statusEl);
    keyInput.parentNode.insertBefore(wrapper, keyInput.nextSibling);

    btn.addEventListener('click', function () {
        btn.disabled = true;
        statusEl.textContent = i18n.checking || 'Checking…';
        statusEl.style.color = '';

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
                    statusEl.textContent = '✅ ' + (i18n.ok || 'Connected.');
                    statusEl.style.color = '#00a32a';
                } else {
                    var msg = (body && body.error && body.error.message) || (i18n.failed || 'Connection failed.');
                    statusEl.textContent = '❌ ' + msg;
                    statusEl.style.color = '#d63638';
                }
            })
            .catch(function () {
                statusEl.textContent = '❌ ' + (i18n.unknownError || 'Unknown error.');
                statusEl.style.color = '#d63638';
            })
            .finally(function () {
                btn.disabled = false;
            });
    });
})();
