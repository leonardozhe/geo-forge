<?php
/**
 * Custom auto-updater.
 *
 * Hooks into WordPress's plugin update flow so that releases published at
 * `https://update.geokami.com/geo-forge/update.json` show up in wp-admin's
 * standard "Plugins → Update" UI with the same UX as .org plugins.
 *
 * Server layout (one directory per product):
 *
 *   https://update.geokami.com/
 *   ├── geo-forge/
 *   │   ├── update.json             ← metadata (WP standard schema)
 *   │   └── geo-forge-1.1.0.zip     ← package
 *   └── future-plugin-b/
 *       └── update.json
 *
 * update.json schema (matches WP's plugin-update response):
 *
 *   {
 *     "name":         "GEO Forge",
 *     "slug":         "geo-forge",
 *     "version":      "1.1.0",
 *     "requires_php": "8.1",
 *     "requires":     "6.0",
 *     "tested":       "6.7",
 *     "download_url": "https://update.geokami.com/geo-forge/geo-forge-1.1.0.zip",
 *     "homepage":     "https://geokami.com/geo-forge",
 *     "last_updated": "2026-07-20 10:00:00",
 *     "sections":     { "description": "...", "changelog": "..." }
 *   }
 *
 * Caching:
 *   The remote update.json is cached in the `geo_forge_remote_update`
 *   transient for 12 hours. Bypass via ?geo_forge_refresh_update=1 on any
 *   admin page, or by clearing the transient.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Updater;

use GEO_Forge\Log\Logger as PluginLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Updater {

	/** Default manifest URL. Override via `geo_forge_update_url` filter. */
	private const DEFAULT_MANIFEST_URL = 'https://update.geokami.com/geo-forge/update.json';

	private const CACHE_TTL = 6 * HOUR_IN_SECONDS;
	private const CACHE_KEY = 'geo_forge_remote_update';

	/**
	 * Wire the updater hooks. Called from GeoForge::register_hooks().
	 */
	public static function register(): void {
		// Don't run on front-end requests that aren't admin — updates only
		// matter where WP's update checker runs (admin + wp-cron).
		add_filter( 'site_transient_update_plugins', array( self::class, 'inject_update' ) );
		add_filter( 'plugins_api', array( self::class, 'plugin_info' ), 10, 3 );

		// Debug: visiting any admin page with ?geo_forge_refresh_update=1
		// clears the cache so the next check pulls fresh data.
		add_action( 'admin_init', array( self::class, 'maybe_refresh_cache' ) );

		// Debug: visiting any admin page with ?geo_forge_check_update=1
		// shows update system status in admin notices.
		add_action( 'admin_init', array( self::class, 'debug_update_status' ) );
	}

	/**
	 * Debug: show update system status.
	 */
	public static function debug_update_status(): void {
		if ( ! isset( $_GET['geo_forge_check_update'] ) ) {
			return;
		}

		$installed_version = GEO_FORGE_VERSION;
		$manifest = self::fetch_manifest();
		$remote_version = $manifest['version'] ?? 'unknown';
		$has_update = version_compare( $remote_version, $installed_version, '>' );

		add_action( 'admin_notices', function() use ( $installed_version, $remote_version, $has_update, $manifest ) {
			echo '<div class="notice notice-info"><p>';
			echo '<strong>GEO Forge Update Debug:</strong><br>';
			echo 'Installed version: ' . esc_html( $installed_version ) . '<br>';
			echo 'Remote version: ' . esc_html( $remote_version ) . '<br>';
			echo 'Has update: ' . ( $has_update ? '✅ YES' : '❌ NO' ) . '<br>';
			if ( $manifest ) {
				echo 'Download URL: ' . esc_html( $manifest['download_url'] ?? 'N/A' );
			}
			echo '</p></div>';
		} );
	}

	/**
	 * Inject our update info into WP's update_plugins transient.
	 *
	 * @param object|false $transient The cached update_plugins transient.
	 * @return object|false
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		$manifest = self::fetch_manifest();
		if ( null === $manifest ) {
			return $transient;
		}

		$remote_version = (string) ( $manifest['version'] ?? '' );
		if ( '' === $remote_version ) {
			return $transient;
		}

		// Only offer an update if the remote version is actually newer.
		if ( version_compare( GEO_FORGE_VERSION, $remote_version, '>=' ) ) {
			return $transient;
		}

		$transient->response[ GEO_FORGE_BASENAME ] = (object) array(
			'id'           => self::cache_bust_url(),
			'slug'         => 'geo-forge',
			'plugin'       => GEO_FORGE_BASENAME,
			'new_version'  => $remote_version,
			'url'          => (string) ( $manifest['homepage'] ?? '' ),
			'package'      => (string) ( $manifest['download_url'] ?? '' ),
			'requires'     => (string) ( $manifest['requires'] ?? '' ),
			'requires_php' => (string) ( $manifest['requires_php'] ?? '' ),
			'tested'       => (string) ( $manifest['tested'] ?? '' ),
			'icons'        => array(),
			'banners'      => (array) ( $manifest['banners'] ?? array() ),
		);

		return $transient;
	}

	/**
	 * Provide data for the "View version X details" popup.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action Requested action (should be 'plugin_information').
	 * @param object             $args   Request args, including $args->slug.
	 * @return false|object
	 */
	public static function plugin_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || 'geo-forge' !== $args->slug ) {
			return $result;
		}

		$manifest = self::fetch_manifest();
		if ( null === $manifest ) {
			return $result;
		}

		$info = (object) array(
			'name'          => (string) ( $manifest['name'] ?? 'GEO Forge' ),
			'slug'          => 'geo-forge',
			'version'       => (string) ( $manifest['version'] ?? '' ),
			'requires'      => (string) ( $manifest['requires'] ?? '' ),
			'requires_php'  => (string) ( $manifest['requires_php'] ?? '' ),
			'tested'        => (string) ( $manifest['tested'] ?? '' ),
			'last_updated'  => (string) ( $manifest['last_updated'] ?? '' ),
			'homepage'      => (string) ( $manifest['homepage'] ?? '' ),
			'download_link' => (string) ( $manifest['download_url'] ?? '' ),
			'sections'      => (array) ( $manifest['sections'] ?? array() ),
		);

		return $info;
	}

	/**
	 * Debug helper: clear the cached manifest if the refresh query param is set.
	 */
	public static function maybe_refresh_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['geo_forge_refresh_update'] ) ) {
			return;
		}
		delete_transient( self::CACHE_KEY );
		PluginLogger::info( 'Updater cache cleared via refresh param.' );
	}

	/**
	 * Fetch the manifest, using the transient cache when possible.
	 *
	 * @return array<string,mixed>|null Parsed JSON, or null on failure.
	 */
	private static function fetch_manifest(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url = self::cache_bust_url();

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'GEO-Forge-Updater/' . GEO_FORGE_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			PluginLogger::warning(
				'Updater fetch failed.',
				array( 'url' => $url, 'error' => $response->get_error_message() )
			);
			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			PluginLogger::warning(
				'Updater returned non-200.',
				array( 'url' => $url, 'status' => $status )
			);
			return null;
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			PluginLogger::warning(
				'Updater returned invalid JSON.',
				array( 'url' => $url )
			);
			return null;
		}

		set_transient( self::CACHE_KEY, $decoded, self::CACHE_TTL );
		return $decoded;
	}

	/**
	 * Resolved manifest URL. Default is the constant; filters let site owners
	 * point at a staging or self-hosted update server.
	 */
	private static function manifest_url(): string {
		/**
		 * Filter the auto-updater manifest URL.
		 *
		 * @param string $url Full URL to update.json.
		 */
		return (string) apply_filters( 'geo_forge_update_url', self::DEFAULT_MANIFEST_URL );
	}

	/**
	 * Build a cache-busting URL for the manifest. WordPress's update check
	 * runs at most every 12 hours; we append the current hour so the CDN
	 * cache is only valid for at most 1 hour (or whatever the CDN's TTL is).
	 * This prevents stale update.json from being served across releases.
	 */
	private static function cache_bust_url(): string {
		$url  = self::manifest_url();
		$hour = gmdate( 'YmdH' ); // e.g. 2026071508
		return add_query_arg( '_t', $hour, $url );
	}
}
