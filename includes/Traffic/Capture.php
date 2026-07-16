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

		Store::record(
			$detection['family'],
			$detection['source'],
			$url,
			200, // intended status at this point
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
		// 1. Well-known route (highest priority — always record).
		$well_known = get_query_var( Router::QUERY_VAR, '' );
		if ( '' !== $well_known ) {
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
