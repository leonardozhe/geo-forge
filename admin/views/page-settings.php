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
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<h1>
			<?php esc_html_e( 'GEO Forge', 'geo-forge' ); ?>
			<span class="geo-forge-subtitle"><?php esc_html_e( 'Settings', 'geo-forge' ); ?></span>
		</h1>
	</div>

	<?php if ( ! get_option( 'geo_forge_api_key' ) ) : ?>
		<div class="geo-forge-card geo-forge-promo">
			<h3>🔑 <?php esc_html_e( 'Get Your GEO KAMI API Key', 'geo-forge' ); ?></h3>
			<p>
				<?php esc_html_e( 'GEO Forge requires a GEO KAMI API key to scan your store. Sign up for free and get 100 points (enough for 5 comprehensive scans).', 'geo-forge' ); ?>
			</p>
			<ol class="geo-forge-steps">
				<li>
					<a href="https://geokami.com/register?ref=geo-forge" target="_blank" rel="noopener">
						<?php esc_html_e( 'Create a free GEO KAMI account', 'geo-forge' ); ?>
					</a>
				</li>
				<li><?php esc_html_e( 'Verify your email', 'geo-forge' ); ?></li>
				<li><?php esc_html_e( 'Navigate to Dashboard → API Keys', 'geo-forge' ); ?></li>
				<li><?php esc_html_e( 'Copy your API key (starts with gk_)', 'geo-forge' ); ?></li>
				<li><?php esc_html_e( 'Paste it below and save', 'geo-forge' ); ?></li>
			</ol>
			<div class="geo-forge-promo-cta">
				<a href="https://geokami.com/register?ref=geo-forge" target="_blank" rel="noopener" class="pure-button pure-button-primary">
					<?php esc_html_e( 'Register for Free', 'geo-forge' ); ?>
				</a>
				<span class="geo-forge-muted">
					<?php esc_html_e( 'Free tier: 100 points • 5 scans • No credit card required', 'geo-forge' ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<?php settings_errors( 'geo_forge' ); ?>

	<div class="geo-forge-card">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pure-form pure-form-stacked">
			<?php wp_nonce_field( $form_action, $nonce_field ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>" />

			<fieldset>
				<div class="pure-control-group">
					<label for="geo_forge_api_key"><?php esc_html_e( 'GEO KAMI API Key', 'geo-forge' ); ?></label>
					<input
						type="password"
						id="geo_forge_api_key"
						name="geo_forge_api_key"
						value="<?php echo esc_attr( $api_key ); ?>"
						class="pure-input-1"
						autocomplete="off"
						spellcheck="false"
						placeholder="gk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
					/>
					<span class="pure-form-message">
						<?php esc_html_e( '35-character API key starting with `gk_`. Stored as plaintext in wp_options (access-controlled by WordPress).', 'geo-forge' ); ?>
					</span>
				</div>

				<div class="pure-control-group">
					<label for="geo_forge_api_base"><?php esc_html_e( 'API Base URL', 'geo-forge' ); ?></label>
					<input
						type="url"
						id="geo_forge_api_base"
						name="geo_forge_api_base"
						value="<?php echo esc_attr( $api_base ); ?>"
						class="pure-input-1"
					/>
					<span class="pure-form-message">
						<?php esc_html_e( 'Default: https://api.geokami.com. Only change this if you are using a self-hosted GEO KAMI instance.', 'geo-forge' ); ?>
					</span>
				</div>

				<div class="pure-controls">
					<button type="submit" class="pure-button pure-button-primary">
						<?php esc_html_e( 'Save Settings', 'geo-forge' ); ?>
					</button>
				</div>
			</fieldset>
		</form>
	</div>
</div>
