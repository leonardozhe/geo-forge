<?php
/**
 * Fix Center view.
 *
 * Lists every registered fix action with its status, risk, priority, and
 * Apply / Rollback / Verify buttons. JS drives the buttons via REST.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GEO_Forge\GeoForge;

$fixer = GeoForge::fixer();
$fixes = $fixer ? $fixer->list() : array();

/**
 * Bucket a fix into a priority label for display.
 *
 * Priority 1 = P1 Critical (auto, zero/low risk)
 * Priority 2 = P2 Warning (auto, medium risk or protocol-level)
 * Priority 3+ = P3 Optional / Future
 */
$priority_label = static function ( int $priority, string $risk ): string {
	if ( 1 === $priority && in_array( $risk, array( 'none', 'low' ), true ) ) {
		return 'P1 — Critical';
	}
	if ( $priority <= 2 ) {
		return 'P2 — Warning';
	}
	return 'P3 — Optional';
};

$grouped = array();
foreach ( $fixes as $fix ) {
	$label = $priority_label( $fix['priority'], $fix['risk_level'] );
	$grouped[ $label ][] = $fix;
}

$risk_color = static function ( string $risk ): string {
	return match ( $risk ) {
		'none'   => '#00a32a',
		'low'    => '#2271b1',
		'medium' => '#dba600',
		'high'   => '#d63638',
		default  => '#646970',
	};
};

$status_label = static function ( string $status ): string {
	return match ( $status ) {
		'applied'     => __( '✅ Applied', 'geo-forge' ),
		'verified'    => __( '✅✅ Verified', 'geo-forge' ),
		'rolled_back' => __( '⏪ Rolled back', 'geo-forge' ),
		'failed'      => __( '❌ Failed', 'geo-forge' ),
		default       => __( '○ Pending', 'geo-forge' ),
	};
};

?>
<div class="wrap geo-forge-wrap">
	<h1><?php esc_html_e( 'GEO Forge — Fix Center', 'geo-forge' ); ?></h1>

	<p class="geo-forge-muted">
		<?php esc_html_e( 'Each fix is reversible — snapshots are captured before every apply. Verify asks GEO KAMI to re-check the affected checks (costs points).', 'geo-forge' ); ?>
	</p>

	<div id="geo-forge-fix-status" class="notice" style="display:none;"><p></p></div>

	<?php if ( empty( $grouped ) ) : ?>
		<div class="geo-forge-card">
			<p class="geo-forge-muted"><?php esc_html_e( 'No fix actions registered.', 'geo-forge' ); ?></p>
		</div>
	<?php else : ?>
		<?php foreach ( $grouped as $group_label => $items ) : ?>
			<div class="geo-forge-card">
				<h3><?php echo esc_html( $group_label ); ?> <span class="geo-forge-muted">(<?php echo esc_html( (string) count( $items ) ); ?>)</span></h3>
				<table class="widefat striped geo-forge-fix-table">
					<thead>
						<tr>
							<th style="width:30%;"><?php esc_html_e( 'Fix', 'geo-forge' ); ?></th>
							<th style="width:10%;"><?php esc_html_e( 'Risk', 'geo-forge' ); ?></th>
							<th style="width:15%;"><?php esc_html_e( 'Status', 'geo-forge' ); ?></th>
							<th style="width:20%;"><?php esc_html_e( 'Applied', 'geo-forge' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'geo-forge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $fix ) :
							$fix_id = esc_attr( $fix['id'] );
							$is_applied = in_array( $fix['status'], array( 'applied', 'verified' ), true );
						?>
							<tr data-fix-id="<?php echo $fix_id; ?>">
								<td>
									<strong><?php echo esc_html( $fix['label'] ); ?></strong>
									<div class="geo-forge-muted"><?php echo esc_html( $fix['description'] ); ?></div>
								</td>
								<td>
									<span class="geo-forge-fix-risk" style="color: <?php echo esc_attr( $risk_color( $fix['risk_level'] ) ); ?>;">
										<?php echo esc_html( ucfirst( $fix['risk_level'] ) ); ?>
									</span>
								</td>
								<td class="geo-forge-fix-status-cell">
									<?php echo esc_html( $status_label( $fix['status'] ) ); ?>
								</td>
								<td>
									<?php echo $fix['applied_at'] ? esc_html( $fix['applied_at'] ) : '—'; ?>
								</td>
								<td>
									<button type="button" class="button button-primary geo-forge-fix-apply" data-fix="<?php echo $fix_id; ?>" <?php disabled( $is_applied ); ?>>
										<?php $is_applied ? esc_html_e( 'Re-apply', 'geo-forge' ) : esc_html_e( 'Apply', 'geo-forge' ); ?>
									</button>
									<button type="button" class="button button-secondary geo-forge-fix-verify" data-fix="<?php echo $fix_id; ?>" <?php disabled( ! $is_applied ); ?>>
										<?php esc_html_e( 'Verify', 'geo-forge' ); ?>
									</button>
									<button type="button" class="button button-secondary geo-forge-fix-rollback" data-fix="<?php echo $fix_id; ?>" <?php disabled( ! $is_applied ); ?>>
										<?php esc_html_e( 'Rollback', 'geo-forge' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
