<?php
/**
 * Plugin uninstall handler.
 *
 * WordPress calls this when the user clicks "Delete" on the Plugins screen.
 *
 * WordPress.org requirement: all plugin data must be removed on uninstall.
 * This includes options, custom tables, transients, and user meta.
 *
 * @package GEO_Forge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// --- Custom tables ---
$geo_forge_tables = array(
	$wpdb->prefix . 'geo_forge_scans',
	$wpdb->prefix . 'geo_forge_fixes',
	$wpdb->prefix . 'geo_forge_logs',
	$wpdb->prefix . 'geo_forge_traffic',
	$wpdb->prefix . 'geo_forge_settings',
);
foreach ( $geo_forge_tables as $geo_forge_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$geo_forge_table}`" );
}

// --- All plugin options (pattern: geo_forge_*) ---
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$geo_forge_option_keys = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'geo\_forge\_%'"
);
foreach ( $geo_forge_option_keys as $geo_forge_key ) {
	delete_option( $geo_forge_key );
}

// --- Transients (pattern: _transient_geo_forge_* and _transient_timeout_geo_forge_*) ---
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$geo_forge_transient_keys = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_geo\_forge\_%' OR option_name LIKE '\_transient\_timeout\_geo\_forge\_%'"
);
foreach ( $geo_forge_transient_keys as $geo_forge_key ) {
	delete_option( $geo_forge_key );
}

// --- Clean up WP's rewrite rules ---
flush_rewrite_rules();

// --- Clear scheduled hooks ---
wp_clear_scheduled_hook( 'geo_forge_daily_scan' );
wp_clear_scheduled_hook( 'geo_forge_weekly_report' );
