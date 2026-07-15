<?php
/**
 * Settings view.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$form_action = 'geo_forge_save_settings';
$nonce_field = 'geo_forge_settings_nonce';
$api_key  = (string) get_option( 'geo_forge_api_key', '' );
$api_base = (string) get_option( 'geo_forge_api_base', 'https://api.geokami.com' );
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<h1>GEO Forge <span class="geo-forge-subtitle">Settings</span></h1>
	</div>

	<?php if ( ! get_option( 'geo_forge_api_key' ) ) : ?>
		<div class="geo-forge-card geo-forge-promo">
			<h3>🔑 Get Your GEO KAMI API Key</h3>
			<p>Sign up for free and get 100 points (enough for 5 comprehensive scans).</p>
			<ol class="geo-forge-steps">
				<li><a href="https://geokami.com/register?ref=geo-forge" target="_blank" rel="noopener">Create a free GEO KAMI account</a></li>
				<li>Verify your email</li>
				<li>Navigate to Dashboard → API Keys</li>
				<li>Copy your API key (starts with gk_)</li>
				<li>Paste it below and save</li>
			</ol>
			<div class="geo-forge-promo-cta">
				<a href="https://geokami.com/register?ref=geo-forge" target="_blank" rel="noopener" class="pure-button pure-button-primary">Register for Free</a>
				<span class="geo-forge-muted">Free tier: 100 points • 5 scans • No credit card required</span>
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
					<label for="geo_forge_api_key">GEO KAMI API Key</label>
					<div class="geo-forge-input-row">
						<input type="password" id="geo_forge_api_key" name="geo_forge_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="pure-input-1" autocomplete="off" spellcheck="false" placeholder="gk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
						<button type="button" id="geo-forge-health-btn" class="pure-button">Health Check</button>
						<span id="geo-forge-health-status" class="geo-forge-status" aria-live="polite"></span>
					</div>
					<span class="pure-form-message">35-character API key starting with gk_.</span>
				</div>

				<div class="pure-control-group">
					<label for="geo_forge_api_base">API Base URL</label>
					<input type="url" id="geo_forge_api_base" name="geo_forge_api_base" value="<?php echo esc_attr( $api_base ); ?>" class="pure-input-1" />
					<span class="pure-form-message">Default: https://api.geokami.com</span>
				</div>

				<div class="pure-controls">
					<button type="submit" class="pure-button pure-button-primary">Save Settings</button>
				</div>
			</fieldset>
		</form>
	</div>
</div>
