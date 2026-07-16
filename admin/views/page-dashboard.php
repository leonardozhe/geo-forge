<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

use GEO_Forge\GeoForge;
use GEO_Forge\Install\Installer;

$sc  = new \GEO_Forge\Scanner\Scanner();
$fx  = GeoForge::fixer();
$lk  = $sc->get_last_scan();
$lt  = (string) get_option( 'geo_forge_last_scan_time', '' );
$hk  = (bool) Installer::get_setting( 'api_key', '' );

// Fetch account info server-side (defensive - if this fails, page still renders)
$acct = null;
if ( $hk ) {
	try {
		$api  = new \GEO_Forge\Api\Client();
		$resp = $api->get_account();
		$acct = $resp['success'] ?? false ? $resp : null;
	} catch ( \Throwable $e ) {
		// Log error but don't break the page
		\GEO_Forge\Log\Logger::debug('Account fetch failed: ' . $e->getMessage());
		$acct = null;
	}
}
$plan  = $acct['plan'] ?? array();
$pts   = $acct['points'] ?? array();
$sub   = $acct['subscription'] ?? array();
$sc0   = $lk['total_score'] ?? null;

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
$ht = $wpdb->get_results( "SELECT id, total_score, grade, grade_label, created_at, checks_result FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30", ARRAY_A ) ?: array();

// Pre-decode JSON for embedding into the page (so View Details can work without REST).
$ht_embed = array();
foreach ( $ht as $row ) {
	$checks = array();
	if ( isset( $row['checks_result'] ) && is_string( $row['checks_result'] ) ) {
		$decoded = json_decode( $row['checks_result'], true );
		if ( is_array( $decoded ) ) {
			$checks = $decoded;
		}
	}
	$ht_embed[ (int) $row['id'] ] = array(
		'id'            => (int) $row['id'],
		'total_score'   => (int) $row['total_score'],
		'created_at'    => $row['created_at'],
		'checks_result' => $checks,
	);
}

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

<div class="gf-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
	<div>
		<h1>Dashboard <span class="gf-subtitle">GEO Forge</span></h1>
		<?php if($lt):?><span class="gf-muted">Last scan: <?php echo esc_html($lt);?></span><?php endif;?>
	</div>
	<div style="display:flex;align-items:center;gap:12px;">
		<?php if($hk&&$acct):?>
		<div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;">
			<span style="font-size:12px;font-weight:600;color:#4338ca;"><?php echo esc_html($plan['label']??$plan['tier']??'Free');?></span>
			<span style="color:#cbd5e1;">|</span>
			<span style="font-size:12px;font-weight:600;color:#1e293b;"><?php echo esc_html(number_format((int)($pts['balance']??0)));?></span>
			<span style="font-size:11px;color:#94a3b8;">pts</span>
			<?php $exp=$sub['currentPeriodEnd']??'';if($exp):?>
			<span style="color:#cbd5e1;">|</span>
			<span style="font-size:11px;color:#94a3b8;">Exp. <?php echo esc_html(substr($exp,0,10));?></span>
			<?php endif;?>
		</div>
		<button type="button" id="geo-forge-scan-btn" class="gf-btn gf-btn-primary" style="padding:8px 20px;font-size:14px;">Scan Now</button>
		<?php elseif($hk):?>
		<div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;">
			<span class="gf-badge gf-badge-green">🔗 Connected</span>
		</div>
		<button type="button" id="geo-forge-scan-btn" class="gf-btn gf-btn-primary" style="padding:8px 20px;font-size:14px;">Scan Now</button>
		<?php else:?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="gf-btn gf-btn-primary" style="padding:8px 20px;font-size:14px;">Add API Key</a>
		<?php endif;?>
		<span id="geo-forge-scan-status" style="font-size:12px;"></span>
	</div>
</div>

<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> AI Score</div>
		<div class="gf-stat"><?php echo null===$sc0?'—':esc_html($sc0);?><span style="font-size:14px;color:#94a3b8;">/100</span></div>
	</div>
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Status</div>
		<div class="gf-stat"><?php echo null===$sc0?'—':'<span style="color:#16a34a">' . esc_html( (string) $ps ) . '</span> <span style="font-size:14px;color:#94a3b8;font-weight:400;">pass</span> · <span style="color:#dc2626">' . esc_html( (string) $fl ) . '</span> <span style="font-size:14px;color:#94a3b8;font-weight:400;">fail</span>';?></div>
	</div>
	<div class="gf-card" style="padding:24px;">
		<div class="gf-stat-label"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg> Grade</div>
		<div class="gf-stat" style="color:<?php echo esc_attr( $grc ); ?>;font-size:28px;font-weight:800;"><?php echo esc_html( $gr ); ?></div>
	</div>
</div>

<?php if($sc0):?>
<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-card-title">Category Breakdown <span class="gf-badge gf-badge-blue"><?php echo esc_html( (string) count( $ca ) ); ?></span></div>
		<table><?php foreach($ca as $c):$e=(int)($c['earned']??0);$m=max(1,(int)($c['max']??1));$p=round($e/$m*100);$cl=$p>=80?'#16a34a':($p>=50?'#ca8a04':'#dc2626');$nm=$cat_names[$c['id']]??ucfirst((string)($c['id']??''));?>
		<tr><td style="font-weight:500;font-size:12px;"><?php echo esc_html($nm);?></td><td><div class="gf-bar"><div class="gf-bar-fill" style="width:<?php echo esc_attr( (string) $p );?>%;background:<?php echo esc_attr($cl);?>;"></div></div></td><td style="width:36px;text-align:right;font-weight:600;font-size:12px;color:<?php echo esc_attr($cl);?>;"><?php echo esc_html( (string) $p );?>%</td></tr>
		<?php endforeach;?></table>
	</div>
	<div style="grid-column:span 2;"><div class="gf-card" style="padding:0;"><div class="gf-card-title" style="padding:20px 20px 0 20px;">Check Results <span class="gf-badge gf-badge-blue"><?php echo esc_html( (string) count( $ck ) ); ?></span></div>

		<!-- Fixed header -->
		<div style="min-width:780px;">
		<table><thead><tr>
			<th style="width:8%;text-align:left;">Status</th>
			<th style="text-align:left;">Check</th>
			<th style="width:14%;text-align:left;">Category</th>
			<th style="width:22%;text-align:left;">AI Models</th>
		</tr></thead></table>
		</div>

		<!-- Scrollable body -->
		<div style="max-height:340px;overflow-y:auto;padding-right:4px;">
		<table style="min-width:780px;">
		<colgroup><col style="width:8%;"><col><col style="width:14%;"><col style="width:22%;"></colgroup>
		<tbody>
		<?php foreach($ck as $ch):$st=$ch['status']??'fail';$label=$ch['label']??$ch['name']??$ch['id']??'?';$chid=$ch['id']??'';$chcat=$ch['category']??'';$cat_label=$cat_names[$chcat]??$chcat;$ai_models=$check_models[$chid]??'—';$score_raw=$ch['score']??0;$max_raw=$ch['maxScore']??0;$goal=$ch['goal']??'';$res=$ch['result']??'';$rec=$ch['recommendation']??'';

		// Merge goal + result + recommendation into one line
		$desc_parts = array();
		if($goal)$desc_parts[]=$goal;
		if($res&&$st!=='pass')$desc_parts[]=$res;
		if($rec)$desc_parts[]=$rec;
		$desc = implode('. ', $desc_parts);
		?>
		<tr>
			<td style="width:8%;">
				<div style="display:flex;align-items:center;gap:4px;">
					<?php if($st==='pass'):?><span style="font-size:16px;color:#16a34a;">✓</span>
					<?php elseif($st==='warn'):?><span style="font-size:16px;color:#ca8a04;">⚠</span>
					<?php else:?><span style="font-size:16px;color:#dc2626;">✗</span><?php endif;?>
					<span style="font-size:10px;font-weight:600;color:<?php echo esc_attr( $st === 'pass' ? '#16a34a' : '#dc2626' ); ?>;"><?php echo esc_html( (string) (int) $score_raw ); ?>/<?php echo esc_html( (string) (int) $max_raw ); ?></span>
				</div>
			</td>
			<td>
				<div style="font-weight:600;font-size:12px;"><?php echo esc_html($label);?></div>
				<?php if($desc):?><div style="font-size:10px;color:#64748b;margin-top:2px;line-height:1.4;"><?php echo esc_html($desc);?></div><?php endif;?>
			</td>
			<td style="width:14%;"><span style="font-size:11px;color:#64748b;"><?php echo esc_html($cat_label);?></span></td>
			<td style="width:22%;"><span style="font-size:10px;color:#94a3b8;"><?php echo esc_html($ai_models);?></span></td>
		</tr>
		<?php endforeach;?></tbody></table></div>
	</div></div>
</div>
<?php endif;?>

<?php if(count($ht)>=2):?>
<div class="gf-card"><div class="gf-card-title">Score History <span class="gf-badge gf-badge-blue"><?php echo esc_html( (string) count( $ht ) ); ?></span></div>
<table><thead><tr><th>Time</th><th>Score</th><th>Grade</th><th>Change</th><th></th></tr></thead><tbody>
<?php foreach($ht as $i=>$t):$pv=$i<count($ht)-1?$ht[$i+1]['total_score']:$t['total_score'];$up=$t['total_score']>$pv?'▲':($t['total_score']<$pv?'▼':'—');$cl=$t['total_score']>=80?'#16a34a':($t['total_score']>=50?'#ca8a04':'#dc2626');$lg=$gr_label($t['total_score']);$chg=(int)abs($t['total_score']-$pv);?>
<tr><td style="font-size:12px;"><?php echo esc_html(substr($t['created_at'],0,16));?></td><td style="font-weight:700;color:<?php echo esc_attr($cl);?>;font-size:13px;"><?php echo esc_html( (string) $t['total_score'] ); ?></td><td style="font-size:13px;font-weight:700;color:<?php echo esc_attr($gr_color($t['total_score']));?>;"><?php echo esc_html($lg);?></td><td style="font-size:12px;color:<?php echo esc_attr( $up === '▲' ? '#16a34a' : ( $up === '▼' ? '#dc2626' : '#94a3b8' ) );?>;"><?php echo esc_html($up);?> <?php echo esc_html( (string) $chg ); ?></td><td><button class="gf-btn gf-view-detail" style="font-size:11px;padding:3px 8px;" data-scan="<?php echo esc_attr($t['id']);?>">View Details</button></td></tr>
<?php endforeach;?></tbody></table></div>
<?php endif;?>

<div id="geo-forge-error" class="gf-notice gf-notice-error" style="display:none;"></div>

<?php if(!$hk):?>
<div class="gf-card gf-promo"><h2>🚀 Get Started with GEO KAMI</h2><p>Free tier: 100 points (5 scans). No credit card required.</p>
<div class="gf-grid gf-grid-3" style="margin:12px 0;"><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🎁</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">100 points</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔍</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">5 scans</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔧</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">Auto-fix</div></div></div>
<a href="<?php echo esc_url( 'https://geokami.com/register?ref=geo-forge' ); ?>" target="_blank" class="gf-btn gf-btn-primary" style="background:#fff!important;color:#4338ca!important;border-color:#fff!important;">Get Free API Key</a></div>
<?php endif;?>

<div class="gf-detail-overlay" id="gf-detail-dialog"><div class="gf-detail-dialog"><button class="gf-detail-close" onclick="document.getElementById('gf-detail-dialog').classList.remove('open')">&times;</button><div id="gf-detail-content"></div></div></div>
</div>
<?php if ( ! empty( $ht_embed ) ) : ?>
<script>
// Embedded scan data for "View Details" — lets the button work even if the REST
// endpoint isn't registered (e.g. PHP opcache serving an older plugin version).
window.GeoForgeScans = <?php echo wp_json_encode( $ht_embed ); ?>;
</script>
<?php endif; ?>
