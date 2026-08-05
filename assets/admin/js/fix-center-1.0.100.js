/**
 * GEO Forge — Fix Center JS.
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeFixer || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

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
            var labels = { applied: '✅ Applied', verified: '✅✅ Verified', rolled_back: '⏪ Rolled back', failed: '❌ Failed', pending: '○ Pending', covered: '👁 Audit mode', ignored: '🚫 Ignored' };
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
                restFetch(actionPath.replace('{id}', fixId))
                    .then(function (res) {
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
                        showStatus('Network error.', true);
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
            });
        });
    }

    // Audit updates the row in place (no reload) so the result is visible.
    document.querySelectorAll('.geo-forge-fix-audit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var fixId = btn.getAttribute('data-fix');
            var originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '…';
            restFetch('fixes/' + fixId + '/audit')
                .then(function (res) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    if (res.ok && res.body.success) {
                        showStatus(res.body.message || 'Audit done.', false);
                        var row = document.querySelector('tr[data-fix-id="' + fixId + '"]');
                        if (!row) { return; }
                        var pass = !!res.body.pass;
                        var line = row.querySelector('.geo-forge-fix-audit-line');
                        var txt = (pass ? '✅ ' : '❌ ') + (res.body.message || 'Audit complete.');
                        if (line) {
                            line.textContent = txt;
                            line.style.color = pass ? '#16a34a' : '#dc2626';
                        } else {
                            // No result line yet — create one before the note.
                            var note = row.querySelector('.geo-forge-fix-note');
                            var span = document.createElement('span');
                            span.className = 'geo-forge-fix-audit-line';
                            span.style.cssText = 'font-size:11px;color:' + (pass ? '#16a34a' : '#dc2626') + ';display:block;';
                            span.textContent = txt;
                            (note ? note.parentNode : row.cells[0]).insertBefore(span, note || null);
                        }
                        var coverBtn = row.querySelector('.geo-forge-fix-cover');
                        if (coverBtn) { coverBtn.disabled = pass; coverBtn.title = pass ? 'Audit passed — nothing to override.' : 'Take over after this failed audit'; }
                    } else {
                        showStatus((res.body && res.body.error && res.body.error.message) || 'Audit failed.', true);
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    showStatus('Network error.', true);
                });
        });
    });

    bind('.geo-forge-fix-apply', 'fixes/{id}/apply');
    bind('.geo-forge-fix-verify', 'fixes/{id}/verify');
    bind('.geo-forge-fix-rollback', 'fixes/{id}/rollback');
    bind('.geo-forge-fix-cover', 'fixes/{id}/cover');
    bind('.geo-forge-fix-ignore', 'fixes/{id}/ignore');
})();
