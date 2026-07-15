<?php
/**
 * Dashboard view.
 *
 * Renders the main overview: AI Score, Issues, Fixable count, Grade,
 * plus a "Scan Now" button. The button triggers `dashboard.js` to POST
 * to the REST endpoint and update the tiles in place.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scanner  = new \GEO_Forge\Scanner\Scanner();
$last     = $scanner->get_last_scan();
$last_time = (string) get_option( 'geo_forge_last_scan_time', '' );
$has_key   = (bool) get_option( 'geo_forge_api_key', '' );

$score = $last['total_score'] ?? null;
$grade_label = $last['grade_label'] ?? '';
$grade_emoji = match ( true ) {
	null === $score            => '—',
	$score >= 80              => '🟢',
	$score >= 50              => '🟡',
	$score >= 25              => '🟠',
	default                   => '🔴',
};

$categories    = is_array( $last['category_scores'] ?? null ) ? $last['category_scores'] : array();
$checks        = is_array( $last['checks_result'] ?? null ) ? $last['checks_result'] : array();
$suggestions   = is_array( $last['suggestions'] ?? null ) ? $last['suggestions'] : array();
$issue_count   = count( array_filter( $checks, fn( $c ) => ( $c['status'] ?? '' ) !== 'pass' ) );
$fixable_count = 0; // Populated after Fixer lands in Milestone 3.

?>
<div class="wrap geo-forge-wrap">
	<h1>
		<?php esc_html_e( 'GEO Forge — Dashboard', 'geo-forge' ); ?>
		<?php if ( $has_key ) : ?>
			<span class="geo-forge-api-ok" title="<?php esc_attr_e( 'API key configured', 'geo-forge' ); ?>">🔗</span>
		<?php else : ?>
			<a class="geo-forge-api-missing" href="<?php echo esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ); ?>">
				<?php esc_html_e( '⚠ API key missing', 'geo-forge' ); ?>
			</a>
		<?php endif; ?>
	</h1>

	<?php if ( $last_time ) : ?>
		<p class="geo-forge-muted">
			<?php
			printf(
				/* translators: %s: last scan time */
				esc_html__( 'Last scan: %s', 'geo-forge' ),
				'<time>' . esc_html( $last_time ) . '</time>'
			);
			?>
		</p>
	<?php endif; ?>

	<div class="geo-forge-grid">
		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'AI Score', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat" data-stat="score">
				<?php echo null === $score ? '—' : esc_html( $grade_emoji . ' ' . $score ); ?>
			</p>
			<p class="geo-forge-muted">/ 100</p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Issues Found', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat" data-stat="issues">
				<?php echo null === $score ? '—' : esc_html( $issue_count ); ?>
			</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'checks that did not pass', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Fixable Auto', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat" data-stat="fixable"><?php echo esc_html( $fixable_count ?: '—' ); ?></p>
			<p class="geo-forge-muted"><?php esc_html_e( 'available in Milestone 3', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Grade', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat" data-stat="grade">
				<?php echo esc_html( $grade_label ?: '—' ); ?>
			</p>
			<p class="geo-forge-muted"><?php esc_html_e( 'GEO KAMI letter grade', 'geo-forge' ); ?></p>
		</div>
	</div>

	<div class="geo-forge-card">
		<h3><?php esc_html_e( 'Category Breakdown', 'geo-forge' ); ?></h3>
		<?php if ( empty( $categories ) ) : ?>
			<p class="geo-forge-muted"><?php esc_html_e( 'Run a scan to see category scores.', 'geo-forge' ); ?></p>
		<?php else : ?>
			<table class="widefat striped geo-forge-category-table">
				<tbody>
					<?php foreach ( $categories as $cat ) :
						$earned = (int) ( $cat['earned'] ?? 0 );
						$max    = (int) ( $cat['max'] ?? 1 );
						$pct    = (int) round( ( $earned / max( 1, $max ) ) * 100 );
						$color  = $pct >= 80 ? '#00a32a' : ( $pct >= 50 ? '#dba600' : '#d63638' );
					?>
						<tr>
							<td style="width:40%;"><?php echo esc_html( ucfirst( (string) ( $cat['id'] ?? '' ) ) ); ?></td>
							<td>
								<div class="geo-forge-bar">
									<div class="geo-forge-bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%; background: <?php echo esc_attr( $color ); ?>;"></div>
								</div>
							</td>
							<td style="width:15%; text-align:right;">
								<?php echo esc_html( $pct . '%' ); ?>
								<span class="geo-forge-muted">(<?php echo esc_html( $earned . '/' . $max ); ?>)</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="geo-forge-card geo-forge-actions">
		<button type="button" id="geo-forge-scan-btn" class="button button-primary" <?php disabled( ! $has_key ); ?>>
			<?php esc_html_e( 'Scan Now', 'geo-forge' ); ?>
		</button>
		<span id="geo-forge-scan-status" class="geo-forge-muted" aria-live="polite"></span>
		<?php if ( ! $has_key ) : ?>
			<span class="geo-forge-muted">
				<?php
				printf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ),
					esc_html__( 'Add your API key first.', 'geo-forge' )
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<div id="geo-forge-error" class="notice notice-error" style="display:none;">
		<p></p>
	</div>
</div>
