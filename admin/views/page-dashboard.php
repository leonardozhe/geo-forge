<?php
/**
 * Dashboard view.
 *
 * Renders inside WP Admin. Receives `$admin` (Admin instance) from the caller.
 * Keep business logic out of this file — data preparation belongs in Admin.php.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap geo-forge-wrap">
	<h1><?php esc_html_e( 'GEO Forge — Dashboard', 'geo-forge' ); ?></h1>

	<div class="geo-forge-card">
		<h2><?php esc_html_e( 'Welcome', 'geo-forge' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s: plugin version */
				esc_html__( 'GEO Forge %s is active. Configure your GEO KAMI API key to start scanning.', 'geo-forge' ),
				'<code>' . esc_html( GEO_FORGE_VERSION ) . '</code>'
			);
			?>
		</p>
	</div>

	<div class="geo-forge-grid">
		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'AI Score', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat">—</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'No scan yet. Run your first scan from the Scan button below.', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Issues Found', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat">—</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'Will appear after your first scan.', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Fixable Auto', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat">—</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'Issues this plugin can auto-fix.', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Grade', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat">—</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'GEO KAMI letter grade.', 'geo-forge' ); ?></p>
		</div>
	</div>

	<div class="geo-forge-card">
		<h3><?php esc_html_e( 'Next Steps', 'geo-forge' ); ?></h3>
		<ol>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ); ?>">
					<?php esc_html_e( 'Enter your GEO KAMI API key', 'geo-forge' ); ?>
				</a>
			</li>
			<li><?php esc_html_e( 'Return here and click "Scan Now" (enabled in Milestone 1).', 'geo-forge' ); ?></li>
			<li><?php esc_html_e( 'Visit the Fix Center to auto-deploy improvements.', 'geo-forge' ); ?></li>
		</ol>
	</div>
</div>
