<?php
/**
 * Dashboard — scan results + history
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\GeoForge;

$scanner    = new \GEO_Forge\Scanner\Scanner();
$fixer      = GeoForge::fixer();
$last       = $scanner->get_last_scan();
$last_time  = (string) get_option( 'geo_forge_last_scan_time', '' );
$has_key    = (bool) get_option( 'geo_forge_api_key', '' );

$score       = $last['total_score'] ?? null;
$grade_label = $last['grade_label'] ?? '';
$grade_emoji = match(true){null===$score=>'—',$score>=80=>'🟢',$score>=50=>'🟡',$score>=25=>'🟠',default=>'🔴'};
$cats        = is_array($last['category_scores']??null) ? $last['category_scores'] : array();
$checks      = is_array($last['checks_result']??null)   ? $last['checks_result']   : array();
$pass_count  = count(array_filter($checks,fn($c)=>($c['status']??'')==='pass'));
$fail_count  = count($checks) - $pass_count;
$fixable_cnt = $fixer ? count($fixer->list()) : 5;

// Fetch scan history from local DB
global $wpdb;
$history = $wpdb->get_results("SELECT total_score, grade, created_at FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30", ARRAY_A) ?: [];
$trend   = array_reverse($history); // oldest first for chart
$max_score = 100;
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<div style="display:flex;align-items:center;justify-content:space-between;">
			<div>
				<h1>Dashboard <span class="geo-forge-subtitle">GEO Forge</span></h1>
				<?php if($last_time):?><span class="geo-forge-muted">Last scan: <time><?php echo esc_html($last_time);?></time></span><?php endif;?>
			</div>
			<div>
				<?php if($has_key):?>
					<span class="geo-forge-badge geo-forge-badge-success">🔗 Connected</span>
				<?php else:?>
					<a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="pure-button">Add API Key</a>
				<?php endif;?>
			</div>
		</div>
	</div>

	<!-- Stat cards -->
	<div class="pure-g geo-forge-stats">
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h4>AI Score</h4>
			<p class="geo-forge-stat"><?php echo null===$score?'—':esc_html($grade_emoji.' '.$score);?><span class="geo-forge-muted" style="font-size:16px;margin-left:4px;">/100</span></p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h4>Status</h4>
			<p class="geo-forge-stat" style="font-size:22px;"><?php echo null===$score?'—':"<span style='color:#16a34a;'>✅ $pass_count</span> pass &nbsp; <span style='color:#dc2626;'>❌ $fail_count</span> fail";?></p>
			<p class="geo-forge-muted"><?php echo $fixable_cnt;?> auto-fixable</p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h4>Grade</h4>
			<p class="geo-forge-stat"><?php echo esc_html($grade_label?:'—');?></p>
			<p class="geo-forge-muted">GEO KAMI rating</p>
		</div></div>
	</div>

	<!-- Score trend -->
	<?php if(count($trend)>=2):?>
	<div class="geo-forge-card">
		<h2>Score History</h2>
		<div class="geo-forge-chart" style="margin-top:12px;">
			<?php foreach($trend as $i=>$t):
				$prev = $i>0 ? $trend[$i-1]['total_score'] : $t['total_score'];
				$up   = $t['total_score'] > $prev ? '▲' : ($t['total_score'] < $prev ? '▼' : '—');
				$color= $t['total_score'] >= 80 ? '#16a34a' : ($t['total_score'] >= 50 ? '#ca8a04' : '#dc2626');
			?>
			<div style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid #f1f5f9;">
				<div style="width:100px;font-size:12px;color:#64748b;"><?php echo esc_html(substr($t['created_at'],0,10));?></div>
				<div style="flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div style="height:100%;width:<?php echo$t['total_score'];?>%;background:<?php echo$color;?>;border-radius:4px;"></div></div>
				<div style="width:48px;text-align:right;font-weight:700;font-size:14px;color:<?php echo$color;?>;"><?php echo$t['total_score'];?></div>
				<div style="width:20px;text-align:center;font-size:12px;color:#64748b;"><?php echo$up;?></div>
			</div>
			<?php endforeach;?>
		</div>
	</div>
	<?php endif;?>

	<!-- Category breakdowns -->
	<div class="pure-g" style="margin-bottom:16px;">
		<div class="pure-u-1 pure-u-md-1-2"><div class="geo-forge-card">
			<h2>Category Breakdown</h2>
			<?php if(empty($cats)):?><p class="geo-forge-muted">Run a scan to see scores.</p>
			<?php else:?><table class="pure-table"><?php foreach($cats as $c): $e=(int)($c['earned']??0);$m=max(1,(int)($c['max']??1));$p=round($e/$m*100);$cl=$p>=80?'#16a34a':($p>=50?'#ca8a04':'#dc2626');?>
				<tr><td style="font-weight:600;"><?php echo esc_html(ucfirst((string)($c['id']??'')));?></td><td><div class="geo-forge-bar"><div class="geo-forge-bar-fill" style="width:<?php echo$p;?>%;background:<?php echo$cl;?>;"></div></div></td><td style="text-align:right;font-weight:600;color:<?php echo$cl;?>;"><?php echo$p;?>%</td><td class="geo-forge-muted"><?php echo$e;?>/<?php echo$m;?></td></tr>
			<?php endforeach;?></table><?php endif;?>
		</div></div>
		<div class="pure-u-1 pure-u-md-1-2"><div class="geo-forge-card">
			<h2>Check Results</h2>
			<?php if(empty($checks)):?><p class="geo-forge-muted">Run a scan to see check details.</p>
			<?php else:?><ul class="geo-forge-check-list">
				<?php foreach($checks as $ch):
					$st=$ch['status']??'fail';$icon=$st==='pass'?'✅':($st==='warn'?'⚠️':'❌');$cls=$st==='pass'?'geo-forge-check-pass':($st==='warn'?'geo-forge-check-warn':'geo-forge-check-fail');
				?><li class="geo-forge-check-item">
					<span class="geo-forge-check-icon <?php echo$cls;?>"><?php echo$icon;?></span>
					<span class="geo-forge-check-body">
						<span class="geo-forge-check-name"><?php echo esc_html($ch['name']??$ch['id']??'Unknown');?></span>
						<span class="geo-forge-check-desc"><?php echo esc_html($ch['goal']??$ch['result']??'');?></span>
					</span>
					<span class="geo-forge-check-score"><?php echo(int)($ch['score']??0);?>/<?php echo(int)($ch['maxScore']??0);?></span>
				</li><?php endforeach;?>
			</ul><?php endif;?>
		</div></div>
	</div>

	<!-- Actions -->
	<div class="geo-forge-actions" style="margin:16px 0;">
		<button type="button" id="geo-forge-scan-btn" class="pure-button pure-button-primary" <?php disabled(!$has_key);?>>Scan Now</button>
		<span id="geo-forge-scan-status" class="geo-forge-muted" aria-live="polite"></span>
	</div>
	<div id="geo-forge-error" class="geo-forge-notice geo-forge-notice-error" style="display:none;"><p></p></div>

	<!-- Promo when no key -->
	<?php if(!$has_key):?>
	<div class="geo-forge-card geo-forge-promo">
		<h2>🚀 Get Started with GEO KAMI</h2>
		<p>Connect to GEO KAMI API for AI agent optimization. Free tier: 100 points (5 scans).</p>
		<div class="pure-g" style="margin:12px 0;">
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>Free Tier</strong><p>100 points</p></div></div>
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>5 Scans</strong><p>Full AI audit</p></div></div>
			<div class="pure-u-1-3"><div class="geo-forge-promo-item"><strong>Auto-Fix</strong><p>One-click</p></div></div>
		</div>
		<div class="geo-forge-promo-cta">
			<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="pure-button pure-button-primary">Get Free API Key</a>
			<a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="pure-button">Enter Key</a>
		</div>
	</div>
	<?php endif;?>
</div>