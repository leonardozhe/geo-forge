=== GEO Forge ===
Contributors:      geokami
Tags:              woocommerce, ai, seo, llms.txt, structured-data
Requires at least: 6.0
Tested up to:      7.0
Requires PHP:      8.1
WC requires at least: 8.0
Stable tag:        1.0.109
License:           GPL v3+
License URI:       https://www.gnu.org/licenses/gpl-3.0.html

One-click AI visibility scan, fix, and monitor for WooCommerce stores. llms.txt, MCP, A2A, structured data.

== Description ==

**GEO Forge** transforms your WooCommerce store from "AI-blind" to "Agent-Ready" — so ChatGPT, Claude, Perplexity, Google AI Overviews, and other AI agents can discover, understand, and transact with your products.

> Powered by [GEO KAMI](https://geokami.com) cloud scanning.

= Why GEO? =

Over **40% of searches** now return AI-generated answers. If AI agents can't parse your store, your products don't exist to ChatGPT, Perplexity, Claude, or Google AI — no matter how well you rank in traditional search.

GEO Forge bridges this gap: audit 22+ AI-readiness checks, auto-deploy fixes, and make your store visible to the agents that now drive real purchase decisions.

= What it does =

* **One-click AI visibility scan** — calls the GEO KAMI API to audit your store against 22+ AI-readiness checks across 7 categories.
* **Auto-fix core issues** — generates `llms.txt`, `security.txt`, MCP and A2A agent cards, structured data enhancements, markdown negotiation, and more.
* **Continuous monitoring** — detects content, theme, and plugin changes, then re-scans automatically.
* **AI traffic insights** — records when AI agents crawl your store.
* **Score trends** — track your AI-readiness score over time with detailed per-check history.

= AI protocols supported =

* `llms.txt` — the standard for AI-readable site summaries
* `security.txt` — RFC 9116 security contact disclosure
* MCP server card + endpoint (Model Context Protocol)
* A2A agent card + endpoint (Agent-to-Agent)
* Agent Skills declaration
* OpenAPI spec + API Catalog (RFC 9727)
* Markdown content negotiation (`Accept: text/markdown`)
* Structured data enhancements (Schema.org / JSON-LD)

= Requirements =

* WordPress 6.0 or higher
* WooCommerce 8.0 or higher
* PHP 8.1 or higher
* A GEO KAMI API key (free tier available at [geokami.com](https://geokami.com))

== Privacy & External Services ==

This plugin communicates with the GEO KAMI Cloud API (`api.geokami.com`, configurable) for scanning and account management.

**Data sent to GEO KAMI during a scan:**

* Your site's domain name
* WordPress version and WooCommerce version
* Product count (aggregate, no product data)
* Site language and permalink structure
* SSL status

**Data NOT sent:**

* Raw product data, customer data, or order data
* Admin credentials or sensitive configuration
* Raw IP addresses (stored only as SHA-256 hashes for AI traffic logs)

**Local storage:**

* Your GEO KAMI API key is stored in `wp_options` (access-controlled by WordPress; only administrators can view or change it)
* Scan results, fix history, logs, and AI traffic logs are stored in your local WordPress database
* Generated files (llms.txt, security.txt, robots.txt) are stored as WordPress options and served via virtual routes

**Third-party services:**

* `api.geokami.com` — scan execution and account management (GEO KAMI Cloud)
* `update.wordpress.org` — standard plugin updates via the WordPress Plugin Directory (after this plugin is listed)

You can review the GEO KAMI privacy policy at [geokami.com/privacy](https://geokami.com/privacy).

== Installation ==

1. Upload the `geo-forge` folder to `/wp-content/plugins/`, or install via **Plugins → Add New** in wp-admin.
2. Activate the plugin through the **Plugins** menu.
3. Go to **GEO Forge → Settings** and enter your GEO KAMI API key.
4. Click **Health Check** to verify connectivity.
5. Visit **GEO Forge → Dashboard** and click **Scan Now**.

== Frequently Asked Questions ==

= Is GEO Forge free? =

The plugin itself is free and open source (GPL v3+). Scans consume credits from your GEO KAMI account. Free-tier accounts receive a monthly scan allowance; paid plans unlock higher limits and advanced features.

= Does GEO Forge slow down my store? =

No. Frontend assets (CSS/JS) are loaded only on GEO Forge admin pages. The virtual routes (`/llms.txt`, `/.well-known/*`) are served via WordPress rewrite rules with no additional database queries.

= Will GEO Forge conflict with my SEO plugin? =

No. GEO Forge complements — not replaces — Rank Math, Yoast, SEOPress, etc. Where both plugins touch the same output (e.g., Schema.org), GEO Forge's additions are merged safely.

= Is my API key safe? =

Your API key is stored in `wp_options`, access-controlled by WordPress permissions (only `manage_options` users can view or change it). The key is sent over HTTPS only, to the configured API base URL.

= Can I use GEO Forge on a non-WooCommerce site? =

Not yet. GEO Forge is built specifically for WooCommerce. A standalone WordPress version is on the roadmap.

= What happens when I uninstall the plugin? =

All plugin data is removed: custom tables, options, transients, scheduled hooks, and rewrite rules. If you want to preserve data for re-installation, export it before uninstalling.

== Screenshots ==

1. Dashboard showing AI Score, category breakdown, and the Scan Now button.
2. Settings page with API key input and Health Check.
3. Optimizations page with auto-fixable issues grouped by priority.
4. llms.txt visual editor with Save/Regenerate.
5. Score History with per-scan detail view.

== Changelog ==

= 1.0.109 =
* New: "Missing Content — LLM 404s" card now supports deleting individual records or clearing all of them (per-row Delete + Delete all button).
= 1.0.108 =
* Fix: GEO-Kami-Scanner UA now correctly excluded from traffic capture — hyphen/space normalization so all GEO KAMI scanner variants (GEO-Kami-Scanner, GeoKamiBot, AgentReadyScanner) are skipped.
= 1.0.107 =
* Fix: GEO KAMI's own scanner probes (GEO-Kami-Scanner / AgentReadyScanner user-agents) are no longer recorded as traffic — the Traffic page and the "Missing Content — LLM 404s" card now reflect only real external AI agents, not the plugin's own scan checks.
= 1.0.106 =
* UI: Cover is now enabled after Audit (pass or fail) — you decide whether to take over the SEO-owned file or keep the current output. Cover stays disabled only until the first Audit runs.
= 1.0.105 =
* New: AI crawler UA coverage expanded to the full documented set (Cloudflare AI Crawl Control reference + OpenAI crawler docs) — added OAI-SearchGPT, OAI-AdsBot, GrokBot/xAI-Grok, PanguBot, meta-externalagent/externalfetcher, DuckAssistBot, Applebot, Google-CloudVertex-Crawl and Googlebot. Traffic now labels xAI (Grok) and Huawei (Petal/Pangu) as their own families.
* Fix: robots.txt generator/audit share one canonical AI user-agent list, so the audit checks exactly the bots the generator allows.
= 1.0.104 =
* Fix: Traffic retention is now safe — the stats table is created lazily and detail rows are only deleted after the rollup succeeds (no silent data loss when the table is missing).
= 1.0.103 =
* New: Traffic retention — detailed activity is kept for the last 7 days; older detail rows are rolled into daily per-bot-family stats and then cleared (so trends/totals survive). Runs daily via cron and opportunistically on the Traffic page.
= 1.0.102 =
* New: Traffic monitors LLM 404s — AI agents' requests for content we don't provide (e.g. /.well-known/mcp.json, markdown variants) are recorded and highlighted in a "Missing Content — LLM 404s" card on the Traffic page.
* UI: Scan Now button progress now shows an estimated percentage (e.g. 42%) instead of a poll counter.
= 1.0.101 =
* Fix: Scan animation now shows only inside the Scan Now button (spinner + Scanning…) — the adjacent status text no longer duplicates it.
* Fix: Scan timestamps stored in UTC and displayed in the WordPress timezone consistently (dashboard header + score history).
* UI: Dashboard title shows the plugin version dynamically (GEO Forge v1.0.101).
= 1.0.100 =
* Fix: Score history now refreshes immediately after a scan — the cached history keys are properly invalidated on completion (previously the dashboard could show stale history for up to 5 minutes).
* UI: Scan Now button shows an inline spinner + "Scanning…" while the scan runs.
= 1.0.99 =
* Fix: REST GET routes were registered with the literal string 'READABLE', which WordPress 7.x no longer normalizes to GET — all read-only endpoints (scan status, scan history, logs, fix list) returned 404. Now uses WP_REST_Server::READABLE (evaluates to GET).
= 1.0.98 =
* Fix: Admin JS now uses version-stamped filenames so Cloudflare (with "Ignore query string") and other edge caches always pick up updated scripts instead of serving stale copies for weeks.
= 1.0.97 =
* New: Covered (audit) mode workflow — when another plugin or a physical file manages llms.txt / robots.txt, run Audit to check the output against GEO Forge's standard, then Cover (override) when it falls short or Ignore when it's fine. No more blind "don't fight": the decision is explicit and reversible.
* New: Traffic → Recent Activity pagination (prev/next + page count, keeps bot family / source filters).
= 1.0.96 =
* New: Major SEO plugin detection (Rank Math / Yoast / SEOPress / AIOSEO). When another plugin or a physical file already manages llms.txt or robots.txt, GEO Forge switches that fix to audit-only mode — it stops registering competing routes, stops writing content, and flags the status as "Covered" in Optimizations and Settings instead of fighting for the output.
= 1.0.95 =
* New: Async scans — the Scan Now button starts a scan and polls /scan/status instead of holding the request open; scheduled scans no longer block wp-cron (initiate + single follow-up event).
* New: Multilingual llms.txt — configure extra locales (e.g. zh_CN,ja) in Settings → Automation to get /llms-{locale}.txt variants with localized headings and store URLs (Polylang/WPML aware).
= 1.0.94 =
* New: Scheduled jobs — daily regeneration of llms.txt, llms-full.txt, security.txt and robots.txt (local, no points consumed), plus optional scheduled GEO KAMI scans. Configurable under Settings → Automation.
* New: llms-full.txt route with a complete product index (sitemap + full list linked from llms.txt).
* Fix: SEO plugin compatibility — robots.txt AI rules now append after Rank Math / Yoast output instead of being overwritten.
* Fix: Structured data now merges aggregateRating into WooCommerce's Product schema (and Rank Math's schema graph) instead of emitting a duplicate JSON-LD block.
* Fix: API key is now encrypted at rest; the Settings page no longer echoes it back.
* Fix: llms.txt / security.txt / robots.txt options are no longer autoloaded (performance).
* Fix: Settings page shows save confirmation and compatibility warnings (physical files, plain permalinks).
= 1.0.93 =
* Fix: All view files now use full `geo_forge_` prefix (WordPress standard) instead of `gf_`.
* Fix: Added wp_cache_get/set to all SELECT queries in Logger.php (reset, prune, recent).
* Fix: Added cache invalidation after all mutations (INSERT/DELETE/TRUNCATE/DROP).
* Fix: Removed all phpcs:disable comments from view files.

= 1.0.92 =
* Fix: Last ExceptionNotEscaped error — replaced sanitize_text_field with esc_html in exception context.
* Fix: All DirectDatabaseQuery/NoCaching warnings — added wp_cache_get/set/delete wrappers in Scanner, Logger, Store, Installer.
* Fix: All NonPrefixedVariableFound warnings in page-dashboard.php — prefixed all 53+ variables with gf_.
* Fix: Logger.php reset() still used $table variable — now inline {$wpdb->prefix}geo_forge_logs.

= 1.0.91 =
* Fix: All ExceptionNotEscaped errors — ApiException constructor now accepts string code; all args wrapped with esc_html()/absint()/esc_url_raw()/sanitize_text_field().
* Fix: All SQL InterpolatedNotPrepared/NotPrepared errors — removed dynamic $sql variables; inline {$wpdb->prefix}table_name in queries.
* Fix: DirectDatabaseQuery in dashboard — moved query to Scanner::get_score_history() method.
* Fix: Removed all phpcs:ignore/phpcs:disable workarounds from Client.php, Scanner.php, Store.php, Logger.php, Installer.php, page-dashboard.php.

= 1.0.90 =
* Fix: Resolved all WordPress Plugin Check errors — proper output escaping (esc_html, esc_attr, wp_kses) on all view templates.
* Fix: Replaced mt_rand() with wp_rand() for better randomness.
* Fix: Sanitized all $_SERVER variables with sanitize_text_field(wp_unslash(...)).
* Fix: Added $wpdb->prepare() for SQL queries with dynamic LIMIT values.
* Fix: Prefixed all global-scope variables with plugin prefix per WordPress coding standards.
* Fix: Reduced readme.txt tags to 5, shortened short description to under 150 chars.

= 1.0.89 =
* New: 'Rebuild Table' button in Logs page — drops and recreates the logs table + resets min_level option to default (info). Fixes 'Logs page shows nothing' on existing installations.
* New: REST endpoint POST /logs/reset.

= 1.0.88 =
* Diagnostic build for Score History Details debugging.

= 1.0.87 =
* BREAKING: Removed custom auto-updater (Updater.php). Future updates come exclusively from WordPress.org. Required for WP.org submission.
* Removed all console.log statements from admin JS.
* Simplified User-Agent header to standard plugin format.
* Expanded Privacy section in readme.txt.
* Rewrote uninstall.php to properly delete all plugin data (required by WP.org).
* Fixed About tab hero title visibility on gradient background.

= 1.0.86 =
* Fix: About hero h2 title color now visible on purple gradient background.

= 1.0.85 =
* Fix: Settings Save button starts disabled, enables only when textarea is modified.
* Fix: Logs page now shows entries on existing installations (auto-migrates stale min_level option).

= 1.0.84 =
* About tab redesign: professional inline SVG line icons, compact WHY + WHAT layout.
* Fix: Logs page default min level changed from warning to info.

= 1.0.83 =
* Fix: Score History View Details uses embedded scan data (no REST dependency).
* Added PHP opcache auto-invalidation on version change.
* Regenerate handlers wrapped in try/catch to prevent Cloudflare 520 errors.

= 1.0.82 =
* Fix: Score History Details now shows actual error messages.
* Fix: Settings Save button disabled by default, enables on modification.

= 1.0.81 =
* Fix: Score History View Details fetches correct scan by ID (new `/scan/{id}` REST endpoint).
* Fix: Optimizations page refreshes after Apply/Verify/Undo.
* Fix: Settings Save/Regenerate buttons show visible feedback.

= 1.0.0 =
* Initial release.
* End-to-end scan via GEO KAMI Cloud API.
* Dashboard with score tiles, category breakdown, Scan Now button.
* Settings page with API key + Health Check.
* Structured logger with admin UI and auto-pruning.
* Fatal-error capture for plugin-originated errors only.

== Upgrade Notice ==

= 1.0.90 =
WordPress Plugin Check compliance: all output escaping, SQL preparation, input sanitization, and variable naming fixed.

= 1.0.86 =
Visual fix for About tab + WordPress.org compliance preparation.
