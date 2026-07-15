<?php
/**
 * Content Signals meta tags generator.
 *
 * Adds AI-readable meta tags to the <head> of every page:
 *   - geo_forge:ai_ready: true
 *   - geo_forge:scan_version: 1.0
 *   - geo_forge:last_scan: {timestamp}
 *
 * Stored in `geo_forge_content_signals_enabled` option.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentSignals {

	private const OPTION = 'geo_forge_content_signals_enabled';

	/**
	 * Register the wp_head hook.
	 */
	public static function register(): void {
		add_action( 'wp_head', array( self::class, 'inject_meta_tags' ) );
	}

	/**
	 * Inject meta tags into <head>.
	 */
	public static function inject_meta_tags(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$last_scan = (string) get_option( 'geo_forge_last_scan_time', '' );

		echo '<meta name="geo_forge:ai_ready" content="true" />' . "\n";
		echo '<meta name="geo_forge:scan_version" content="1.0" />' . "\n";

		if ( ! empty( $last_scan ) ) {
			echo '<meta name="geo_forge:last_scan" content="' . esc_attr( $last_scan ) . '" />' . "\n";
		}
	}

	/**
	 * Check if content signals are enabled.
	 */
	public static function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION, 'no' );
	}

	/**
	 * Enable content signals.
	 */
	public static function enable(): void {
		update_option( self::OPTION, 'yes' );
	}

	/**
	 * Disable content signals (rollback).
	 */
	public static function disable(): void {
		delete_option( self::OPTION );
	}
}
