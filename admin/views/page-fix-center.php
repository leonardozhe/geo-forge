<?php
/**
 * Fix Center view.
 *
 * @package GEO_Forge
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GEO_Forge\GeoForge;

$geo_forge_fx  = GeoForge::fixer();
$geo_forge_fs  = $geo_forge_fx ? $geo_forge_fx->list() : array();
$geo_forge_si  = static function ( $s ) {
	return match ( $s ) {
		'applied'     => '✅',
		'verified'    => '✅✅',
		'rolled_back' => '⏪',
		'failed'      => '❌',
		default       => '○',
	};
};
$geo_forge_rc  = static function ( $r ) {
	return match ( $r ) {
		'none'   => '#16a34a',
		'low'    => '#2563eb',
		'medium' => '#ca8a04',
		'high'   => '#dc2626',
		default  => '#94a3b8',
	};
};
$geo_forge_gp  = array();
foreach ( $geo_forge_fs as $geo_forge_f ) {
	$geo_forge_l = ( 1 === $geo_forge_f['priority'] && in_array( $geo_forge_f['risk_level'], array( 'none', 'low' ), true ) ) ? 'Quick Fixes' : ( $geo_forge_f['priority'] <= 2 ? 'Server Config' : 'Advanced' );
	$geo_forge_gp[ $geo_forge_l ][] = $geo_forge_f;
}
?>
<div class="geo-forge-wrap">
<div class="gf-header"><h1>Optimizations <span class="gf-subtitle">Fix now to improve your AI score</span></h1><p class="gf-muted">These optimizations can be applied immediately on your server. Each fix records a snapshot and can be rolled back.</p></div>
<div id="geo-forge-fix-status" class="gf-notice" style="display:none;"><p></p></div>

<?php if ( empty( $geo_forge_gp ) ) : ?>
<div class="gf-card"><p class="gf-muted">No optimization actions registered.</p></div>
<?php else : ?>
<?php foreach ( $geo_forge_gp as $geo_forge_gl => $geo_forge_it ) : ?>
<?php $geo_forge_is_p1 = str_contains( $geo_forge_gl, 'no risk' ); ?>
<div class="gf-card<?php echo $geo_forge_is_p1 ? ' gf-card-highlight' : ''; ?>"<?php echo $geo_forge_is_p1 ? ' style="border-color:#16a34a;border-left:3px solid #16a34a;"' : ''; ?>>
	<div class="gf-card-title"><?php echo esc_html( $geo_forge_gl ); ?> <span class="gf-badge gf-badge-blue"><?php echo count( $geo_forge_it ); ?></span></div>
	<?php if ( $geo_forge_is_p1 ) : ?><p class="gf-muted" style="margin-bottom:8px;color:#16a34a;">✅ Zero risk — can be applied immediately without affecting your store.</p><?php endif; ?>
	<table class="striped"><thead><tr><th>Fix</th><th>Risk</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody>
	<?php foreach ( $geo_forge_it as $geo_forge_fx2 ) : ?>
	<?php $geo_forge_id = esc_attr( $geo_forge_fx2['id'] ); ?>
	<?php $geo_forge_ap = in_array( $geo_forge_fx2['status'], array( 'applied', 'verified' ), true ); ?>
	<tr data-fix-id="<?php echo esc_attr( $geo_forge_fx2['id'] ); ?>">
		<td><strong style="font-size:13px;"><?php echo esc_html( $geo_forge_fx2['label'] ); ?></strong><br><span class="gf-muted"><?php echo esc_html( $geo_forge_fx2['description'] ); ?></span></td>
		<td><span style="color:<?php echo esc_attr( $geo_forge_rc( $geo_forge_fx2['risk_level'] ) ); ?>;font-weight:600;font-size:12px;"><?php echo esc_html( ucfirst( $geo_forge_fx2['risk_level'] ) ); ?></span></td>
		<td class="geo-forge-fix-status-cell"><?php echo esc_html( $geo_forge_si( $geo_forge_fx2['status'] ) . ' ' . ucfirst( $geo_forge_fx2['status'] ) ); ?></td>
		<td class="gf-muted"><?php echo esc_html( $geo_forge_fx2['applied_at'] ?? '—' ); ?></td>
		<td style="white-space:nowrap;">
			<button class="gf-btn gf-btn-primary geo-forge-fix-apply" data-fix="<?php echo esc_attr( $geo_forge_fx2['id'] ); ?>" <?php disabled( $geo_forge_ap ); ?>>Apply</button>
			<button class="gf-btn geo-forge-fix-verify" data-fix="<?php echo esc_attr( $geo_forge_fx2['id'] ); ?>" <?php disabled( ! $geo_forge_ap ); ?>>Verify</button>
			<button class="gf-btn geo-forge-fix-rollback" data-fix="<?php echo esc_attr( $geo_forge_fx2['id'] ); ?>" <?php disabled( ! $geo_forge_ap ); ?>>Undo</button>
		</td>
	</tr>
	<?php endforeach; ?></tbody></table>
</div>
<?php endforeach; ?>
<?php endif; ?></div>
