# AGENTS.md

This document defines the collaboration protocol for all AI agents working on the GEO Forge plugin. **Violating these rules causes plugin breakage and WordPress.org rejection.** Read before writing any code.

## 🔴 Critical: Multi-Agent Collaboration Protocol

### Before You Write Any Code

1. **Always start with `git pull origin main`**. Other agents may have pushed changes since your last session. Never assume your local copy is current.
2. **Read the files you plan to edit**. Do not rely on memory of the codebase state. Files may have changed.
3. **Check `git status`** before beginning work. Note any untracked or modified files left by other agents.

### Before You Commit

1. **Run `php -l` on every changed PHP file** and ensure no syntax errors.
2. **Run `git diff --stat`** and review every file you changed. If you changed files unrelated to your task, revert them.
3. **Pull again** before committing: `git pull origin main`. Handle merge conflicts explicitly — do not force-push or blindly accept yours/theirs.

### ⛔ Never Do This

- **⛔ Never commit `.env` or API keys** — WordPress plugins are public. GEO KAMI API keys belong in the WordPress admin settings UI, never in code.
- **⛔ Never force-push** `git push --force` — this destroys other agents' work.
- **⛔ Never use WordPress functions outside of WordPress context** — no `get_option()` in standalone scripts.
- **⛔ Never directly modify `.htaccess` or `nginx.conf`** — use WordPress Rewrite API for virtual routing. Must work on Apache, Nginx, and LiteSpeed.
- **⛔ Never delete files you didn't create** — other agents may depend on them.
- **⛔ Never bundle external libraries without Composer** — add to `composer.json`, don't copy-paste vendor code.

---

## Project: GEO Forge

A WooCommerce plugin that transforms stores from "AI-blind" to "Agent-Ready" by connecting to the GEO KAMI Cloud API for scanning, then auto-deploying fixes for llms.txt, security.txt, MCP/A2A endpoints, structured data, markdown negotiation, and more.

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 7.4+ (target 8.1+) |
| Platform | WordPress 6.0+ / WooCommerce 8.0+ |
| API Client | `wp_remote_get` / `wp_remote_post` |
| Caching | WordPress Transients API |
| Database | WordPress `$wpdb` + custom tables |
| Cron | WP Cron (`wp_schedule_event`) |
| Routing | WordPress Rewrite API + `template_redirect` |
| REST API | `register_rest_route` |
| JavaScript | Vanilla JS + Chart.js (admin only) |
| Dependencies | Zero external PHP dependencies for core |

### Architecture Rules

1. **All code is namespaced** — `GEO_Forge\*` for PHP, `geoForge` for JS globals.
2. **Plugin directory is the root** — includes live inside `includes/`, admin views in `admin/views/`, templates in `templates/`.
3. **One class per file** — filename matches class name: `class-api-client.php` = `GEO_Forge_API_Client`.
4. **Frontend assets only load on plugin admin pages** — use `wp_enqueue_scripts` with conditional checks. Never load CSS/JS site-wide.
5. **API keys are encrypted at rest** — use `wp_hash()` or WordPress salts, never plaintext in the database.
6. **Every fix is reversible** — snapshot the state before any automated change.

### WordPress Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) for PHP.
- Use `snake_case` for WordPress hooks and filters.
- Use `camelCase` for JavaScript.
- All user-facing strings use `__()`, `_e()`, `esc_html__()` with text domain `geo-forge`.
- All output is escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
- All input is sanitized: `sanitize_text_field()`, `sanitize_email()`, `wp_kses()`.
- All DB queries use `$wpdb->prepare()` for parameterized queries.
- All AJAX/REST endpoints check `current_user_can('manage_woocommerce')`.

### File Ownership

| Path | Owner | Rules |
|------|-------|-------|
| `geo-forge.php` | Core agent | Bootstrap only. No business logic in this file. |
| `includes/class-api-client.php` | API agent | All GEO KAMI HTTP calls go through here. |
| `includes/class-fixer.php` | Fixer agent | Every fix action must implement the fix interface. |
| `includes/class-well-known.php` | Routing agent | All `/.well-known/*` routes. Must call `flush_rewrite_rules()` sparingly. |
| `admin/views/` | UI agent | Pure view templates. No SQL queries in views. |
| `templates/` | Templates agent | Default content templates for llms.txt, security.txt, etc. |
| `languages/` | i18n agent | Valid `.po`/`.mo` files. Run `msgfmt` to compile. |
| `readme.txt` | Docs agent | WordPress.org readme. Must pass Plugin Directory validator. |

### GEO KAMI API Integration

```
Base URL: https://api.geokami.com (configurable in settings)
Auth:     Bearer gk_xxx (35-char API key)
Endpoints:
  POST /api/scan              — Initiate scan (async, returns scan_id)
  GET  /api/scans/{id}        — Get scan result
  GET  /api/scans/{id}/status — Lightweight status check
  POST /api/verify            — Quick verify specific checks
  GET  /api/account           — Account info + points balance
  GET  /api/health            — API health check
```

### Test Conventions

- Test every fix action with a dry-run first before applying.
- Every `class-*.php` should have a corresponding test scenario in the development plan.
- Before marking a fix module "complete", verify the fix actually changes the GEO KAMI scan score for that check.
- Test on at least one live WooCommerce store (colored-contacts.us is the reference store).

---

## 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:
- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

## 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior WordPress developer say this is overcomplicated?" If yes, simplify.

## 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:
- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:
- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

## 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:
- "Add scan button" → "Click button → API call fires → result displays in dashboard"
- "Fix llms.txt" → "Visit /llms.txt → returns proper markdown → GEO KAMI score improves for llms_txt_quality"
- "Add MCP endpoint" → "POST to /.well-known/mcp.json → returns valid MCP card → tools/list responds"

For multi-step tasks, state a brief plan:
```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

---

**These guidelines are working if:** fewer unnecessary changes in diffs, fewer rewrites due to overcomplication, and clarifying questions come before implementation rather than after mistakes.
