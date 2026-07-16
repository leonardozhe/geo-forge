/**
 * GEO Forge — Settings JS
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeSettings || {};
    var restRoot = cfg.restRoot || '';
    var restNonce = cfg.restNonce || '';

    // Map button-ID suffix → REST endpoint segment.
    // Button IDs: geo-forge-save-llms    → POST /well-known/llms-txt
    // Button IDs: geo-forge-save-security → POST /well-known/security-txt
    // Button IDs: geo-forge-save-robots   → POST /well-known/robots-txt
    var ENDPOINT_MAP = { llms: 'llms-txt', security: 'security-txt', robots: 'robots-txt' };

    // Track original textarea values so Save button can stay disabled until the user edits.
    var originalValues = {};

    console.log('[GEO Forge Settings] script loaded. restRoot=' + restRoot);

    function showToast(message, isError) {
        var el = document.getElementById('geo-forge-editor-status');
        if (!el) {
            console.warn('[GEO Forge Settings] #geo-forge-editor-status not found in DOM');
            // Fallback: alert so the user at least sees something
            alert(message);
            return;
        }
        el.innerHTML = '<p>' + message + '</p>';
        el.className = 'gf-notice ' + (isError ? 'gf-notice-error' : 'gf-notice-success');
        el.style.display = 'block';
        setTimeout(function () { el.style.display = 'none'; }, 4000);
    }

    if (!restRoot || !restNonce) {
        console.error('[GEO Forge Settings] Config missing! restRoot=' + restRoot + ', restNonce=' + (restNonce ? 'set' : 'empty'));
        return;
    }

    // Health Check button
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#geo-forge-health-btn');
        if (!btn) return;
        e.preventDefault();
        console.log('[GEO Forge Settings] Health Check clicked');
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '…';
        var statusEl = document.getElementById('geo-forge-health-status');
        if (statusEl) { statusEl.textContent = '…'; statusEl.style.color = ''; }
        fetch(restRoot + 'health-check', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (body) {
            btn.disabled = false;
            btn.textContent = originalText;
            if (body && body.ok) {
                if (statusEl) { statusEl.textContent = '✅ Connected'; statusEl.style.color = '#00a32a'; }
            } else {
                var msg = (body && body.error && body.error.message) || 'Connection failed';
                if (statusEl) { statusEl.textContent = '❌ ' + msg; statusEl.style.color = '#dc2626'; }
            }
        })
        .catch(function (err) {
            console.error('[GEO Forge Settings] Health Check fetch error:', err);
            btn.disabled = false;
            btn.textContent = originalText;
            if (statusEl) { statusEl.textContent = '❌ Network error'; statusEl.style.color = '#dc2626'; }
        });
    });

    // Save buttons — map suffix to correct REST endpoint
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[id^="geo-forge-save-"]');
        if (!btn) return;
        e.preventDefault();
        var id = btn.id.replace('geo-forge-save-', '');       // 'llms', 'security', 'robots'
        var endpoint = ENDPOINT_MAP[id] || id;                 // 'llms-txt', 'security-txt', 'robots-txt'
        var ta = document.getElementById('geo-forge-' + id + '-content');
        if (!ta) {
            console.error('[GEO Forge Settings] textarea not found: geo-forge-' + id + '-content');
            showToast('❌ Editor not found', true);
            return;
        }
        console.log('[GEO Forge Settings] Save clicked for: ' + id + ' → endpoint: ' + endpoint);
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '…';
        var url = restRoot + 'well-known/' + endpoint;
        console.log('[GEO Forge Settings] POST ' + url);
        fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: ta.value })
        })
        .then(function (r) {
            console.log('[GEO Forge Settings] Save response status: ' + r.status);
            return r.json();
        })
        .then(function (body) {
            console.log('[GEO Forge Settings] Save response body:', body);
            btn.disabled = false;
            btn.textContent = originalText;
            if (body && body.success) {
                // Content is now persisted — treat current textarea value as the new baseline
                originalValues[id] = ta.value;
                updateSaveBtnState(id);
                showToast('✅ Saved');
            } else {
                showToast('❌ ' + ((body && body.error && body.error.message) || 'Save failed'), true);
            }
        })
        .catch(function (err) {
            console.error('[GEO Forge Settings] Save fetch error:', err);
            btn.disabled = false;
            btn.textContent = originalText;
            showToast('❌ Network error', true);
        });
    });

    // Regenerate buttons — same mapping
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[id^="geo-forge-regen-"]');
        if (!btn) return;
        e.preventDefault();
        var id = btn.id.replace('geo-forge-regen-', '');
        var endpoint = ENDPOINT_MAP[id] || id;
        var ta = document.getElementById('geo-forge-' + id + '-content');
        if (!ta) {
            console.error('[GEO Forge Settings] textarea not found: geo-forge-' + id + '-content');
            showToast('❌ Editor not found', true);
            return;
        }
        console.log('[GEO Forge Settings] Regenerate clicked for: ' + id);
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '…';
        var url = restRoot + 'well-known/' + endpoint + '/regenerate';
        console.log('[GEO Forge Settings] POST ' + url);
        fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' }
        })
        .then(function (r) {
            console.log('[GEO Forge Settings] Regenerate response status: ' + r.status);
            return r.json();
        })
        .then(function (body) {
            console.log('[GEO Forge Settings] Regenerate response body:', body);
            btn.disabled = false;
            btn.textContent = originalText;
            if (body && body.success && body.content) {
                ta.value = body.content;
                // Trigger input event so the Save button's disabled state updates
                ta.dispatchEvent(new Event('input', { bubbles: true }));
                showToast('✅ Regenerated');
            } else {
                showToast('❌ Regenerate failed', true);
            }
        })
        .catch(function (err) {
            console.error('[GEO Forge Settings] Regenerate fetch error:', err);
            btn.disabled = false;
            btn.textContent = originalText;
            showToast('❌ Network error', true);
        });
    });

    console.log('[GEO Forge Settings] event listeners registered');

    // Save-button state management: disabled until textarea differs from its initial value.
    function updateSaveBtnState(suffix) {
        var saveBtn = document.getElementById('geo-forge-save-' + suffix);
        var ta = document.getElementById('geo-forge-' + suffix + '-content');
        if (!saveBtn || !ta) {
            console.warn('[GEO Forge Settings] updateSaveBtnState: missing element for ' + suffix +
                ' (saveBtn=' + !!saveBtn + ', ta=' + !!ta + ')');
            return;
        }
        var baseline = originalValues[suffix] || '';
        var isDirty = ta.value !== baseline;
        saveBtn.disabled = !isDirty;
        console.log('[GEO Forge Settings] save-' + suffix + ' ' + (isDirty ? 'ENABLED' : 'DISABLED') +
            ' (dirty=' + isDirty + ', len=' + ta.value.length + ' vs baseline=' + baseline.length + ')');
    }

    // Initialize: store baseline values and disable all Save buttons.
    console.log('[GEO Forge Settings] Initializing Save-button state tracking...');
    var suffixes = ['llms', 'security', 'robots'];
    suffixes.forEach(function (suffix) {
        var ta = document.getElementById('geo-forge-' + suffix + '-content');
        var saveBtn = document.getElementById('geo-forge-save-' + suffix);
        if (!ta) {
            console.warn('[GEO Forge Settings] textarea not found: geo-forge-' + suffix + '-content');
            return;
        }
        if (!saveBtn) {
            console.warn('[GEO Forge Settings] save button not found: geo-forge-save-' + suffix);
            return;
        }
        originalValues[suffix] = ta.value;
        saveBtn.disabled = true;
        // Re-check on every keystroke
        ta.addEventListener('input', function () { updateSaveBtnState(suffix); });
        console.log('[GEO Forge Settings] initialized ' + suffix + ': disabled Save button, stored baseline length=' + ta.value.length);
    });
    console.log('[GEO Forge Settings] Save-button state tracking initialized for ' + suffixes.length + ' sections');
})();
