/**
 * GEO Forge — Logs page JS.
 *
 * Handles two buttons:
 *   - "Clear Logs" — DELETE all rows (TRUNCATE).
 *   - "Rebuild Table" — DROP + recreate the table AND reset min_level option.
 *
 * Both POST to the REST API and reload the page on success.
 */
(function () {
    'use strict';

    var cfg = window.GeoForgeLogs || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';
    var i18n = cfg.i18n || {};

    var statusEl = document.getElementById('geo-forge-clear-status');

    function postLogsAction(endpoint, confirmMessage, successMessage) {
        if (!window.confirm(confirmMessage)) {
            return;
        }

        if (statusEl) {
            statusEl.textContent = '';
            statusEl.style.color = '';
        }

        // Disable all log action buttons during the request.
        var allBtns = document.querySelectorAll('#geo-forge-clear-logs, #geo-forge-reset-logs');
        allBtns.forEach(function (b) { b.disabled = true; });

        fetch(restRoot + endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (body) {
                if (body && body.success) {
                    var msg = (body && body.message) || successMessage;
                    if (statusEl) {
                        statusEl.textContent = '✅ ' + msg;
                        statusEl.style.color = '#00a32a';
                    }
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    var msg = (body && body.error && body.error.message) || 'Failed.';
                    if (statusEl) {
                        statusEl.textContent = '❌ ' + msg;
                        statusEl.style.color = '#d63638';
                    }
                    allBtns.forEach(function (b) { b.disabled = false; });
                }
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.textContent = '❌ ' + (i18n.unknownError || 'Unknown error.');
                    statusEl.style.color = '#d63638';
                }
                allBtns.forEach(function (b) { b.disabled = false; });
            });
    }

    // Clear Logs button — deletes all rows, keeps table structure.
    var clearBtn = document.getElementById('geo-forge-clear-logs');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            postLogsAction(
                'logs/clear',
                i18n.confirmClear || 'Clear all log entries? This cannot be undone.',
                i18n.cleared || 'Logs cleared.'
            );
        });
    }

    // Rebuild Table button — drops + recreates the table AND resets min_level option.
    // Use this when the Logs page shows nothing despite recent plugin activity
    // (typically caused by a stale 'warning' min_level option from a previous version).
    var resetBtn = document.getElementById('geo-forge-reset-logs');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            postLogsAction(
                'logs/reset',
                'Rebuild the logs table? This will:\n• Delete ALL log entries\n• Reset the minimum log level to default (info)\n• Recreate the table structure\n\nUse this if logs are not appearing despite plugin activity.',
                'Logs table rebuilt. Min level reset to default.'
            );
        });
    }
})();
