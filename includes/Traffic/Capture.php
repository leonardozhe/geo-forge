<?php
/**
 * AI traffic capture.
 *
 * Hooks into `template_redirect` and, for requests matching one of our
 * detection signals, records a row via `Traffic\Store`.
 *
 * Signals detected:
 *   1. Well-known routes (llms.txt, security.txt) — always record.
 *   2. Markdown negotiation (`Accept: text/markdown`) — always record.
 *   3. Known AI bot User-Agent — sampled (see Traffic\Logger::record()).
 *
 * Non-matching requests return immediately — no overhead for regular visitors.
 *
 * Why `template_redirect`?
 *   - WP is fully loaded, so we can call get_query_var() for well-known routes.
 *   - Fires after headers are sent, so we can observe the response status.
 *   - Runs on every page load — which is what we want for capture.
 *
 * Why NOT register_shutdown_function?
 *   - We want the response status from the actual handler, which shutdown
 *     can see — but template_redirect fires before the response is finalized.
 *     We capture the *intended* status (200 for most pages) which is good enough.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Traffic;

use GEO_Forge\Log\Logger as PluginLogger;
use GEO_Forge\WellKnown\Router;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capture {

	/**
	 * User-Agent tokens used by GEO KAMI's own scan engine when it probes
	 * the site during a scan (e.g. /.well-known/mcp.json discovery checks).
	 * These are our own tooling, not external AI visitor traffic — never
	 * record them so the Traffic page reflects real AI agents only.
	 */
	private const SCANNER_UA_TOKENS = array( 'geokami', 'agentready' );

	/**
	 * Wire the capture hook. Called from GeoForge::register_hooks().
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'on_template_redirect' ), 999 );
	}

	/**
	 * Main dispatch — runs on every page load.
	 * Returns fast for non-AI requests.
	 */
	public static function on_template_redirect(): void {
		// Don't capture admin / REST / cron / AJAX — those aren't public traffic.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		$detection = self::detect();
		if ( null === $detection ) {
			return; // not AI traffic
		}

		$ip_hash = self::hash_ip();
		$url     = self::current_url();
		$method  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );

		// Record the real outcome. 404s are high-value signals — the agent
		// asked for something we don't provide.
		$status = is_404() ? 404 : 200;

		Store::record(
			$detection['family'],
			$detection['source'],
			$url,
			$status,
			$ip_hash,
			(string) $method,
			null
		);
	}

	/**
	 * Detect whether the current request is AI traffic.
	 *
	 * @return array{family:BotFamily, source:string}|null
	 */
	private static function detect(): ?array {
		// GEO KAMI's own scan engine probing the site — not AI visitor traffic.
		if ( self::is_geokami_scanner() ) {
			return null;
		}

		// 1. Well-known route (highest priority — always record).
		$well_known = get_query_var( Router::QUERY_VAR, '' );
		if ( '' !== $well_known ) {
			return array(
				'family' => self::family_from_ua(),
				'source' => 'well_known',
			);
		}

		// 1b. Well-known-style paths we do NOT serve (e.g. /.well-known/mcp.json,
		//     a2a.json, llm*.txt variants) — AI agents probing for data we don't
		//     provide. Recorded as well_known so the Traffic page can flag them.
		$path = (string) ( wp_parse_url( self::current_url(), PHP_URL_PATH ) ?? '' );
		if ( preg_match( '#(^|/)\.well-known/[A-Za-z0-9._-]+(\.json|\.txt)?$#', $path )
			|| preg_match( '#/llm?s?[-A-Za-z0-9._]*\.txt$#', $path ) ) {
			return array(
				'family' => self::family_from_ua(),
				'source' => 'well_known',
			);
		}

		// 2. Markdown negotiation.
		$accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ?? '' ) );
		if ( str_contains( $accept, 'text/markdown' ) || str_contains( $accept, 'text/x-markdown' ) ) {
			return array(
				'family' => self::family_from_ua(),
				'source' => 'markdown',
			);
		}

		// 3. Known AI bot by User-Agent.
		$family = self::family_from_ua();
		if ( 'unknown' !== $family && 'other' !== $family ) {
			return array(
				'family' => $family,
				'source' => 'bot_ua',
			);
		}

		return null;
	}

	/**
	 * Is this request from GEO KAMI's own scanner? Case-insensitive match
	 * on the documented scanner user-agents (GEO-Kami-Scanner, AgentReadyScanner).
	 */
	private static function is_geokami_scanner(): bool {
		$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );
		// Normalize away hyphens/spaces so GEO-Kami-Scanner, GeoKamiBot and
		// "GEO KAMI Scanner" all match the same token.
		$ua = str_replace( array( '-', ' ', '_' ), '', $ua );
		foreach ( self::SCANNER_UA_TOKENS as $geo_forge_token ) {
			if ( str_contains( $ua, $geo_forge_token ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Match the current User-Agent against known bot patterns.
	 */
	private static function family_from_ua(): string {
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		if ( '' === $ua ) {
			return 'unknown';
		}

		return BotFamily::detect( $ua );
	}

	/**
	 * Hash the remote IP with a per-site salt.
	 * The salt is derived from WordPress's AUTH_KEY so it's:
	 *   - consistent across requests on this site
	 *   - different from other sites (so the same IP yields different hashes)
	 *   - never logged, never stored in plaintext
	 */
	private static function hash_ip(): string {
		$ip   = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'geo-forge-default-salt';

		if ( '' === $ip ) {
			return '0000000000000000000000000000000000000000000000000000000000000000';
		}

		return hash( 'sha256', $salt . '|' . $ip );
	}

	/**
	 * Build a representation of the current URL (scheme+host+path+query).
	 * We don't log post bodies or cookies — just what the bot requested.
	 */
	private static function current_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
		$uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
		return $scheme . '://' . $host . $uri;
	}
}
