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
            btn.disabled = true; statusEl.textContent = 'Scanning...'; statusEl.style.color = '#64748b';
            fetch(root + 'scan', { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                btn.disabled = false;
                if (body && body.success) {
                    statusEl.textContent = '✅ Done — refreshing...'; statusEl.style.color = '#16a34a';
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    var msg = (body && body.error && body.error.message) || 'Scan failed.';
                    statusEl.textContent = '❌ ' + msg; statusEl.style.color = '#dc2626';
                }
            })
            .catch(function () { btn.disabled = false; statusEl.textContent = '❌ Network error'; statusEl.style.color = '#dc2626'; });
        });
    }

    // View Details — event delegation
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.gf-view-detail');
        if (!btn) return;
        var dialog = document.getElementById('gf-detail-dialog');
        var content = document.getElementById('gf-detail-content');
        if (!dialog || !content) return;
        content.innerHTML = '<p class="gf-muted">Loading...</p>';
        dialog.classList.add('open');
        fetch(root + 'scan/last', { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
        .then(function (r) { return r.json(); })
        .then(function (b) {
            if (b && b.scan) {
                var s = b.scan, ch = s.checks_result || [];
                var h = '<h2>Scan Details</h2><p class="gf-muted">Score: <b>' + s.total_score + '</b> | ' + (s.created_at||'') + '</p><hr style="margin:12px 0">';
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
            } else { content.innerHTML = '<p class="gf-muted">Details not available.</p>'; }
        })
        .catch(function () { content.innerHTML = '<p class="gf-muted">Failed to load.</p>'; });
    });
    document.addEventListener('click', function (e) { if (e.target.id === 'gf-detail-dialog') e.target.classList.remove('open'); });

    // Account info fetch
    var userUrl = root + 'account';
    fetch(userUrl, { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
    .then(function (r) { return r.json(); })
    .then(function (body) {
        var el = document.getElementById('gf-account-info');
        if (!el || !body || !body.success) return;
        var d = body.data || {};
        var html =
            '<span class="gf-badge gf-badge-green" style="font-size:11px;">🔗 ' + (d.plan||'Free') + '</span>' +
            '<span style="font-size:11px;color:#64748b;">' + (d.points!==undefined?d.points:'—') + ' pts</span>' +
            (d.expires ? '<span style="font-size:11px;color:#94a3b8;">Expires ' + d.expires + '</span>' : '');
        el.innerHTML = html;
    })
    .catch(function () {});
})();
