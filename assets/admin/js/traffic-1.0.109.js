/**
 * GEO Forge — Traffic page JS.
 * Handles deleting LLM 404 records (single + all) from the
 * "Missing Content — LLM 404s" card.
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeTraffic || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

    function showStatus(message, isError) {
        var el = document.getElementById('geo-forge-traffic-status');
        if (!el) return;
        el.innerHTML = '<p>' + message + '</p>';
        el.className = 'gf-notice ' + (isError ? 'gf-notice-error' : 'gf-notice-success');
        el.style.display = 'block';
        setTimeout(function () { el.style.display = 'none'; }, 5000);
    }

    function del(path, btn) {
        var label = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = '…'; }
        fetch(restRoot + path, {
            method: 'DELETE', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce }
        }).then(function (r) {
            return r.json().then(function (body) { return { ok: r.ok, body: body }; });
        }).then(function (res) {
            if (res.ok && res.body.success) {
                showStatus(res.body.message || 'Done.', false);
                // Reload so the card and counts re-render from the server.
                setTimeout(function () { location.reload(); }, 400);
            } else {
                showStatus((res.body && res.body.error && res.body.error.message) || 'Failed.', true);
                if (btn) { btn.disabled = false; btn.textContent = label; }
            }
        }).catch(function () {
            showStatus('Network error.', true);
            if (btn) { btn.disabled = false; btn.textContent = label; }
        });
    }

    document.querySelectorAll('.geo-forge-404-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm(cfg.i18n && cfg.i18n.confirmDelete ? cfg.i18n.confirmDelete : 'Delete this 404 record?')) return;
            del('traffic/404/' + btn.getAttribute('data-id'), btn);
        });
    });

    var clearBtn = document.querySelector('.geo-forge-404-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (!window.confirm(cfg.i18n && cfg.i18n.confirmClear ? cfg.i18n.confirmClear : 'Delete ALL LLM 404 records? This cannot be undone.')) return;
            del('traffic/404', clearBtn);
        });
    }
})();
