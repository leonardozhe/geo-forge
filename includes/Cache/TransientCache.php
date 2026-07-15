<?php
/**
 * Thin wrapper around WordPress Transients with typed get/set/forget.
 *
 * Why not use Transients directly? Centralizes:
 *   - Key prefixing (so we never collide with other plugins).
 *   - Default TTLs per cache type.
 *   - A place to swap in object-cache-aware behavior later without touching callers.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TransientCache {

	/** All keys are prefixed with this to avoid collisions. */
	private const PREFIX = 'geo_forge_';

	/** Recommended TTLs. Filters let site owners override per-key. */
	private const TTL = array(
		'scan_result'  => DAY_IN_SECONDS,
		'account_info' => HOUR_IN_SECONDS,
	);

	/**
	 * @param string $key    Short key (without prefix). E.g. 'scan_result'.
	 * @param mixed  $default Returned when the transient is missing or expired.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$value = get_transient( self::PREFIX . $key );
		return false === $value ? $default : $value;
	}

	/**
	 * @param string $key Short key (without prefix).
	 * @param mixed  $value Any value serializable by WP (scalars, arrays, objects).
	 * @param int|null $ttl Seconds. null → uses default from self::TTL, or 1 hour fallback.
	 */
	public static function set( string $key, mixed $value, ?int $ttl = null ): void {
		if ( null === $ttl ) {
			$ttl = self::TTL[ $key ] ?? HOUR_IN_SECONDS;
		}

		/**
		 * Filter the TTL for a specific cache key.
		 *
		 * @param int    $ttl Seconds.
		 * @param string $key Cache key (without prefix).
		 */
		$ttl = (int) apply_filters( 'geo_forge_cache_ttl', $ttl, $key );

		set_transient( self::PREFIX . $key, $value, max( 0, $ttl ) );
	}

	public static function forget( string $key ): void {
		delete_transient( self::PREFIX . $key );
	}

	/**
	 * Remove every transient we own.
	 * Useful on uninstall and on "hard reset" from the dashboard.
	 */
	public static function flush_all(): void {
		global $wpdb;

		// LIKE query is OK here — prefix is hardcoded, not user input.
		$like = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}
}
