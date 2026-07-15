/**
 * GEO Forge — Dashboard JS
 * Scan + View Details
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeDashboard || {};
    var root = cfg.restRoot || '', nonce = cfg.restNonce || '';

    // Scan button
    var btn = document.getElementById('geo-forge-scan-btn');
    var statusEl = document.getElementById('geo-forge-scan-status');
    if (btn) {
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
    }

    // View Details — delegate from document
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.gf-view-detail');
        if (!btn) return;
        var scanId = btn.getAttribute('data-scan') || '';
        var dialog = document.getElementById('gf-detail-dialog');
        var content = document.getElementById('gf-detail-content');
        if (!dialog || !content) return;
        content.innerHTML = '<p class="gf-muted">Loading...</p>';
        dialog.classList.add('open');
        fetch(root + 'scan/last', {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': nonce }
        })
        .then(function (r) { return r.json(); })
        .then(function (b) {
            if (b && b.scan) {
                var s = b.scan, ch = s.checks_result || [];
                var h = '<h2>Scan Details</h2><p class="gf-muted">Score: <b>' + s.total_score + '</b> | Grade: <b>' + (s.grade_label||'') + '</b> | ' + (s.created_at||'') + '</p><hr style="margin:12px 0">';
                h += '<h3>Checks (' + ch.length + ')</h3>';
                ch.forEach(function (x) {
                    var st = x.status || 'fail', ic = st === 'pass' ? '✅' : (st === 'warn' ? '⚠️' : '❌');
                    h += '<div class="gf-check-item"><span>' + ic + '</span><div style="flex:1"><div style="font-weight:600;">' + x.label + '</div>';
                    if (x.goal) h += '<div class="gf-check-meta">Goal: ' + x.goal + '</div>';
                    if (x.result && st !== 'pass') h += '<div class="gf-check-meta">Result: ' + x.result + '</div>';
                    if (x.recommendation) h += '<div class="gf-check-recommendation">💡 ' + x.recommendation + (x.effort ? ' (≈' + x.effort + ')' : '') + '</div>';
                    h += '</div><span style="font-size:11px;color:#94a3b8">' + (x.score||0) + '/' + (x.maxScore||0) + '</span></div>';
                });
                content.innerHTML = h;
            } else {
                content.innerHTML = '<p class="gf-muted">Details not available.</p>';
            }
        })
        .catch(function () { content.innerHTML = '<p class="gf-muted">Failed to load.</p>'; });
    });

    // Close dialog
    document.addEventListener('click', function (e) {
        if (e.target.id === 'gf-detail-dialog') {
            e.target.classList.remove('open');
        }
    });
})();
