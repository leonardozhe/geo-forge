<?php
/**
 * Settings form handler.
 *
 * Handles the POST from admin/views/page-settings.php via admin-post.php.
 * Validates nonce + capability, sanitizes, persists, redirects back with a notice.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Admin;

use GEO_Forge\Cron\Scheduler;
use GEO_Forge\Install\Installer;
use GEO_Forge\WellKnown\LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const FORM_ACTION = 'geo_forge_save_settings';
	private const NONCE_FIELD = 'geo_forge_settings_nonce';
	private const REDIRECT_TO = 'geo-forge-settings';

	private const SCAN_FREQUENCIES = array( 'daily', 'twicedaily', 'weekly' );

	/** Wire the handler in. Called from Admin::register(). */
	public static function register(): void {
		// admin-post_{action} fires for both logged-in users.
		add_action( 'admin_post_' . self::FORM_ACTION, array( self::class, 'handle_save' ) );
	}

	/**
	 * Process the settings form submission.
	 */
	public static function handle_save(): void {
		// 1. Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'geo-forge' ) );
		}

		// 2. Nonce check.
		check_admin_referer( self::FORM_ACTION, self::NONCE_FIELD );

		// 3. Sanitize.
		$api_key  = isset( $_POST['geo_forge_api_key'] )
			? sanitize_text_field( wp_unslash( $_POST['geo_forge_api_key'] ) )
			: '';
		$api_base = isset( $_POST['geo_forge_api_base'] )
			? esc_url_raw( wp_unslash( $_POST['geo_forge_api_base'] ) )
			: 'https://api.geokami.com';

		$auto_regen = isset( $_POST['geo_forge_auto_regen_llms'] ) ? 'yes' : 'no';
		$auto_scan  = isset( $_POST['geo_forge_auto_scan_enabled'] ) ? 'yes' : 'no';
		$content_signals = isset( $_POST['geo_forge_content_signals_enabled'] ) ? 'yes' : 'no';
		$markdown        = isset( $_POST['geo_forge_markdown_enabled'] ) ? 'yes' : 'no';

		// Extra llms.txt languages, e.g. "zh_CN,ja,fr". Keep only valid locales.
		$clean_languages = array();
		if ( isset( $_POST['geo_forge_llms_languages'] ) ) {
			foreach ( explode( ',', sanitize_text_field( wp_unslash( $_POST['geo_forge_llms_languages'] ) ) ) as $piece ) {
				$piece = trim( $piece );
				if ( preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $piece ) ) {
					$clean_languages[] = $piece;
				}
			}
		}
		$frequency  = isset( $_POST['geo_forge_scan_frequency'] )
			? sanitize_text_field( wp_unslash( $_POST['geo_forge_scan_frequency'] ) )
			: 'daily';
		if ( ! in_array( $frequency, self::SCAN_FREQUENCIES, true ) ) {
			$frequency = 'daily';
		}

		// 4. Light validation on the key shape.
		if ( '' !== $api_key && ! preg_match( '/^gk_[A-Za-z0-9]{32,}$/', $api_key ) ) {
			self::redirect_with_notice( 'error', __( 'API key must start with `gk_` followed by at least 32 alphanumeric characters.', 'geo-forge' ) );
			return;
		}

		if ( '' !== $api_base && ! filter_var( $api_base, FILTER_VALIDATE_URL ) ) {
			self::redirect_with_notice( 'error', __( 'API base URL is not a valid URL.', 'geo-forge' ) );
			return;
		}

		// 5. Persist. The API key is encrypted at rest; an empty field means
		//    "keep the existing key" — never overwrite with blank.
		if ( '' !== $api_key ) {
			Installer::set_setting( 'api_key', Installer::encrypt_secret( $api_key ) );
		}
		Installer::set_setting( 'api_base', '' !== $api_base ? $api_base : 'https://api.geokami.com' );
		Installer::set_setting( 'auto_regen_llms', $auto_regen );
		Installer::set_setting( 'auto_scan_enabled', $auto_scan );
		Installer::set_setting( 'content_signals_enabled', $content_signals );
		Installer::set_setting( 'markdown_enabled', $markdown );
		Installer::set_setting( 'scan_frequency', $frequency );
		Installer::set_setting( 'llms_languages', implode( ',', $clean_languages ) );

		// 6. Keep scheduled jobs in sync with the new settings.
		Scheduler::schedule();

		// 7. Generate any newly configured llms.txt language variants now.
		foreach ( $clean_languages as $locale ) {
			LlmsTxt::regenerate_lang( $locale );
		}

		// 8. Done.
		self::redirect_with_notice( 'updated', __( 'Settings saved.', 'geo-forge' ) );
	}

	/**
	 * Redirect back to the settings page with a transient notice.
	 */
	private static function redirect_with_notice( string $type, string $message ): void {
		set_transient( 'geo_forge_settings_notice', array(
			'type'    => $type,
			'message' => $message,
		), 30 );

		$url = add_query_arg(
			array(
				'page'             => self::REDIRECT_TO,
				'geo_forge_notice' => 1,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
