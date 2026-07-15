/**
 * GEO Forge — Fix Center JS.
 *
 * Drives Apply / Verify / Rollback buttons via REST. On success, updates
 * the row's status cell and button states in place.
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeFixer || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

    var statusEl = document.getElementById('geo-forge-fix-status');

    function showStatus(message, isError) {
        statusEl.querySelector('p').textContent = message;
        statusEl.className = 'notice ' + (isError ? 'notice-error' : 'notice-success');
        statusEl.style.display = 'block';
        setTimeout(function () { statusEl.style.display = 'none'; }, 5000);
    }

    function restFetch(path, opts) {
        opts = opts || {};
        return fetch(restRoot + path, {
            method: opts.method || 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            }
        }).then(function (r) {
            return r.json().then(function (body) { return { ok: r.ok, body: body }; });
        });
    }

    function updateRow(fixId, status, appliedAt) {
        var row = document.querySelector('tr[data-fix-id="' + fixId + '"]');
        if (!row) return;

        var statusCell = row.querySelector('.geo-forge-fix-status-cell');
        if (statusCell) {
            var emoji = ({
                applied: '✅ Applied',
                verified: '✅✅ Verified',
                rolled_back: '⏪ Rolled back',
                failed: '❌ Failed',
                pending: '○ Pending'
            })[status] || status;
            statusCell.textContent = emoji;
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

                restFetch(actionPath.replace('{id}', fixId))
                    .then(function (res) {
                        if (res.ok && res.body.success) {
                            var newStatus = res.body.status || 'applied';
                            var appliedAt = res.body.applied_at || null;
                            updateRow(fixId, newStatus, appliedAt);
                            showStatus(res.body.message || 'Done.', false);
                        } else {
                            var msg = (res.body && res.body.error && res.body.error.message) || 'Failed.';
                            showStatus(msg, true);
                        }
                    })
                    .catch(function () { showStatus('Network error.', true); })
                    .finally(function () {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
            });
        });
    }

    bind('.geo-forge-fix-apply', 'fixes/{id}/apply');
    bind('.geo-forge-fix-verify', 'fixes/{id}/verify');
    bind('.geo-forge-fix-rollback', 'fixes/{id}/rollback');
})();
