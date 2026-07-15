<?php
/**
 * Plugin uninstall handler.
 *
 * Fires when the user deletes the plugin from wp-admin.
 * Drops our custom tables, wipes our options, clears scheduled hooks.
 *
 * @package GEO_Forge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Custom tables. Prefix is already applied by WP install.
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}geo_forge_scans`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}geo_forge_fixes`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}geo_forge_logs`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}geo_forge_traffic`" );

// Options we own. Listed explicitly — `DELETE LIKE 'geo_forge_%'` is risky
// because future extensions may share the prefix but not be uninstalled now.
$options = array(
	'geo_forge_api_key',
	'geo_forge_api_base',
	'geo_forge_auto_scan_enabled',
	'geo_forge_scan_frequency',
	'geo_forge_auto_fix_enabled',
	'geo_forge_auto_fix_risk_level',
	'geo_forge_notify_score_drop',
	'geo_forge_notify_email',
	'geo_forge_notify_threshold',
	'geo_forge_log_min_level',
	'geo_forge_log_retention_days',
	'geo_forge_traffic_sample_rate',
	'geo_forge_last_scan_result',
	'geo_forge_last_scan_time',
	'geo_forge_account_info',
	'geo_forge_db_version',
);

foreach ( $options as $option_name ) {
	delete_option( $option_name );
}

// Transients we may have set (24h scan cache, 1h account cache).
delete_transient( 'geo_forge_last_scan' );
delete_transient( 'geo_forge_account_info' );

// Scheduled cron hooks.
wp_clear_scheduled_hook( 'geo_forge_daily_scan' );
wp_clear_scheduled_hook( 'geo_forge_weekly_report' );
