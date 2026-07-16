<?php
/**
 * Plugin activation / deactivation handler.
 *
 * - activate:   create custom tables, seed default options, schedule cron stubs.
 * - deactivate: clear scheduled cron events; tables/options kept (non-destructive).
 *
 * Destructive cleanup (drop tables, delete options) lives in `uninstall.php`.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Install;

use GEO_Forge\GeoForge;
use GEO_Forge\WellKnown\Router;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	/**
	 * Runs on plugin activation.
	 *
	 * Safe to call multiple times — `dbDelta()` is idempotent for unchanged schemas,
	 * and default options are only written when absent.
	 */
	public static function activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		self::create_tables();
		self::seed_defaults();
		self::migrate_settings_to_table();

		// Rebuild WordPress's rewrite cache so our virtual routes work immediately.
		Router::flush_rules();
	}

	/**
	 * Runs on plugin deactivation.
	 * Intentionally light — we do NOT drop tables or delete options here,
	 * so re-activating is cheap and user settings persist across disable/enable.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'geo_forge_daily_scan' );
		wp_clear_scheduled_hook( 'geo_forge_weekly_report' );
		Router::flush_rules();
	}

	/**
	 * Create (or upgrade) the plugin's custom tables.
	 */
	private static function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$scans_table = "CREATE TABLE {$wpdb->prefix}geo_forge_scans (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    scan_id varchar(36) NOT NULL,
    total_score int NOT NULL DEFAULT 0,
    grade varchar(10) NOT NULL,
    grade_label varchar(50) NOT NULL,
    category_scores longtext,
    checks_result longtext,
    suggestions longtext,
    points_cost int NOT NULL DEFAULT 0,
    scan_duration_ms int DEFAULT NULL,
    completed_at datetime DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY scan_id (scan_id),
    KEY total_score (total_score),
    KEY created_at (created_at)
) {$charset_collate};";

		$fixes_table = "CREATE TABLE {$wpdb->prefix}geo_forge_fixes (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    fix_id varchar(50) NOT NULL,
    scan_id varchar(36) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    score_change int NOT NULL DEFAULT 0,
    snapshot longtext,
    error_message text,
    applied_at datetime DEFAULT NULL,
    verified_at datetime DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY fix_id (fix_id),
    KEY status (status)
) {$charset_collate};";

		$logs_table = "CREATE TABLE {$wpdb->prefix}geo_forge_logs (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    level varchar(10) NOT NULL,
    message varchar(500) NOT NULL,
    context longtext,
    source varchar(120) NOT NULL,
    request_id varchar(16) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY level (level),
    KEY created_at (created_at),
    KEY request_id (request_id)
) {$charset_collate};";

		$traffic_table = "CREATE TABLE {$wpdb->prefix}geo_forge_traffic (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bot_family varchar(40) NOT NULL,
    request_url varchar(500) NOT NULL,
    response_status smallint NOT NULL DEFAULT 200,
    remote_ip_hash varchar(64) NOT NULL,
    request_method varchar(10) NOT NULL DEFAULT 'GET',
    source varchar(20) NOT NULL,
    response_size int DEFAULT NULL,
    PRIMARY KEY  (id),
    KEY recorded_at (recorded_at),
    KEY bot_family (bot_family),
    KEY source (source)
) {$charset_collate};";

		$settings_table = "CREATE TABLE {$wpdb->prefix}geo_forge_settings (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    setting_key varchar(100) NOT NULL,
    setting_value longtext NOT NULL,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY setting_key (setting_key)
) {$charset_collate};";

		dbDelta( $scans_table );
		dbDelta( $fixes_table );
		dbDelta( $logs_table );
		dbDelta( $traffic_table );
		dbDelta( $settings_table );

		update_option( 'geo_forge_db_version', GEO_FORGE_VERSION );

		// Migrate settings from wp_options to custom table for persistence
		self::migrate_settings_to_table();
	}

	/**
	 * Migrate plugin settings from wp_options to the custom settings table.
	 * wp_options can be wiped on uninstall — our table persists.
	 */
	private static function migrate_settings_to_table(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'geo_forge_settings';

		$keys = array(
			'api_key', 'api_base', 'auto_scan_enabled', 'scan_frequency',
			'auto_fix_enabled', 'auto_fix_risk_level', 'notify_score_drop',
			'notify_threshold', 'log_min_level', 'log_retention_days',
			'traffic_sample_rate',
		);

		foreach ( $keys as $key ) {
			$option_name = 'geo_forge_' . $key;
			$value       = get_option( $option_name, null );

			if ( null !== $value && false !== $value ) {
				$wpdb->replace(
					$table,
					array(
						'setting_key'   => $key,
						'setting_value' => is_array( $value ) ? wp_json_encode( $value ) : (string) $value,
					),
					array( '%s', '%s' )
				);
				wp_cache_delete( 'geo_forge_setting_' . $key, 'geo-forge' );
			}
		}
	}

	public static function get_setting( string $key, mixed $default = null ): mixed {
		global $wpdb;

		$cache_key = 'geo_forge_setting_' . $key;
		$cached    = wp_cache_get( $cache_key, 'geo-forge' );
		if ( false !== $cached ) {
			if ( 'empty' === $cached ) {
				return get_option( 'geo_forge_' . $key, $default );
			}
			return is_array( $cached ) ? $cached : $cached;
		}

		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT setting_value FROM {$wpdb->prefix}geo_forge_settings WHERE setting_key = %s", $key )
		);

		if ( null === $value || false === $value ) {
			wp_cache_set( $cache_key, 'empty', 'geo-forge', 0 );
			return get_option( 'geo_forge_' . $key, $default );
		}

		$decoded = json_decode( $value, true );
		$result  = ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : $value;
		wp_cache_set( $cache_key, $result, 'geo-forge', 0 );
		return $result;
	}

	public static function set_setting( string $key, mixed $value ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'geo_forge_settings';

		$db_value = is_array( $value ) ? wp_json_encode( $value ) : (string) $value;

		$wpdb->replace(
			$table,
			array( 'setting_key' => $key, 'setting_value' => $db_value ),
			array( '%s', '%s' )
		);

		update_option( 'geo_forge_' . $key, is_array( $value ) ? $db_value : $value );
		wp_cache_delete( 'geo_forge_setting_' . $key, 'geo-forge' );
	}

	/**
	 * Seed default option values on first install.
	 */
	private static function seed_defaults(): void {
		$defaults = array(
			'geo_forge_api_base'            => 'https://api.geokami.com',
			'geo_forge_auto_scan_enabled'   => 'yes',
			'geo_forge_scan_frequency'      => 'daily',
			'geo_forge_auto_fix_enabled'    => 'no',
			'geo_forge_auto_fix_risk_level' => 'low',
			'geo_forge_notify_score_drop'   => 'yes',
			'geo_forge_notify_threshold'    => 50,
			'geo_forge_log_min_level'       => 'warning',
			'geo_forge_log_retention_days'  => 30,
			'geo_forge_traffic_sample_rate' => 10,
		);

		foreach ( $defaults as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $default_value );
			}
		}
	}
}
