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
    // Priority:
    //   1. Embedded `window.GeoForgeScans` data (baked into the page by PHP).
    //      Works even if the REST endpoint isn't registered yet (opcache).
    //   2. REST GET /scan/{id} (new endpoint, v1.0.81+).
    //   3. REST GET /scan/last (legacy fallback, for very old scans not in embed).
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

        var scanId = btn.getAttribute('data-scan');
        var embedded = window.GeoForgeScans || {};
        var scan = scanId ? embedded[scanId] || embedded[Number(scanId)] : null;

        if (scan && scan.checks_result) {
            console.log('[GEO Forge Dashboard] Using embedded data for scanId=' + scanId);
            renderScanDetail(content, scan);
            return;
        }

        console.log('[GEO Forge Dashboard] No embedded data for scanId=' + scanId + ', falling back to REST');
        fetchScanDetail(scanId).then(function (s) {
            renderScanDetail(content, s);
        }).catch(function (err) {
            console.error('[GEO Forge Dashboard] Details fetch error:', err);
            content.innerHTML = '<p class="gf-muted">Failed to load: ' + (err.message || 'Unknown error') + '</p>';
        });
    });

    function fetchScanDetail(scanId) {
        var url = scanId ? restRoot + 'scan/' + scanId : restRoot + 'scan/last';
        console.log('[GEO Forge Dashboard] GET ' + url);
        return fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce }
        })
        .then(function (r) {
            console.log('[GEO Forge Dashboard] Details response status: ' + r.status);
            if (!r.ok) {
                return r.json().catch(function () { return null; }).then(function (body) {
                    var msg = (body && body.error && body.error.message) || 'HTTP ' + r.status;
                    throw new Error(msg);
                });
            }
            return r.json();
        })
        .then(function (b) {
            if (b && b.scan) return b.scan;
            var debugInfo = b ? JSON.stringify(b).substring(0, 200) : '(empty)';
            throw new Error('Scan not found in response: ' + debugInfo);
        });
    }

    function renderScanDetail(content, s) {
        var checks = s.checks_result || [];
        var rows = '';
        checks.forEach(function (x) {
            var ic = x.status === 'pass' ? '✅' : (x.status === 'warn' ? '⚠️' : '❌');
            rows += '<tr><td>' + ic + '</td><td style="font-size:12px;">' + (x.label||x.id||'?') + '</td><td style="font-size:11px;color:#64748b;">' + (x.category||'') + '</td><td style="font-size:12px;font-weight:600;">' + (x.score||0) + '/' + (x.maxScore||0) + '</td><td style="font-size:11px;color:#94a3b8;">' + (x.goal||'') + '</td></tr>';
        });
        content.innerHTML = '<h2>Scan Details</h2><p class="gf-muted">Score: <b>' + s.total_score + '</b> | ' + (s.created_at||'') + '</p><hr style="margin:12px 0"><table><thead><tr><th></th><th>Check</th><th>Category</th><th>Score</th><th>Result</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    // Close dialog on overlay click
    document.addEventListener('click', function (e) {
        if (e.target.id === 'gf-detail-dialog') {
            e.target.classList.remove('open');
        }
    });

    console.log('[GEO Forge Dashboard] event listeners registered');
})();
