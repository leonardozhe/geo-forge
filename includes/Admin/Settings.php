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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const FORM_ACTION = 'geo_forge_save_settings';
	private const NONCE_FIELD = 'geo_forge_settings_nonce';
	private const REDIRECT_TO = 'geo-forge-settings';

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
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
			: 'https://geokami.com';

		// 4. Light validation on the key shape.
		if ( '' !== $api_key && ! preg_match( '/^gk_[A-Za-z0-9]{32,}$/', $api_key ) ) {
			self::redirect_with_notice( 'error', __( 'API key must start with `gk_` followed by at least 32 alphanumeric characters.', 'geo-forge' ) );
			return;
		}

		if ( '' !== $api_base && ! filter_var( $api_base, FILTER_VALIDATE_URL ) ) {
			self::redirect_with_notice( 'error', __( 'API base URL is not a valid URL.', 'geo-forge' ) );
			return;
		}

		// 5. Persist. Empty key is allowed (user is clearing it).
		update_option( 'geo_forge_api_key', $api_key );
		update_option( 'geo_forge_api_base', '' !== $api_base ? $api_base : 'https://geokami.com' );

		// 6. Done.
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
				'page'                    => self::REDIRECT_TO,
				'geo_forge_notice'        => 1,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
