<?php
/**
 * Dashboard view — unified compact design.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$scanner  = new \GEO_Forge\Scanner\Scanner();
$last     = $scanner->get_last_scan();
$last_time = (string) get_option( 'geo_forge_last_scan_time', '' );
$has_key   = (bool) get_option( 'geo_forge_api_key', '' );
$score = $last['total_score'] ?? null;
$grade_label = $last['grade_label'] ?? '';
$grade_emoji = match ( true ) { null === $score => '—', $score >= 80 => '🟢', $score >= 50 => '🟡', $score >= 25 => '🟠', default => '🔴' };
$categories  = is_array( $last['category_scores'] ?? null ) ? $last['category_scores'] : array();
$checks      = is_array( $last['checks_result'] ?? null ) ? $last['checks_result'] : array();
$issue_count = count( array_filter( $checks, fn( $c ) => ( $c['status'] ?? '' ) !== 'pass' ) );
$fixable     = 5; // 5 P1 fix actions
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
		<div>
			<h1>GEO Forge <span class="geo-forge-subtitle">Dashboard</span></h1>
			<?php if ( $last_time ) : ?>
				<span class="geo-forge-muted">Last scan: <time><?php echo esc_html( $last_time ); ?></time></span>
			<?php endif; ?>
		</div>
		<div>
			<?php if ( $has_key ) : ?>
				<span class="geo-forge-badge geo-forge-badge-success">🔗 Connected</span>
			<?php else : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ); ?>" class="pure-button">Add API Key</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="pure-g geo-forge-stats">
		<div class="pure-u-1-4"><div class="geo-forge-card">
			<h3>AI Score</h3>
			<p class="geo-forge-stat" data-stat="score"><?php echo null === $score ? '—' : esc_html( $grade_emoji . ' ' . $score ); ?></p>
			<p class="geo-forge-muted">/ 100</p>
		</div></div>
		<div class="pure-u-1-4"><div class="geo-forge-card">
			<h3>Issues</h3>
			<p class="geo-forge-stat" data-stat="issues"><?php echo null === $score ? '—' : esc_html( $issue_count ); ?></p>
			<p class="geo-forge-muted">not passing</p>
		</div></div>
		<div class="pure-u-1-4"><div class="geo-forge-card">
			<h3>Fixable</h3>
			<p class="geo-forge-stat" data-stat="fixable"><?php echo esc_html( (string) $fixable ); ?></p>
			<p class="geo-forge-muted">auto-fixes</p>
		</div></div>
		<div class="pure-u-1-4"><div class="geo-forge-card">
			<h3>Grade</h3>
			<p class="geo-forge-stat" data-stat="grade"><?php echo esc_html( $grade_label ?: '—' ); ?></p>
			<p class="geo-forge-muted">GEO KAMI</p>
		</div></div>
	</div>

	<div class="geo-forge-card">
		<h2 style="margin:0 0 10px;">Category Breakdown</h2>
		<?php if ( empty( $categories ) ) : ?>
			<p class="geo-forge-muted">Run a scan to see category scores.</p>
		<?php else : ?>
			<table class="pure-table">
				<tbody>
					<?php foreach ( $categories as $cat ) : $earned=(int)($cat['earned']??0); $max=max(1,(int)($cat['max']??1)); $pct=round($earned/$max*100); $color=$pct>=80?'#16a34a':($pct>=50?'#ca8a04':'#dc2626'); ?>
					<tr><td style="width:35%;"><?php echo esc_html(ucfirst((string)($cat['id']??''))); ?></td><td><div class="geo-forge-bar"><div class="geo-forge-bar-fill" style="width:<?php echo $pct;?>%;background:<?php echo $color;?>;"></div></div></td><td style="width:12%;text-align:right;"><?php echo $pct;?>% <span class="geo-forge-muted">(<?php echo$earned.'/'.$max;?>)</span></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="geo-forge-actions">
		<button type="button" id="geo-forge-scan-btn" class="pure-button pure-button-primary" <?php disabled( ! $has_key ); ?>>Scan Now</button>
		<span id="geo-forge-scan-status" class="geo-forge-muted" aria-live="polite"></span>
	</div>
	<div id="geo-forge-error" class="geo-forge-notice geo-forge-notice-error" style="display:none;"><p></p></div>

	<?php if ( ! $has_key ) : ?>
	<div class="geo-forge-card geo-forge-promo" style="margin-top:14px;">
		<h3>🚀 Get Started with GEO KAMI</h3>
		<p>Connect your store to GEO KAMI API for AI agent optimization. Free tier: 100 points (5 scans).</p>
		<div class="pure-g" style="margin:12px 0;">
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>Free Tier</strong><p>100 points</p></div></div>
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>5 Scans</strong><p>Full AI audit</p></div></div>
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>Auto-Fix</strong><p>One-click</p></div></div>
		</div>
		<div class="geo-forge-promo-cta">
			<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="pure-button pure-button-primary">Get Free API Key</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ); ?>" class="pure-button">Enter Key</a>
		</div>
	</div>
	<?php endif; ?>
</div>
