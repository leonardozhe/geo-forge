<?php
/**
 * Settings view.
 *
 * Minimal v1: API key + API base URL. More sections added in later milestones.
 * Receives `$admin` (Admin instance) from the caller.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Form is registered here; handler is in Settings::register().
// Using admin-post.php so the form submits from wp-admin regardless of page slug.
$form_action = 'geo_forge_save_settings';
$nonce_field = 'geo_forge_settings_nonce';

$api_key  = (string) get_option( 'geo_forge_api_key', '' );
$api_base = (string) get_option( 'geo_forge_api_base', 'https://api.geokami.com' );

?>
<div class="wrap geo-forge-wrap">
	<h1><?php esc_html_e( 'GEO Forge — Settings', 'geo-forge' ); ?></h1>

	<?php settings_errors( 'geo_forge' ); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( $form_action, $nonce_field ); ?>
		<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>" />

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="geo_forge_api_key"><?php esc_html_e( 'GEO KAMI API Key', 'geo-forge' ); ?></label>
					</th>
					<td>
						<input
							type="password"
							id="geo_forge_api_key"
							name="geo_forge_api_key"
							value="<?php echo esc_attr( $api_key ); ?>"
							class="regular-text"
							autocomplete="off"
							spellcheck="false"
							placeholder="gk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
						/>
						<p class="description">
							<?php esc_html_e( '35-character API key starting with `gk_`. Stored as plaintext in wp_options (access-controlled by WordPress).', 'geo-forge' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="geo_forge_api_base"><?php esc_html_e( 'API Base URL', 'geo-forge' ); ?></label>
					</th>
					<td>
						<input
							type="url"
							id="geo_forge_api_base"
							name="geo_forge_api_base"
							value="<?php echo esc_attr( $api_base ); ?>"
							class="regular-text"
						/>
						<p class="description">
							<?php esc_html_e( 'Default: https://api.geokami.com. Only change this if you are using a self-hosted GEO KAMI instance.', 'geo-forge' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'geo-forge' ) ); ?>
	</form>
</div>
