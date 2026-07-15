<?php
/**
 * llms.txt editor view.
 *
 * Simple textarea + Save + Regenerate buttons. Shows current stored content.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GEO_Forge\WellKnown\LlmsTxt;

$content  = LlmsTxt::get_current();
$live_url = home_url( '/llms.txt' );

?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<h1>
			<?php esc_html_e( 'GEO Forge', 'geo-forge' ); ?>
			<span class="geo-forge-subtitle"><?php esc_html_e( 'llms.txt Editor', 'geo-forge' ); ?></span>
		</h1>
		<p class="geo-forge-muted">
			<?php
			printf(
				/* translators: %s: public URL */
				esc_html__( 'AI agents fetch this file from %s. Your edits are served immediately after saving.', 'geo-forge' ),
				'<code><a href="' . esc_url( $live_url ) . '" target="_blank" rel="noopener">' . esc_html( $live_url ) . '</a></code>'
			);
			?>
		</p>
	</div>

	<div id="geo-forge-editor-status" class="geo-forge-notice" style="display:none;"><p></p></div>

	<div class="geo-forge-card">
		<div class="geo-forge-editor-actions">
			<button type="button" id="geo-forge-save-llms" class="pure-button pure-button-primary">
				<?php esc_html_e( 'Save', 'geo-forge' ); ?>
			</button>
			<button type="button" id="geo-forge-regen-llms" class="pure-button">
				<?php esc_html_e( 'Regenerate from store data', 'geo-forge' ); ?>
			</button>
			<span class="geo-forge-muted">
				<?php
				printf(
					/* translators: %d: byte count */
					esc_html__( '%d bytes', 'geo-forge' ),
					strlen( $content )
				);
				?>
			</span>
		</div>

		<textarea
			id="geo-forge-llms-content"
			class="geo-forge-editor"
			rows="28"
			spellcheck="false"
		><?php echo esc_textarea( $content ); ?></textarea>
	</div>
</div>
