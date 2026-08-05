/**
 * GEO Forge — Dashboard JS
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeDashboard || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    // Scan button — async: POST /scan returns immediately, then we poll
    // GET /scan/status every few seconds until the scan completes.
    var scanPollTimer = null;
    var scanPollCount = 0;
    var SCAN_POLL_INTERVAL = 4000;
    var SCAN_POLL_MAX = 40; // ~160s ceiling

    function stopScanPolling() {
        if (scanPollTimer) { clearInterval(scanPollTimer); scanPollTimer = null; }
        scanPollCount = 0;
    }

    function scanDone(btn, statusEl) {
        btn.disabled = false;
        if (statusEl) { statusEl.textContent = '✅ Done — refreshing...'; statusEl.style.color = '#16a34a'; }
        setTimeout(function () { location.reload(); }, 800);
    }

    function scanFailed(btn, statusEl, msg) {
        btn.disabled = false;
        if (statusEl) { statusEl.textContent = '❌ ' + (msg || 'Scan failed.'); statusEl.style.color = '#dc2626'; }
    }

    function pollScanStatus(btn, statusEl) {
        scanPollCount++;
        if (scanPollCount > SCAN_POLL_MAX) {
            stopScanPolling();
            btn.disabled = false;
            if (statusEl) { statusEl.textContent = '⚠️ Scan still running — check back shortly.'; statusEl.style.color = '#d97706'; }
            return;
        }
        if (statusEl) { statusEl.textContent = 'Scanning… (' + scanPollCount + ')'; }

        fetch(restRoot + 'scan/status', {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce }
        })
        .then(function (r) { return r.json(); })
        .then(function (body) {
            if (!body || !body.success) { throw new Error('Bad status response'); }
            if (body.status === 'completed') {
                stopScanPolling();
                scanDone(btn, statusEl);
            } else if (body.status === 'failed' || body.status === 'error') {
                stopScanPolling();
                scanFailed(btn, statusEl, body.message);
            }
        })
        .catch(function () {
            stopScanPolling();
            btn.disabled = false;
            if (statusEl) { statusEl.textContent = '❌ Network error'; statusEl.style.color = '#dc2626'; }
        });
    }

    document.querySelectorAll('#geo-forge-scan-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            stopScanPolling();
            btn.disabled = true;
            var statusEl = document.getElementById('geo-forge-scan-status');
            if (statusEl) { statusEl.textContent = 'Starting scan…'; statusEl.style.color = '#64748b'; }
            fetch(restRoot + 'scan', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                if (!body || !body.success) {
                    var msg = (body && body.error && body.error.message) || 'Scan failed.';
                    scanFailed(btn, statusEl, msg);
                    return;
                }
                if (body.status === 'completed') {
                    scanDone(btn, statusEl);
                    return;
                }
                scanPollCount = 0;
                scanPollTimer = setInterval(function () { pollScanStatus(btn, statusEl); }, SCAN_POLL_INTERVAL);
            })
            .catch(function () {
                scanFailed(btn, statusEl, 'Network error');
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
        var dialog = document.getElementById('gf-detail-dialog');
        var content = document.getElementById('gf-detail-content');
        if (!dialog || !content) {
            return;
        }
        content.innerHTML = '<p class="gf-muted">Loading...</p>';
        dialog.classList.add('open');

        var scanId = btn.getAttribute('data-scan');
        var embedded = window.GeoForgeScans || {};
        var scan = scanId ? embedded[scanId] || embedded[Number(scanId)] : null;

        if (scan && scan.checks_result) {
            renderScanDetail(content, scan);
            return;
        }

        fetchScanDetail(scanId).then(function (s) {
            renderScanDetail(content, s);
        }).catch(function (err) {
            content.innerHTML = '<p class="gf-muted">Failed to load: ' + (err.message || 'Unknown error') + '</p>' +
                '<p style="font-size:11px;color:#94a3b8;margin-top:8px;">scanId=' + scanId +
                ' | restRoot=' + restRoot + '</p>';
        });
    });

    function fetchScanDetail(scanId) {
        var url = scanId ? restRoot + 'scan/' + scanId : restRoot + 'scan/last';
        return fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce }
        })
        .then(function (r) {
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

})();
