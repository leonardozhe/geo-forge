/**
 * GEO Forge — Dashboard JS
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeDashboard || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

    console.log('[GEO Forge Dashboard] script loaded. restRoot=' + restRoot);

    // Scan button
    document.querySelectorAll('#geo-forge-scan-btn').forEach(function (btn) {
        console.log('[GEO Forge Dashboard] Scan button bound');
        btn.addEventListener('click', function () {
            console.log('[GEO Forge Dashboard] Scan clicked');
            btn.disabled = true;
            var statusEl = document.getElementById('geo-forge-scan-status');
            if (statusEl) { statusEl.textContent = 'Scanning...'; statusEl.style.color = '#64748b'; }
            fetch(restRoot + 'scan', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                btn.disabled = false;
                if (body && body.success) {
                    if (statusEl) { statusEl.textContent = '✅ Done — refreshing...'; statusEl.style.color = '#16a34a'; }
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    var msg = (body && body.error && body.error.message) || 'Scan failed.';
                    if (statusEl) { statusEl.textContent = '❌ ' + msg; statusEl.style.color = '#dc2626'; }
                }
            })
            .catch(function (err) {
                console.error('[GEO Forge Dashboard] Scan fetch error:', err);
                btn.disabled = false;
                if (statusEl) { statusEl.textContent = '❌ Network error'; statusEl.style.color = '#dc2626'; }
            });
        });
    });

    // View Details — event delegation on document
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.gf-view-detail');
        if (!btn) return;
        console.log('[GEO Forge Dashboard] View Details clicked');
        var dialog = document.getElementById('gf-detail-dialog');
        var content = document.getElementById('gf-detail-content');
        if (!dialog || !content) {
            console.warn('[GEO Forge Dashboard] #gf-detail-dialog or #gf-detail-content not found');
            return;
        }
        content.innerHTML = '<p class="gf-muted">Loading...</p>';
        dialog.classList.add('open');
        // Use the specific scan ID from the button's data-scan attribute.
        // Fall back to /scan/last only if data-scan is missing.
        var scanId = btn.getAttribute('data-scan');
        var url = scanId ? restRoot + 'scan/' + scanId : restRoot + 'scan/last';
        console.log('[GEO Forge Dashboard] GET ' + url + ' (scanId=' + scanId + ')');
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce }
        })
        .then(function (r) {
            console.log('[GEO Forge Dashboard] Details response status: ' + r.status);
            return r.json();
        })
        .then(function (b) {
            console.log('[GEO Forge Dashboard] Details response body:', b);
            if (b && b.scan) {
                var s = b.scan, checks = s.checks_result || [], rows = '';
                checks.forEach(function (x) {
                    var ic = x.status === 'pass' ? '✅' : (x.status === 'warn' ? '⚠️' : '❌');
                    rows += '<tr><td>' + ic + '</td><td style="font-size:12px;">' + (x.label||x.id||'?') + '</td><td style="font-size:11px;color:#64748b;">' + (x.category||'') + '</td><td style="font-size:12px;font-weight:600;">' + (x.score||0) + '/' + (x.maxScore||0) + '</td><td style="font-size:11px;color:#94a3b8;">' + (x.goal||'') + '</td></tr>';
                });
                content.innerHTML = '<h2>Scan Details</h2><p class="gf-muted">Score: <b>' + s.total_score + '</b> | ' + (s.created_at||'') + '</p><hr style="margin:12px 0"><table><thead><tr><th></th><th>Check</th><th>Category</th><th>Score</th><th>Result</th></tr></thead><tbody>' + rows + '</tbody></table>';
            } else { content.innerHTML = '<p class="gf-muted">Details not available.</p>'; }
        })
        .catch(function (err) {
            console.error('[GEO Forge Dashboard] Details fetch error:', err);
            content.innerHTML = '<p class="gf-muted">Failed to load.</p>';
        });
    });

    // Close dialog on overlay click
    document.addEventListener('click', function (e) {
        if (e.target.id === 'gf-detail-dialog') {
            e.target.classList.remove('open');
        }
    });

    console.log('[GEO Forge Dashboard] event listeners registered');
})();
