/**
 * GEO Forge — Content Editor JS
 * llms.txt, security.txt, robots.txt save + regenerate
 */
(function () {
    'use strict';
    var cfg = window.GeoForgeSettings || {};
    var root = cfg.restRoot || '', nonce = cfg.restNonce || '';

    function bindEditor(saveId, regenId, textareaId, endpoint) {
        var saveBtn = document.getElementById(saveId);
        var regenBtn = document.getElementById(regenId);
        var ta = document.getElementById(textareaId);
        if (!saveBtn || !ta) return;

        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true;
            fetch(root + 'well-known/' + endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: ta.value })
            })
            .then(function (r) { return r.json(); })
            .then(function (b) {
                saveBtn.disabled = false;
                if (b && b.success) {
                    showToast('✅ Saved (' + (b.bytes || ta.value.length) + ' bytes)');
                } else {
                    showToast('❌ ' + ((b && b.error && b.error.message) || 'Save failed'));
                }
            })
            .catch(function () { saveBtn.disabled = false; showToast('❌ Network error'); });
        });

        if (regenBtn) {
            regenBtn.addEventListener('click', function () {
                regenBtn.disabled = true;
                fetch(root + 'well-known/' + endpoint + '/regenerate', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (b) {
                    regenBtn.disabled = false;
                    if (b && b.success && b.content) {
                        ta.value = b.content;
                        showToast('✅ Regenerated (' + b.bytes + ' bytes)');
                    } else {
                        showToast('❌ Regeneration failed');
                    }
                })
                .catch(function () { regenBtn.disabled = false; showToast('❌ Network error'); });
            });
        }
    }

    function showToast(msg) {
        var el = document.getElementById('geo-forge-editor-status');
        if (!el) return;
        el.innerHTML = '<p>' + msg + '</p>';
        el.className = 'gf-notice ' + (msg.indexOf('✅') >= 0 ? 'gf-notice-success' : 'gf-notice-error');
        el.style.display = 'block';
        setTimeout(function () { el.style.display = 'none'; }, 4000);
    }

    bindEditor('geo-forge-save-llms',    'geo-forge-regen-llms',    'geo-forge-llms-content',    'llms-txt');
    bindEditor('geo-forge-save-security', 'geo-forge-regen-security', 'geo-forge-security-content', 'security-txt');
    bindEditor('geo-forge-save-robots',   'geo-forge-regen-robots',   'geo-forge-robots-content',   'robots-txt');
})();
