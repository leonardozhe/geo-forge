<?php
/**
 * Well-Known URI router.
 *
 * Serves virtual files at the site root via the WordPress Rewrite API:
 *   /llms.txt              → generated llms.txt markdown
 *   /.well-known/mcp.json  → (future milestone)
 *   /.well-known/a2a.json  → (future milestone)
 *
 * Why rewrite rules instead of physical files?
 *   - Works on Apache, Nginx, LiteSpeed without filesystem writes.
 *   - Content is generated dynamically — always up to date.
 *   - No need to touch .htaccess / nginx.conf (per AGENTS.md).
 *
 * Flow:
 *   1. User visits /llms.txt
 *   2. WordPress's .htaccess routes to index.php with our query var
 *   3. We intercept on `template_redirect`, render, and exit.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

use GEO_Forge\Log\PluginLogger;
use GEO_Forge\Traffic\BotFamily;
use GEO_Forge\Traffic\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure the generators are autoloadable (their classes live alongside the router).
class_exists( LlmsTxt::class );
class_exists( SecurityTxt::class );

class Router {

	/** Query var we register to carry the route name through WP's rewrite layer. */
	public const QUERY_VAR = 'geo_forge_well_known';

	/**
	 * Map of route name → URL regex (matched against REQUEST_URI after WP normalization).
	 * Adding a new route: add entry here + a handler in dispatch().
	 */
	private const ROUTES = array(
		'llms_txt'      => '^llms\.txt/?$',
		'security_txt'  => '^\.well-known/security\.txt/?$',
	);

	/** Tracks the current set of registered routes — used for flush-on-upgrade detection. */
	private const ROUTES_VERSION = 2;

	/**
	 * Wire everything up. Called from GeoForge::register_hooks().
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
		add_action( 'template_redirect', array( self::class, 'dispatch' ), 1 );
		add_action( 'admin_init', array( self::class, 'maybe_flush_on_upgrade' ) );
	}

	/**
	 * Register rewrite rules. One per well-known route.
	 */
	public static function register_rewrite_rules(): void {
		foreach ( self::ROUTES as $name => $regex ) {
			add_rewrite_rule(
				$regex,
				'index.php?' . self::QUERY_VAR . '=' . $name,
				'top'
			);
		}
	}

	/**
	 * Tell WP to accept our query var (otherwise it gets stripped).
	 *
	 * @param string[] $vars Existing public query vars.
	 * @return string[]
	 */
	public static function register_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Intercept matching requests and serve our generated content.
	 */
	public static function dispatch(): void {
		$route = get_query_var( self::QUERY_VAR, '' );
		if ( '' === $route || ! array_key_exists( $route, self::ROUTES ) ) {
			return;
		}

		PluginLogger::debug( 'Well-known route hit.', array( 'route' => $route ) );

		// Log the hit to traffic (since Capture runs at priority 999 and never
		// reaches well-known routes dispatched at priority 1).
		self::capture_traffic( $route );

		$content     = '';
		$content_type = 'text/plain; charset=utf-8';

		switch ( $route ) {
			case 'llms_txt':
				$content      = LlmsTxt::serve();
				$content_type = 'text/plain; charset=utf-8'; // llms.txt spec mandates text/plain
				break;

			case 'security_txt':
				$content      = SecurityTxt::serve();
				$content_type = 'text/plain; charset=utf-8';
				break;
		}

		status_header( 200 );
		header( 'Content-Type: ' . $content_type );
		header( 'Cache-Control: public, max-age=3600' );
		header( 'X-GEO-Forge: true' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated content, no user HTML.
		echo $content;
		exit;
	}

	/**
	 * Force WordPress to rebuild its rewrite rules.
	 * Called on activation/deactivation. MUST be called after our rules are registered.
	 */
	public static function flush_rules(): void {
		self::register_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * On admin init, check if the stored routes version is behind the current
	 * code. If so, flush rewrite rules. This catches the case where a new
	 * route (e.g. security.txt) is added in a plugin update — without a flush,
	 * the new route would 404 until the user manually saves permalinks.
	 */
	public static function maybe_flush_on_upgrade(): void {
		$stored = (int) get_option( 'geo_forge_routes_version', 0 );
		if ( $stored < self::ROUTES_VERSION ) {
			self::flush_rules();
			update_option( 'geo_forge_routes_version', self::ROUTES_VERSION );
			PluginLogger::info(
				'Flushed rewrite rules on upgrade.',
				array( 'old_version' => $stored, 'new_version' => self::ROUTES_VERSION )
			);
		}
	}

	/**
	 * Record the well-known route hit for the Traffic module.
	 * The Trap\Capture hook runs at priority 999 and will never see
	 * requests dispatched here at priority 1 — so we log them directly.
	 */
	private static function capture_traffic( string $route ): void {
		if ( ! class_exists( Store::class ) || ! class_exists( BotFamily::class ) ) {
			return;
		}

		$ip_hash = hash( 'sha256', ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . '|' . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$host    = $_SERVER['HTTP_HOST'] ?? '';
		$uri     = $_SERVER['REQUEST_URI'] ?? '/';
		$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		// Detect bot family from UA.
		$family = BotFamily::Unknown;
		$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
		foreach ( BotFamily::cases() as $f ) {
			if ( null !== $f->ua_pattern() && preg_match( $f->ua_pattern(), $ua ) ) {
				$family = $f;
				break;
			}
		}

		Store::record(
			$family,
			'well_known',
			( is_ssl() ? 'https' : 'http' ) . '://' . $host . $uri,
			200,
			$ip_hash,
			(string) $method
		);
	}
}
