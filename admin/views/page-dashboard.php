<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
use GEO_Forge\GeoForge;
use GEO_Forge\Install\Installer;

$sc  = new \GEO_Forge\Scanner\Scanner();
$fx  = GeoForge::fixer();
$lk  = $sc->get_last_scan();
$lt  = (string) get_option( 'geo_forge_last_scan_time', '' );
$hk  = (bool) Installer::get_setting( 'api_key', '' );
$sc0 = $lk['total_score'] ?? null;

$gr_label = function ( int $s ): string {
	if ( $s >= 90 ) { return 'S'; } if ( $s >= 75 ) { return 'A'; }
	if ( $s >= 50 ) { return 'B'; } if ( $s >= 25 ) { return 'C'; } return 'D';
};
$gr_color = function ( int $s ): string {
	if ( $s >= 90 ) { return '#7c3aed'; } if ( $s >= 75 ) { return '#16a34a'; }
	if ( $s >= 50 ) { return '#2563eb'; } if ( $s >= 25 ) { return '#ca8a04'; } return '#dc2626';
};
$gr  = null !== $sc0 ? $gr_label( $sc0 ) : '—';
$grc = null !== $sc0 ? $gr_color( $sc0 ) : '#94a3b8';
$ca  = is_array( $lk['category_scores'] ?? null ) ? $lk['category_scores'] : array();
$ck  = is_array( $lk['checks_result'] ?? null ) ? $lk['checks_result'] : array();
$ps  = count( array_filter( $ck, fn( $c ) => ( $c['status'] ?? '' ) === 'pass' ) );
$fl  = count( $ck ) - $ps;

global $wpdb;
$ht = $wpdb->get_results( "SELECT id, total_score, grade, grade_label, created_at FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30", ARRAY_A ) ?: array();

$cat_names = array(
	'ai-readability' => 'AI Readability', 'discoverability' => 'Discoverability',
	'accessibility' => 'Content Accessibility', 'bot-control' => 'Bot Access Control',
	'security' => 'Security & UX', 'protocol' => 'Protocol Discovery', 'commerce' => 'Commerce',
);
$check_models = array(
	'robots_txt'      => 'GPTBot, ClaudeBot, PerplexityBot',
	'llms_txt_exists' => 'ChatGPT, Claude, Perplexity',
	'llms_txt_quality'=> 'ChatGPT, Claude, Perplexity',
	'security_txt'    => 'Security researchers',
	'mcp_server_card' => 'Claude Desktop, MCP clients',
	'a2a_agent_card'  => 'Google A2A agents',
	'json_ld'         => 'Google AI, ChatGPT',
	'review_rating'   => 'Google Shopping, Perplexity',
	'basic_security'  => 'All web crawlers',
	'hsts_config'     => 'All HTTPS clients',
	'canonical'       => 'Google, search engines',
	'sitemap_xml'     => 'Google, Bing',
	'meta_tags'       => 'Google AI, ChatGPT',
	'ai_bot_rules'    => 'GPTBot, ClaudeBot',
);
?>
<div class="geo-forge-wrap">
<div class="gf-header"><div style="display:flex;align-items:center;justify-content:space-between;"><div><h1>Dashboard <span class="gf-subtitle">GEO Forge</span></h1><?php if ( $lt ) : ?><span class="gf-muted">Last scan: <?php echo esc_html( $lt ); ?></span><?php endif; ?></div><div id="gf-account-info" style="display:flex;align-items:center;gap:8px;"><?php if ( $hk ) : ?><span class="gf-badge gf-badge-green">🔗 Connected</span><?php else : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=geo-forge-settings' ) ); ?>" class="gf-btn">Add API Key</a><?php endif; ?></div></div></div>

<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><i data-lucide="bar-chart-2" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> AI Score</div>
		<div class="gf-stat"><?php echo null === $sc0 ? '—' : esc_html( $sc0 ); ?><span style="font-size:14px;color:#94a3b8;">/100</span></div>
	</div>
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><i data-lucide="check-circle-2" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> Status</div>
		<div class="gf-stat"><?php echo null === $sc0 ? '—' : "<span style='color:#16a34a'>$ps</span> <span style='font-size:14px;color:#94a3b8;font-weight:400;'>pass</span> · <span style='color:#dc2626'>$fl</span> <span style='font-size:14px;color:#94a3b8;font-weight:400;'>fail</span>"; ?></div>
	</div>
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><i data-lucide="award" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> Grade</div>
		<div class="gf-stat" style="color:<?php echo $grc; ?>;font-size:48px;font-weight:800;"><?php echo $gr; ?></div>
	</div>
</div>

<?php if ( $sc0 ) : ?>
<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-card-title">Category Breakdown <span class="gf-badge gf-badge-blue"><?php echo count( $ca ); ?></span></div>
		<table><?php foreach ( $ca as $c ) : $e = (int) ( $c['earned'] ?? 0 ); $m = max( 1, (int) ( $c['max'] ?? 1 ) ); $p = round( $e / $m * 100 ); $cl = $p >= 80 ? '#16a34a' : ( $p >= 50 ? '#ca8a04' : '#dc2626' ); $nm = $cat_names[ $c['id'] ] ?? ucfirst( (string) ( $c['id'] ?? '' ) ); ?>
		<tr><td style="font-weight:500;font-size:12px;"><?php echo esc_html( $nm ); ?></td><td><div class="gf-bar"><div class="gf-bar-fill" style="width:<?php echo $p; ?>%;background:<?php echo $cl; ?>;"></div></div></td><td style="width:36px;text-align:right;font-weight:600;font-size:12px;color:<?php echo $cl; ?>;"><?php echo $p; ?>%</td></tr>
		<?php endforeach; ?></table>
	</div>
	<div style="grid-column: span 2;"><div class="gf-card" style="padding:0;"><div class="gf-card-title" style="padding:20px 20px 0 20px;">Check Results <span class="gf-badge gf-badge-blue"><?php echo count( $ck ); ?></span></div>

		<!-- Fixed header -->
		<table style="min-width:720px;"><thead><tr>
			<th style="width:5%;"></th>
			<th style="width:28%;">Check</th>
			<th style="width:12%;">Category</th>
			<th style="width:20%;">AI Models</th>
			<th style="width:35%;">Goal</th>
		</tr></thead></table>

		<!-- Scrollable body -->
		<div style="max-height:340px;overflow-y:auto;padding-right:4px;">
		<table style="min-width:720px;">
		<colgroup><col style="width:5%;"><col style="width:28%;"><col style="width:12%;"><col style="width:20%;"><col style="width:35%;"></colgroup>
		<tbody>
		<?php foreach ( $ck as $ch ) : $st = $ch['status'] ?? 'fail'; $label = $ch['label'] ?? $ch['name'] ?? $ch['id'] ?? '?'; $chid = $ch['id'] ?? ''; $chcat = $ch['category'] ?? ''; $cat_label = $cat_names[ $chcat ] ?? $chcat; $ai_models = $check_models[ $chid ] ?? '—'; $score_raw = $ch['score'] ?? 0; $max_raw = $ch['maxScore'] ?? 0; $goal = $ch['goal'] ?? ''; $res = $ch['result'] ?? ''; $rec = $ch['recommendation'] ?? ''; ?>
		<tr>
			<td style="width:5%;text-align:center;">
				<?php if ( $st === 'pass' ) : ?><i data-lucide="circle-check" style="width:16px;height:16px;color:#16a34a;"></i>
				<?php elseif ( $st === 'warn' ) : ?><i data-lucide="triangle-alert" style="width:16px;height:16px;color:#ca8a04;"></i>
				<?php else : ?><i data-lucide="circle-x" style="width:16px;height:16px;color:#dc2626;"></i><?php endif; ?>
			</td>
			<td style="width:28%;">
				<div style="font-weight:600;font-size:12px;"><?php echo esc_html( $label ); ?></div>
				<?php if ( $res ) : ?><div style="font-size:10px;color:#94a3b8;margin-top:1px;"><?php echo esc_html( $res ); ?></div><?php endif; ?>
			</td>
			<td style="width:12%;"><span style="font-size:11px;color:#64748b;"><?php echo esc_html( $cat_label ); ?></span></td>
			<td style="width:20%;"><span style="font-size:10px;color:#94a3b8;"><?php echo esc_html( $ai_models ); ?></span></td>
			<td style="width:35%;">
				<?php if ( $goal ) : ?><div style="font-size:11px;color:#475569;margin-bottom:3px;"><?php echo esc_html( $goal ); ?></div><?php endif; ?>
				<?php if ( $rec ) : ?><div style="font-size:10px;color:#dc2626;margin-bottom:2px;">💡 <?php echo esc_html( $rec ); ?></div><?php endif; ?>
				<div style="font-size:12px;font-weight:600;">
					<span style="color:<?php echo $st === 'pass' ? '#16a34a' : '#dc2626'; ?>;"><?php echo (int) $score_raw; ?>/<?php echo (int) $max_raw; ?></span>
					<span style="font-size:10px;font-weight:400;color:#94a3b8;margin-left:4px;">—
					<?php echo $st === 'pass' ? 'Passed' : 'Failed'; ?></span>
				</div>
			</td>
		</tr>
		<?php endforeach; ?></tbody></table></div>
	</div></div>
</div>
<?php endif; ?>

<?php if ( count( $ht ) >= 2 ) : ?>
<div class="gf-card"><div class="gf-card-title">Score History <span class="gf-badge gf-badge-blue"><?php echo count( $ht ); ?></span></div>
<table><thead><tr><th>Time</th><th>Score</th><th>Grade</th><th>Change</th><th></th></tr></thead><tbody>
<?php foreach ( $ht as $i => $t ) : $pv = $i < count( $ht ) - 1 ? $ht[ $i + 1 ]['total_score'] : $t['total_score']; $up = $t['total_score'] > $pv ? '▲' : ( $t['total_score'] < $pv ? '▼' : '—' ); $cl = $t['total_score'] >= 80 ? '#16a34a' : ( $t['total_score'] >= 50 ? '#ca8a04' : '#dc2626' ); $lg = $gr_label( $t['total_score'] ); ?>
<tr><td style="font-size:12px;"><?php echo esc_html( substr( $t['created_at'], 0, 16 ) ); ?></td><td style="font-weight:700;color:<?php echo $cl; ?>;"><?php echo $t['total_score']; ?></td><td><span class="gf-badge" style="color:#fff;background:<?php echo $gr_color( $t['total_score'] ); ?>;"><?php echo $lg; ?></span></td><td style="font-size:12px;color:<?php echo $up === '▲' ? '#16a34a' : ( $up === '▼' ? '#dc2626' : '#94a3b8' ); ?>;"><?php echo $up; ?> <?php echo abs( $t['total_score'] - $pv ); ?></td><td><button class="gf-btn gf-view-detail" style="font-size:11px;padding:3px 8px;">View Details</button></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:10px;margin:16px 0;">
	<button type="button" id="geo-forge-scan-btn" class="gf-btn gf-btn-primary" <?php disabled( ! $hk ); ?>>Scan Now</button>
	<span id="geo-forge-scan-status" style="font-size:12px;"></span>
</div>
<div id="geo-forge-error" class="gf-notice gf-notice-error" style="display:none;"></div>

<?php if ( ! $hk ) : ?>
<div class="gf-card gf-promo"><h2>🚀 Get Started with GEO KAMI</h2><p>Free tier: 100 points (5 scans). No credit card required.</p>
<div class="gf-grid gf-grid-3" style="margin:12px 0;"><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🎁</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">100 points</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔍</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">5 scans</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔧</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">Auto-fix</div></div></div>
<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="gf-btn gf-btn-primary" style="background:#fff!important;color:#4338ca!important;border-color:#fff!important;">Get Free API Key</a></div>
<?php endif; ?>

<div class="gf-detail-overlay" id="gf-detail-dialog"><div class="gf-detail-dialog"><button class="gf-detail-close" onclick="document.getElementById('gf-detail-dialog').classList.remove('open')">&times;</button><div id="gf-detail-content"></div></div></div>
</div>
<script>lucide.createIcons();</script>
