<?php
/**
 * Plugin uninstall handler.
 *
 * WordPress calls this when the user clicks "Delete" on the Plugins screen.
 *
 * GEO Forge policy: we do NOT drop tables or delete configuration on uninstall.
 * Why? Store owners frequently deactivate/reactivate plugins for debugging.
 * Losing scan history, API keys, fixes, and traffic data on an accidental
 * uninstall is unacceptable.
 *
 * Only clean up:
 *   - Transients (will be re-created on next activation)
 *   - Rewrite rules (will be re-registered on next activation)
 *   - Cron hooks (will be re-scheduled on next activation)
 *
 * Full data wipe (tables + options) is available via the "Delete All Data"
 * button in Settings, or by calling WP-CLI:
 *   wp geo-forge purge --all
 *
 * @package GEO_Forge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// --- Transients (safe to delete — auto-recreated) ---
delete_transient( 'geo_forge_last_scan' );
delete_transient( 'geo_forge_account_info' );
delete_transient( 'geo_forge_remote_update' );
delete_transient( 'geo_forge_settings_notice' );

// --- Clean up WP's rewrite rules ---
delete_option( 'geo_forge_routes_version' );
flush_rewrite_rules();

// --- Clear scheduled hooks ---
wp_clear_scheduled_hook( 'geo_forge_daily_scan' );
wp_clear_scheduled_hook( 'geo_forge_weekly_report' );

// --- All other data stays ---
// Custom tables (geo_forge_scans, geo_forge_fixes, geo_forge_logs, geo_forge_traffic)
// are preserved. WordPress options (geo_forge_*) are preserved.
// Re-installing will pick up everything exactly as it was.
