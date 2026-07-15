# GEO Forge Plugin Audit Report

**Version:** 1.0.23-dev  
**Date:** 2026-07-15  
**Auditor:** Kilo Code Review  
**Scope:** Full codebase — PHP, JS, CSS, views, readme.txt, uninstall.php

---

## Executive Summary

The plugin has solid architecture (namespaced classes, PSR-4 autoloader, singleton pattern, interface-based fixer engine). However, the audit found **32 issues** across 4 severity levels. The most urgent are JavaScript crashes that break the Fix Center and Settings pages, XSS vulnerabilities in the dashboard, and WordPress.org rejection risks from external CDN imports and incorrect asset loading.

---

## 🔴 Critical Bugs (6)

### C-1: Fix Center JS crashes — `statusEl.querySelector('p')` returns null

| File | Line | Severity |
|------|------|----------|
| `assets/admin/js/fix-center.js` | 17 | Critical |

**Problem:** `showStatus()` calls `statusEl.querySelector('p')` but `#geo-forge-fix-status` is a bare `<div>` with no `<p>` child element. Every Apply/Verify/Undo action throws `TypeError: Cannot read properties of null (reading 'textContent')`.

**Impact:** Fix Center is completely non-functional. Users can click buttons but get no feedback and JS execution halts.

**Fix:** Add a `<p>` child to the status div in `page-fix-center.php:9`, or change the JS to use `statusEl.textContent` directly.

---

### C-2: llms.txt editor JS crashes — same `querySelector('p')` bug

| File | Line | Severity |
|------|------|----------|
| `assets/admin/js/llms-editor.js` | 21 | Critical |

**Problem:** Identical to C-1. `showStatus()` calls `statusEl.querySelector('p')` but `#geo-forge-editor-status` has no `<p>`.

**Fix:** Same as C-1.

---

### C-3: Settings page — security.txt & robots.txt buttons have no JS handlers

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-settings.php` | 107 | Critical |

**Problem:** The inline `<script>` block at line 107 only calls `ed()` for `llms-txt`. The save/regenerate buttons for `security.txt` and `robots.txt` (lines 71-83) have no JavaScript event listeners. They are dead UI — clicking them does nothing.

**Fix:** Add two more `ed()` calls:
```js
ed('geo-forge-save-security','geo-forge-regen-security','geo-forge-security-content','security-txt');
ed('geo-forge-save-robots','geo-forge-regen-robots','geo-forge-robots-content','robots-txt');
```

---

### C-4: Fix Center — status cell never updates after action

| File | Line | Severity |
|------|------|----------|
| `assets/admin/js/fix-center.js` | 41 | Critical |
| `admin/views/page-fix-center.php` | 21 | Critical |

**Problem:** `updateRow()` looks for `.geo-forge-fix-status-cell` but `page-fix-center.php` never assigns this CSS class to any `<td>`. After a successful Apply/Verify/Undo, the row's status column stays unchanged.

**Fix:** Add `class="geo-forge-fix-status-cell"` to the status `<td>` in `page-fix-center.php:21`.

---

### C-5: REST scan endpoint will timeout on most shared hosts

| File | Line | Severity |
|------|------|----------|
| `includes/Scanner/Scanner.php` | 46-116 | Critical |

**Problem:** `run_scan()` uses `sleep(2)` polling for up to 120 seconds. No `set_time_limit()` call. Most shared hosts enforce 30-60s PHP execution limits. On timeout, the user gets a 504 error with no feedback.

**Fix:** Add `set_time_limit(0)` at the start of `run_scan()`, or switch to an async model (initiate scan → poll from JS with a lightweight status endpoint).

---

### C-6: Settings page notice never displays

| File | Line | Severity |
|------|------|----------|
| `includes/Admin/Settings.php` | 74-88 | Critical |
| `admin/views/page-settings.php` | 28 | Critical |

**Problem:** `redirect_with_notice()` stores a transient and redirects with `?geo_forge_notice=1`. But `page-settings.php` never reads this transient or checks for the query param. The "Settings saved" / error messages are invisible.

**Fix:** Add code at the top of `page-settings.php` to check for `$_GET['geo_forge_notice']` and render the transient notice.

---

## 🟠 WordPress Standard Violations (11)

### W-1: External CDN dependency in CSS

| File | Line | Severity |
|------|------|----------|
| `assets/admin/css/admin.css` | 2 | High |

**Problem:** `@import url('https://cdn.jsdelivr.net/npm/lucide-static@0.344.0/font/lucide.min.css')` loads a font from an external CDN. WordPress.org plugin review **will reject** this. All assets must be self-hosted.

**Fix:** Download the font files into `assets/admin/fonts/` and reference them locally via `@font-face`.

---

### W-2: CSS loaded via `readfile()` instead of `wp_enqueue_style()`

| File | Line | Severity |
|------|------|----------|
| `includes/Admin/Admin.php` | 123-127 | High |

**Problem:** `inject_css()` hooks into `admin_head` and does `readfile()` on the CSS file. This bypasses WordPress's asset pipeline — no versioning, no minification, no dependency tracking, no cache headers, no `?ver=` query string for cache busting.

**Fix:** Use `wp_enqueue_style()` with the file version parameter. Only enqueue on plugin pages.

---

### W-3: CSS loads on EVERY admin page

| File | Line | Severity |
|------|------|----------|
| `includes/Admin/Admin.php` | 60 | High |

**Problem:** `admin_head` fires on every single admin page load. The CSS is injected site-wide, not just on plugin pages. This violates the AGENTS.md rule: "Frontend assets only load on plugin admin pages."

**Fix:** Move CSS injection inside the `str_contains($hook, 'geo-forge')` check in `enqueue_assets()`.

---

### W-4: Top-level menu contradicts documentation

| File | Line | Severity |
|------|------|----------|
| `includes/Admin/Admin.php` | 72 | Medium |

**Problem:** Uses `add_menu_page()` creating a top-level sidebar item. But `readme.txt:59` says "WooCommerce → GEO Forge → Settings". Should use `add_submenu_page('woocommerce', ...)` for consistency with the WooCommerce ecosystem.

**Fix:** Replace `add_menu_page()` with `add_submenu_page('woocommerce', ...)` and adjust the first submenu page registration.

---

### W-5: Menu capability mismatch

| File | Line | Severity |
|------|------|----------|
| `includes/Admin/Admin.php` | 76 | Medium |

**Problem:** Uses `manage_options` capability. WooCommerce uses `manage_woocommerce`. Shop managers (a common role) cannot access the plugin.

**Fix:** Use `manage_woocommerce` to align with WooCommerce's permission model.

---

### W-6: `readme.txt` stable tag doesn't match plugin version

| File | Line | Severity |
|------|------|----------|
| `readme.txt` | 7 | High |

**Problem:** `Stable tag: 1.0.0-dev` but plugin header says `Version: 1.0.23-dev`. WordPress.org requires exact match.

**Fix:** Update `readme.txt` stable tag to `1.0.23-dev`.

---

### W-7: Missing `languages/` directory

| File | Line | Severity |
|------|------|----------|
| `geo-forge.php` | 12 | Medium |

**Problem:** Plugin header declares `Domain Path: /languages` but the directory doesn't exist. `load_plugin_textdomain()` silently fails — no translations will ever load.

**Fix:** Create `languages/` directory and generate a `.pot` file.

---

### W-8: Global functions in view template

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 5-6 | Medium |

**Problem:** `gf_grade_label()` and `gf_grade_color()` are defined as global functions. If another plugin defines the same function names, PHP fatals.

**Fix:** Move to static methods on a helper class, or use closures.

---

### W-9: `Installer::activate()` calls `current_user_can()`

| File | Line | Severity |
|------|------|----------|
| `includes/Install/Installer.php` | 31 | Low |

**Problem:** Activation hooks are fired by WordPress core after its own capability check. Calling `current_user_can()` here is redundant and may fail in CLI/programmatic activation contexts (e.g., `wp plugin activate`).

**Fix:** Remove the `current_user_can()` check.

---

### W-10: Direct DB query without `$wpdb->prepare()` in view

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 12 | Medium |

**Problem:** Raw SQL query without prepared statement. While access-controlled, it violates WP coding standards and sets a bad pattern.

**Fix:** Use `$wpdb->prepare()` or move the query to a model class.

---

### W-11: `Installer::create_tables()` calls `migrate_settings_to_table()` twice

| File | Line | Severity |
|------|------|----------|
| `includes/Install/Installer.php` | 37, 146 | Low |

**Problem:** `activate()` calls `migrate_settings_to_table()` at line 37, then `create_tables()` also calls it at line 146. Redundant double migration on every activation.

**Fix:** Remove one of the two calls.

---

## 🟡 Security Issues (5)

### S-1: XSS in dashboard inline JavaScript

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 77 | High |

**Problem:** `gfViewDetail()` builds HTML via string concatenation from API response data (`x.label`, `x.goal`, `x.result`, `x.recommendation`) without escaping. If scan results contain `<script>` or `<img onerror=...>`, they execute in the admin context.

**Fix:** Use `document.createElement()` + `textContent`, or escape all values before concatenation.

---

### S-2: XSS in dashboard error display

| File | Line | Severity |
|------|------|----------|
| `assets/admin/js/dashboard.js` | 37 | Medium |

**Problem:** `errBox.innerHTML = '<p>' + msg + '</p>'` uses `innerHTML` with API response data. If the API returns HTML in the error message, it renders.

**Fix:** Use `textContent` instead of `innerHTML`.

---

### S-3: Unescaped output in dashboard view

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 22, 57 | Medium |

**Problem:** Line 22: `$ps` and `$fl` used directly in HTML without `esc_html()`. Line 57: `$t['total_score']` and `$t['created_at']` output without escaping.

**Fix:** Wrap all dynamic output with `esc_html()`.

---

### S-4: `TransientCache::flush_all()` leaves orphan timeout entries

| File | Line | Severity |
|------|------|----------|
| `includes/Cache/TransientCache.php` | 73-75 | Low |

**Problem:** Deletes `_transient_geo_forge_*` but not `_transient_timeout_geo_forge_*`. Orphan timeout rows accumulate in `wp_options` over time.

**Fix:** Also delete `_transient_timeout_geo_forge_%` entries.

---

### S-5: `Scanner::store_result()` uses `$wpdb->replace()` with potentially empty `scan_id`

| File | Line | Severity |
|------|------|----------|
| `includes/Scanner/Scanner.php` | 159 | Low |

**Problem:** If the API returns an empty `scan_id`, `replace()` matches on the empty unique key and overwrites the previous scan row.

**Fix:** Validate `scan_id` is non-empty before calling `replace()`.

---

## 🔵 UI/UX Issues (10)

### U-1: Dashboard view is unmaintainable — all HTML on 2 lines

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 1-78 | Medium |

**Problem:** ~75 lines of complex HTML crammed into 2 physical lines. Impossible to review, debug, or modify safely.

**Fix:** Reformat with proper indentation and line breaks.

---

### U-2: No confirmation for credit-consuming actions

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 62 | Medium |
| `admin/views/page-fix-center.php` | 24 | Medium |

**Problem:** "Scan Now" and "Apply" fixes consume GEO KAMI points but have no confirmation dialog. Users can accidentally burn credits.

**Fix:** Add `window.confirm()` before scan/apply actions.

---

### U-3: Traffic page "All-Time Total" is misleading

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-traffic.php` | 10, 18 | Medium |

**Problem:** The "All-Time Total" stat sums only the last 14 days of chart data but labels it as all-time.

**Fix:** Either query the actual all-time total from the DB, or rename the label to "Last 14 Days".

---

### U-4: No empty state guidance on dashboard

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 21-23 | Low |

**Problem:** When no scan has been run, the dashboard shows "—" without guiding the user to configure their API key and run their first scan.

**Fix:** Add a getting-started prompt when `$sc0 === null`.

---

### U-5: Inline `<script>` blocks in views

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 76-78 | Medium |
| `admin/views/page-settings.php` | 103-108 | Medium |

**Problem:** JS embedded directly in PHP templates. Bypasses CSP headers, can't be cached separately, violates WP best practices.

**Fix:** Move inline JS to external files in `assets/admin/js/`.

---

### U-6: No responsive table handling

| File | Line | Severity |
|------|------|----------|
| `assets/admin/css/admin.css` | 118 | Low |

**Problem:** Tables overflow on mobile. The grid collapses to single column at 768px but tables don't adapt.

**Fix:** Wrap tables in a container with `overflow-x: auto`.

---

### U-7: `page-llms-editor.php` is orphaned and uses stale CSS classes

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-llms-editor.php` | 1-27 | Low |

**Problem:** Uses `geo-forge-card`, `geo-forge-editor`, `geo-forge-muted` — none of which exist in the current CSS. Not reachable from any menu item. Dead code.

**Fix:** Remove the file or integrate it into the settings page.

---

### U-8: Promo banner animation runs continuously

| File | Line | Severity |
|------|------|----------|
| `assets/admin/css/admin.css` | 81-82 | Low |

**Problem:** `animation: gf-shift 15s ease infinite` runs constantly. Can trigger vestibular issues. No `prefers-reduced-motion` media query.

**Fix:** Add `@media (prefers-reduced-motion: reduce)` to disable animation.

---

### U-9: Log page filter form loses query params

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-logs.php` | 10 | Low |

**Problem:** Form `method="get"` only includes `page=geo-forge-logs`. Any other URL params are lost on submit.

**Fix:** Add hidden inputs for any additional query params, or use `add_query_arg()` to build the action URL.

---

### U-10: Dashboard "View Details" always shows the last scan

| File | Line | Severity |
|------|------|----------|
| `admin/views/page-dashboard.php` | 77 | Medium |

**Problem:** `gfViewDetail(id)` ignores the `id` parameter and always fetches `/scan/last`. Every history row shows identical data.

**Fix:** Either add a REST endpoint that accepts a scan ID, or store the full scan data in a `data-*` attribute on each row.

---

## Issue Summary

| Severity | Count | Category |
|----------|-------|----------|
| 🔴 Critical | 6 | JS crashes, dead UI, timeout risk, invisible notices |
| 🟠 WP Violations | 11 | CDN import, readfile CSS, wrong menu, missing i18n, capability mismatch |
| 🟡 Security | 5 | XSS vectors, unescaped output, orphan data |
| 🔵 UI/UX | 10 | Misleading labels, no confirmations, orphan views, maintainability |
| **Total** | **32** | |

---

## Recommended Fix Priority

### Phase 1 — Critical (blocks functionality)
1. **C-1, C-2** — Fix JS crashes in fix-center.js and llms-editor.js
2. **C-3** — Bind security.txt and robots.txt button handlers
3. **C-4** — Add missing CSS class to fix status cell
4. **C-6** — Display settings save notices
5. **C-5** — Add `set_time_limit()` to scan endpoint

### Phase 2 — WordPress.org compliance
6. **W-1** — Self-host the Lucide font
7. **W-2, W-3** — Move CSS to `wp_enqueue_style()`, only on plugin pages
8. **W-6** — Sync readme.txt stable tag with plugin version
9. **W-7** — Create `languages/` directory with `.pot` file

### Phase 3 — Security hardening
10. **S-1, S-2, S-3** — Fix all XSS vectors (escape output, use textContent)
11. **S-4** — Clean up orphan transient timeout entries

### Phase 4 — UX polish
12. **U-1** — Reformat dashboard view for maintainability
13. **U-2** — Add confirmation dialogs for credit-consuming actions
14. **U-5** — Extract inline JS to external files
15. **U-10** — Fix "View Details" to show correct scan data
