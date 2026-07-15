<?php
/**
 * Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) exit;
use GEO_Forge\GeoForge;
use GEO_Forge\Install\Installer;

$scanner   = new \GEO_Forge\Scanner\Scanner();
$fixer     = GeoForge::fixer();
$last      = $scanner->get_last_scan();
$last_time = (string) get_option( 'geo_forge_last_scan_time', '' );
$has_key   = (bool) Installer::get_setting( 'api_key', '' );

$score       = $last['total_score'] ?? null;
$grade_label = $last['grade_label'] ?? '';
$emoji       = match(true){null===$score=>'—',$score>=80=>'🟢',$score>=50=>'🟡',$score>=25=>'🟠',default=>'🔴'};
$cats        = is_array($last['category_scores']??null) ? $last['category_scores'] : [];
$checks      = is_array($last['checks_result']??null)   ? $last['checks_result']   : [];
$pass_count  = count(array_filter($checks,fn($c)=>($c['status']??'')==='pass'));
$fail_count  = count($checks) - $pass_count;
$fixable_cnt = $fixer ? count($fixer->list()) : 5;

global $wpdb;
$history = $wpdb->get_results("SELECT total_score,grade,created_at FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30", ARRAY_A) ?: [];
$trend   = array_reverse($history);
?>
<div class="geo-forge-wrap">

	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
		<div>
			<h1>Dashboard</h1>
			<?php if($last_time):?><div class="geo-forge-muted">Last scan: <?php echo esc_html($last_time);?></div><?php endif;?>
		</div>
		<div>
			<?php if($has_key):?>
				<span class="geo-forge-badge geo-forge-badge-success">🔗 Connected</span>
			<?php else:?>
				<a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="button">Add API Key</a>
			<?php endif;?>
		</div>
	</div>

	<div class="geo-forge-grid geo-forge-grid-3">
		<div class="geo-forge-card">
			<h3>AI Score</h3>
			<div class="geo-forge-stat"><?php echo null===$score?'—':$emoji.' '.$score;?><span style="font-size:14px;color:#94a3b8;margin-left:4px;">/100</span></div>
		</div>
		<div class="geo-forge-card">
			<h3>Status</h3>
			<div class="geo-forge-stat" style="font-size:20px;"><?php echo null===$score?'—':"<span style='color:#16a34a'>✅ $pass_count pass</span> &nbsp; <span style='color:#dc2626'>❌ $fail_count fail</span>";?></div>
		</div>
		<div class="geo-forge-card">
			<h3>Grade</h3>
			<div class="geo-forge-stat"><?php echo esc_html($grade_label?:'—');?></div>
		</div>
	</div>

	<?php if($score):?>
	<div class="geo-forge-grid geo-forge-grid-2">
		<div class="geo-forge-card">
			<h3>Category Breakdown</h3>
			<table>
				<?php foreach($cats as $c): $e=(int)($c['earned']??0);$m=max(1,(int)($c['max']??1));$p=round($e/$m*100);$cl=$p>=80?'#16a34a':($p>=50?'#ca8a04':'#dc2626');?>
				<tr>
					<td style="font-weight:600;width:180px;"><?php echo esc_html(ucfirst((string)($c['id']??'')));?></td>
					<td><div class="geo-forge-bar"><div class="geo-forge-bar-fill" style="width:<?php echo$p;?>%;background:<?php echo$cl;?>;"></div></div></td>
					<td style="text-align:right;font-weight:600;width:60px;color:<?php echo$cl;?>;"><?php echo$p;?>%</td>
				</tr>
				<?php endforeach;?>
			</table>
		</div>
		<div class="geo-forge-card">
			<h3>Check Results</h3>
			<div style="max-height:350px;overflow-y:auto;">
			<?php foreach($checks as $ch):
				$st=$ch['status']??'fail';$icon=$st==='pass'?'✅':($st==='warn'?'⚠️':'❌');
			?>
			<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">
				<span><?php echo$icon;?></span>
				<span style="flex:1;"><?php echo esc_html($ch['name']??$ch['id']??'?');?></span>
				<span style="font-size:11px;color:#94a3b8;"><?php echo(int)($ch['score']??0);?>/<?php echo(int)($ch['maxScore']??0);?></span>
			</div>
			<?php endforeach;?>
			</div>
		</div>
	</div>
	<?php endif;?>

	<?php if(count($trend)>=2):?>
	<div class="geo-forge-card">
		<h3>Score History</h3>
		<?php foreach($trend as $i=>$t):
			$prev=$i>0?$trend[$i-1]['total_score']:$t['total_score'];
			$up=$t['total_score']>$prev?'▲':($t['total_score']<$prev?'▼':'—');
			$cl=$t['total_score']>=80?'#16a34a':($t['total_score']>=50?'#ca8a04':'#dc2626');
		?>
		<div style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid #f1f5f9;">
			<div style="width:90px;font-size:11px;color:#94a3b8;"><?php echo esc_html(substr($t['created_at'],0,10));?></div>
			<div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="height:100%;width:<?php echo$t['total_score'];?>%;background:<?php echo$cl;?>;border-radius:3px;"></div></div>
			<div style="width:40px;text-align:right;font-weight:700;font-size:13px;color:<?php echo$cl;?>;"><?php echo$t['total_score'];?></div>
			<div style="width:20px;text-align:center;font-size:11px;color:#94a3b8;"><?php echo$up;?></div>
		</div>
		<?php endforeach;?>
	</div>
	<?php endif;?>

	<div style="display:flex;align-items:center;gap:12px;margin:16px 0;">
		<button type="button" id="geo-forge-scan-btn" class="geo-forge-btn-primary" <?php disabled(!$has_key);?>>Scan Now</button>
		<span id="geo-forge-scan-status"></span>
	</div>
	<div id="geo-forge-error" class="geo-forge-notice geo-forge-notice-error" style="display:none;"></div>

	<?php if(!$has_key):?>
	<div class="geo-forge-promo geo-forge-card">
		<h3>🚀 Get Started with GEO KAMI</h3>
		<p style="color:#fff">Free tier: 100 points (5 scans). No credit card required.</p>
		<div class="geo-forge-grid geo-forge-grid-3" style="margin:12px 0;">
			<div class="geo-forge-promo-item"><div class="geo-forge-stat" style="font-size:22px;color:#fff">🎁</div><p style="font-size:11px;color:#fff">100 points on signup</p></div>
			<div class="geo-forge-promo-item"><div class="geo-forge-stat" style="font-size:22px;color:#fff">🔍</div><p style="font-size:11px;color:#fff">5 comprehensive scans</p></div>
			<div class="geo-forge-promo-item"><div class="geo-forge-stat" style="font-size:22px;color:#fff">🔧</div><p style="font-size:11px;color:#fff">One-click auto-fix</p></div>
		</div>
		<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="geo-forge-btn-primary" style="display:inline-block;padding:8px 16px;border-radius:6px;text-decoration:none;color:#fff;background:#4338ca;">Get Free API Key</a>
	</div>
	<?php endif;?>
</div>
