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

use GEO_Forge\Log\Logger;

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
		'llms_txt'     => '^llms\.txt/?$',
		'security_txt' => '^\.well-known/security\.txt/?$',
	);

	/**
	 * Wire everything up. Called from GeoForge::register_hooks().
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
		add_action( 'template_redirect', array( self::class, 'dispatch' ), 1 );
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

		Logger::debug( 'Well-known route hit.', array( 'route' => $route ) );

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
}
