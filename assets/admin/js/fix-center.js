/**
 * GEO Forge — Fix Center JS.
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeFixer || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    console.log('[GEO Forge Fixer] script loaded. restRoot=' + restRoot);

    function showStatus(message, isError) {
        var el = document.getElementById('geo-forge-fix-status');
        if (!el) return;
        el.innerHTML = '<p>' + message + '</p>';
        el.className = 'gf-notice ' + (isError ? 'gf-notice-error' : 'gf-notice-success');
        el.style.display = 'block';
        setTimeout(function () { el.style.display = 'none'; }, 5000);
    }

    function restFetch(path, opts) {
        opts = opts || {};
        return fetch(restRoot + path, {
            method: opts.method || 'POST', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' }
        }).then(function (r) {
            return r.json().then(function (body) { return { ok: r.ok, body: body }; });
        });
    }

    function updateRow(fixId, status, appliedAt) {
        var row = document.querySelector('tr[data-fix-id="' + fixId + '"]');
        if (!row) return;
        var statusCell = row.querySelector('.geo-forge-fix-status-cell');
        if (statusCell) {
            var labels = { applied: '✅ Applied', verified: '✅✅ Verified', rolled_back: '⏪ Rolled back', failed: '❌ Failed', pending: '○ Pending' };
            statusCell.textContent = labels[status] || status;
        }
        var isApplied = status === 'applied' || status === 'verified';
        var applyBtn = row.querySelector('.geo-forge-fix-apply');
        var verifyBtn = row.querySelector('.geo-forge-fix-verify');
        var rollbackBtn = row.querySelector('.geo-forge-fix-rollback');
        if (applyBtn) applyBtn.disabled = isApplied;
        if (verifyBtn) verifyBtn.disabled = !isApplied;
        if (rollbackBtn) rollbackBtn.disabled = !isApplied;
    }

    function bind(selector, actionPath) {
        document.querySelectorAll(selector).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var fixId = btn.getAttribute('data-fix');
                var originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = '…';
                console.log('[GEO Forge Fixer] ' + actionPath.replace('{id}', fixId) + ' clicked');
                restFetch(actionPath.replace('{id}', fixId))
                    .then(function (res) {
                        console.log('[GEO Forge Fixer] response:', res);
                        if (res.ok && res.body.success) {
                            showStatus(res.body.message || 'Done.', false);
                            // Reload the page so all columns (status, applied_at, button states)
                            // are re-rendered from the server. Small delay so user sees the toast.
                            setTimeout(function () { location.reload(); }, 800);
                        } else {
                            showStatus((res.body && res.body.error && res.body.error.message) || 'Failed.', true);
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(function (err) {
                        console.error('[GEO Forge Fixer] fetch error:', err);
                        showStatus('Network error.', true);
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
            });
        });
    }

    bind('.geo-forge-fix-apply', 'fixes/{id}/apply');
    bind('.geo-forge-fix-verify', 'fixes/{id}/verify');
    bind('.geo-forge-fix-rollback', 'fixes/{id}/rollback');
    console.log('[GEO Forge Fixer] listeners bound: apply=' + document.querySelectorAll('.geo-forge-fix-apply').length + ', verify=' + document.querySelectorAll('.geo-forge-fix-verify').length + ', rollback=' + document.querySelectorAll('.geo-forge-fix-rollback').length);
})();
