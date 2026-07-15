/**
 * GEO Forge — Dashboard JS.
 *
 * Handles the "Scan Now" button: POSTs to the REST endpoint, polls for
 * the result (scan typically takes 10-15s on GEO KAMI's side), and
 * updates the stat tiles and category table in place.
 *
 * Reads config from window.GeoForgeDashboard (localized by Admin.php).
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeDashboard || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    var i18n = cfg.i18n || {};

    var btn = document.getElementById('geo-forge-scan-btn');
    var statusEl = document.getElementById('geo-forge-scan-status');
    var errorBox = document.getElementById('geo-forge-error');

    if (!btn) {
        return;
    }

    function setBusy(isBusy) {
        btn.disabled = isBusy;
        statusEl.textContent = isBusy ? (i18n.scanning || 'Scanning…') : '';
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.querySelector('p').textContent = message;
        errorBox.style.display = 'block';
    }

    function clearError() {
        if (errorBox) errorBox.style.display = 'none';
    }

    function updateStat(selector, value) {
        var el = document.querySelector('[data-stat="' + selector + '"]');
        if (el) el.textContent = value;
    }

    function emojiForScore(score) {
        if (score >= 80) return '🟢';
        if (score >= 50) return '🟡';
        if (score >= 25) return '🟠';
        return '🔴';
    }

    function renderScan(scan) {
        if (!scan) return;

        var score = scan.total_score || 0;
        updateStat('score', emojiForScore(score) + ' ' + score);
        updateStat('grade', scan.grade_label || '—');

        var checks = scan.checks_result || [];
        var issues = checks.filter(function (c) { return c.status !== 'pass'; }).length;
        updateStat('issues', String(issues));

        // Rebuild category table.
        var wrap = document.querySelector('.geo-forge-category-table');
        if (!wrap) return;
        var tbody = wrap.querySelector('tbody');
        if (!tbody) return;

        var html = '';
        var cats = scan.category_scores || [];
        cats.forEach(function (cat) {
            var earned = cat.earned || 0;
            var max = Math.max(1, cat.max || 1);
            var pct = Math.round((earned / max) * 100);
            var color = pct >= 80 ? '#00a32a' : (pct >= 50 ? '#dba600' : '#d63638');
            var id = (cat.id || '').toString();
            id = id.charAt(0).toUpperCase() + id.slice(1);

            html += '<tr>' +
                '<td style="width:40%;">' + escapeHtml(id) + '</td>' +
                '<td><div class="geo-forge-bar"><div class="geo-forge-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div></td>' +
                '<td style="width:15%;text-align:right;">' + pct + '% <span class="geo-forge-muted">(' + earned + '/' + cat.max + ')</span></td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function restFetch(path, opts) {
        opts = opts || {};
        return fetch(restRoot + path, {
            method: opts.method || 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            }
        }).then(function (r) {
            return r.json().then(function (body) {
                return { ok: r.ok, status: r.status, body: body };
            });
        });
    }

    btn.addEventListener('click', function () {
        clearError();
        setBusy(true);

        restFetch('scan', { method: 'POST' })
            .then(function (res) {
                if (!res.ok || !res.body.success) {
                    var err = (res.body && res.body.error) || {};
                    throw new Error(err.message || (i18n.scanFailed || 'Scan failed.'));
                }
                return res.body.scan;
            })
            .then(function (scan) {
                renderScan(scan);
                setBusy(false);
            })
            .catch(function (err) {
                setBusy(false);
                showError(err.message || (i18n.unknownError || 'Unknown error.'));
            });
    });
})();
