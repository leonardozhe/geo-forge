/**
 * GEO Forge — Dashboard JS
 * Scan + View Details + Account info
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

    // View Details — event delegation on document body
    document.body.addEventListener('click', function (e) {
        var target = e.target;
        if (!target.classList.contains('gf-view-detail')) return;
        var scanId = target.getAttribute('data-scan');
        var dialog = document.getElementById('gf-detail-dialog');
        var content = document.getElementById('gf-detail-content');
        if (!dialog || !content) return;
        content.innerHTML = '<p class="gf-muted">Loading...</p>';
        dialog.classList.add('open');
        fetch(root + 'scan/last', { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
        .then(function (r) { return r.json(); })
        .then(function (b) {
            if (b && b.scan) {
                var s = b.scan, checks = s.checks_result || [];
                var rows = '';
                checks.forEach(function (x) {
                    var ic = x.status === 'pass' ? '✅' : (x.status === 'warn' ? '⚠️' : '❌');
                    rows += '<tr><td>' + ic + '</td><td style="font-size:12px;">' + (x.label||x.id||'?') + '</td><td style="font-size:11px;color:#64748b;">' + (x.category||'') + '</td><td style="font-size:12px;font-weight:600;">' + (x.score||0) + '/' + (x.maxScore||0) + '</td><td style="font-size:11px;color:#94a3b8;">' + (x.goal||'') + '</td></tr>';
                });
                content.innerHTML = '<h2>Scan Details</h2><p class="gf-muted">Score: <b>' + s.total_score + '</b> | ' + (s.created_at||'') + '</p><hr style="margin:12px 0"><table><thead><tr><th></th><th>Check</th><th>Category</th><th>Score</th><th>Result</th></tr></thead><tbody>' + rows + '</tbody></table>';
            } else { content.innerHTML = '<p class="gf-muted">Details not available.</p>'; }
        })
        .catch(function () { content.innerHTML = '<p class="gf-muted">Failed to load.</p>'; });
    });
    document.addEventListener('click', function (e) { if (e.target.id === 'gf-detail-dialog') e.target.classList.remove('open'); });

    // Account info fetch
    var userUrl = root + 'account';
    var accEl = document.getElementById('gf-account-info');
    if (accEl) {
        fetch(userUrl, { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
        .then(function (r) { return r.json(); })
        .then(function (body) {
            if (!body || !body.success || !body.data) return;
            var d = body.data, plan = d.plan || {}, points = d.points || {}, sub = d.subscription || {};
            accEl.innerHTML =
                '<span class="gf-badge" style="background:#4338ca;color:#fff;">' + (plan.label||plan.tier||'Free') + '</span>' +
                '<span style="font-size:12px;font-weight:600;color:#1e293b;">' + (points.balance!==undefined?points.balance:'—') + '</span>' +
                '<span style="font-size:11px;color:#94a3b8;">' + (sub.currentPeriodEnd?'Exp. '+sub.currentPeriodEnd.substring(0,10):'') + '</span>';
        })
        .catch(function () {});
    }
})();
