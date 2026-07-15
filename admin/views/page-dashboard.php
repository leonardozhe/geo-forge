<?php if(!defined('ABSPATH'))exit;use GEO_Forge\GeoForge;use GEO_Forge\Install\Installer;
$sc=new \GEO_Forge\Scanner\Scanner();$fx=GeoForge::fixer();$lk=$sc->get_last_scan();
$lt=(string)get_option('geo_forge_last_scan_time','');$hk=(bool)Installer::get_setting('api_key','');
$sc0=$lk['total_score']??null;$gl=$lk['grade_label']??'';
$em=match(true){null===$sc0=>'—',$sc0>=80=>'🟢',$sc0>=50=>'🟡',$sc0>=25=>'🟠',default=>'🔴'};
$ca=is_array($lk['category_scores']??null)?$lk['category_scores']:[];
$ck=is_array($lk['checks_result']??null)?$lk['checks_result']:[];
$ps=count(array_filter($ck,fn($c)=>($c['status']??'')==='pass'));$fl=count($ck)-$ps;
$fc=$fx?count($fx->list()):5;
global $wpdb;$ht=$wpdb->get_results("SELECT total_score,grade,created_at FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30",ARRAY_A)?:[];$tr=array_reverse($ht);
?>
<div class="geo-forge-wrap">
<div class="gf-header"><div style="display:flex;align-items:center;justify-content:space-between;"><div><h1>Dashboard <span class="gf-subtitle">GEO Forge</span></h1><?php if($lt):?><span class="gf-muted">Last scan: <?php echo esc_html($lt);?></span><?php endif;?></div><div><?php if($hk):?><span class="gf-badge gf-badge-green">🔗 Connected</span><?php else:?><a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="gf-btn">Add API Key</a><?php endif;?></div></div></div>

<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-stat-label">AI Score</div><div class="gf-stat"><?php echo null===$sc0?'—':$em.' '.$sc0;?><span style="font-size:14px;color:#94a3b8;"> /100</span></div></div>
	<div class="gf-card"><div class="gf-stat-label">Status</div><div class="gf-stat" style="font-size:20px;"><?php echo null===$sc0?'—':"<span style='color:#16a34a'>✅ $ps pass</span> · <span style='color:#dc2626'>❌ $fl fail</span>";?></div></div>
	<div class="gf-card"><div class="gf-stat-label">Grade</div><div class="gf-stat"><?php echo esc_html($gl?:'—');?></div></div>
</div>

<?php if($sc0):?>
<div class="gf-grid gf-grid-2" style="margin-bottom:12px;">
	<div class="gf-card">
		<div class="gf-card-title">Category Breakdown</div>
		<table><?php foreach($ca as $c):$e=(int)($c['earned']??0);$m=max(1,(int)($c['max']??1));$p=round($e/$m*100);$cl=$p>=80?'#16a34a':($p>=50?'#ca8a04':'#dc2626');?>
		<tr><td style="width:180px;font-weight:500;"><?php echo esc_html(ucfirst((string)($c['id']??'')));?></td><td><div class="gf-bar"><div class="gf-bar-fill" style="width:<?php echo$p;?>%;background:<?php echo$cl;?>;"></div></div></td><td style="width:48px;text-align:right;font-weight:600;color:<?php echo$cl;?>;"><?php echo$p;?>%</td></tr>
		<?php endforeach;?></table>
	</div>
	<div class="gf-card">
		<div class="gf-card-title">Check Results</div>
		<div style="max-height:340px;overflow-y:auto;"><?php foreach($ck as $ch):$st=$ch['status']??'fail';$ic=$st==='pass'?'✅':($st==='warn'?'⚠️':'❌');?>
		<div class="gf-check-item"><span><?php echo$ic;?></span><span style="flex:1;"><?php echo esc_html($ch['name']??$ch['id']??'?');?></span><span style="font-size:11px;color:#94a3b8;"><?php echo(int)($ch['score']??0);?>/<?php echo(int)($ch['maxScore']??0);?></span></div>
		<?php endforeach;?></div>
	</div>
</div>
<?php endif;?>

<?php if(count($tr)>=2):?>
<div class="gf-card"><div class="gf-card-title">Score History</div>
<?php foreach($tr as $i=>$t):$pv=$i>0?$tr[$i-1]['total_score']:$t['total_score'];$up=$t['total_score']>$pv?'▲':($t['total_score']<$pv?'▼':'—');$cl=$t['total_score']>=80?'#16a34a':($t['total_score']>=50?'#ca8a04':'#dc2626');?>
<div style="display:flex;align-items:center;gap:12px;padding:5px 0;border-bottom:1px solid #f1f5f9;">
<div style="width:90px;font-size:11px;color:#94a3b8;"><?php echo esc_html(substr($t['created_at'],0,10));?></div>
<div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;"><div style="height:100%;width:<?php echo$t['total_score'];?>%;background:<?php echo$cl;?>;border-radius:3px;"></div></div>
<div style="width:36px;text-align:right;font-weight:700;font-size:12px;color:<?php echo$cl;?>;"><?php echo$t['total_score'];?></div><div style="width:16px;text-align:center;font-size:10px;color:#94a3b8;"><?php echo$up;?></div></div>
<?php endforeach;?></div>
<?php endif;?>

<div style="display:flex;align-items:center;gap:10px;margin:16px 0;">
	<button type="button" id="geo-forge-scan-btn" class="gf-btn gf-btn-primary" <?php disabled(!$hk);?>>Scan Now</button>
	<span id="geo-forge-scan-status" style="font-size:12px;"></span>
</div>
<div id="geo-forge-error" class="gf-notice gf-notice-error" style="display:none;"></div>

<?php if(!$hk):?>
<div class="gf-card gf-promo"><h2>🚀 Get Started with GEO KAMI</h2><p>Free tier: 100 points (5 scans). No credit card required.</p>
<div class="gf-grid gf-grid-3" style="margin:12px 0;"><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🎁</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">100 points</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔍</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">5 scans</div></div><div class="gf-card" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.1);"><div class="gf-stat" style="font-size:22px;color:#fff;">🔧</div><div class="gf-muted" style="color:rgba(255,255,255,.8);">Auto-fix</div></div></div>
<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="gf-btn gf-btn-primary" style="background:#fff!important;color:#4338ca!important;border-color:#fff!important;">Get Free API Key</a></div>
<?php endif;?></div>