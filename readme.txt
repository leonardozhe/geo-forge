=== GEO Forge ===
Contributors:      geokami
Tags:              woocommerce, geo, ai, llms.txt, mcp
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.1
Stable tag:        1.0.0-dev
License:           GPL v3+
License URI:       https://www.gnu.org/licenses/gpl-3.0.html

Forge your WooCommerce store for the AI era — one-click scan, fix, and monitor for AI agent discoverability.

== Description ==

**GEO Forge** transforms your WooCommerce store from "AI-blind" to "Agent-Ready" — so ChatGPT, Claude, Perplexity, Google AI Overviews, and other AI agents can discover, understand, and transact with your products.

> Powered by [GEO KAMI](https://geokami.com) cloud scanning.

= What it does =

* **One-click AI visibility scan** — calls the GEO KAMI API to audit your store against 22+ AI-readiness checks.
* **Auto-fix core issues** — generates `llms.txt`, `security.txt`, MCP and A2A agent cards, structured data enhancements, markdown negotiation, and more.
* **Continuous monitoring** — detects content, theme, and plugin changes, then re-scans automatically.
* **AI traffic insights** — records when AI agents crawl your store (Milestone 4).
* **Score trends** — track your AI-readiness score over time.

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

= Privacy =

* Your GEO KAMI API key is stored in `wp_options` (access-controlled by WordPress).
* No data leaves your store except the scan requests sent to `api.geokami.com` (configurable).
* AI traffic logs store only SHA-256 IP hashes — never raw IP addresses.

= Credits =

GEO Forge is developed by the GEO KAMI team. Scan scoring and suggestions are provided by the GEO KAMI Cloud API.

== Installation ==

1. Upload the `geo-forge` folder to `/wp-content/plugins/`, or install via **Plugins → Add New** in wp-admin.
2. Activate the plugin through the **Plugins** menu.
3. Go to **WooCommerce → GEO Forge → Settings** and enter your GEO KAMI API key.
4. Click **Health Check** to verify connectivity.
5. Visit **WooCommerce → GEO Forge → Dashboard** and click **Scan Now**.

== Frequently Asked Questions ==

= Is GEO Forge free? =

The plugin itself is free and open source (GPL v3+). Scans consume credits from your GEO KAMI account. Free-tier accounts receive a monthly scan allowance; paid plans unlock higher limits and advanced features.

= Does GEO Forge slow down my store? =

No. Frontend assets (CSS/JS) are loaded only on GEO Forge admin pages. The virtual routes (`/llms.txt`, `/.well-known/*`) are served via WordPress rewrite rules with no additional database queries.

= Will GEO Forge conflict with my SEO plugin? =

No. GEO Forge complements — not replaces — Rank Math, Yoast, SEOPress, etc. Where both plugins touch the same output (e.g., Schema.org), GEO Forge's additions are merged safely.

= Is my API key safe? =

Your API key is stored in `wp_options`, access-controlled by WordPress permissions (only `manage_woocommerce` users can view or change it). The key is sent over HTTPS only.

= Can I use GEO Forge on a non-WooCommerce site? =

Not yet. GEO Forge is built specifically for WooCommerce. A standalone WordPress version is on the roadmap.

== Screenshots ==

1. Dashboard showing AI Score, category breakdown, and the Scan Now button.
2. Settings page with API key input and Health Check.
3. Fix Center with auto-fixable issues grouped by priority.
4. llms.txt visual editor.
5. Logs page with level filter and context viewer.

== Changelog ==

= 1.0.0-dev =
* Initial development build.
* End-to-end scan via GEO KAMI Cloud API.
* Dashboard with score tiles, category breakdown, Scan Now button.
* Settings page with API key + Health Check.
* Structured logger with admin UI and auto-pruning.
* Fatal-error capture for plugin-originated errors only.

== Upgrade Notice ==

= 1.0.0-dev =
Initial development release.
