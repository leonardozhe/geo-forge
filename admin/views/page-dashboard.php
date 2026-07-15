<?php if(!defined('ABSPATH'))exit;use GEO_Forge\GeoForge;use GEO_Forge\Install\Installer;
$sc=new \GEO_Forge\Scanner\Scanner();$fx=GeoForge::fixer();$lk=$sc->get_last_scan();
$lt=(string)get_option('geo_forge_last_scan_time','');$hk=(bool)Installer::get_setting('api_key','');
$sc0=$lk['total_score']??null;$gl=$lk['grade_label']??'';
function gf_grade_label($s){return $s>=90?'S':($s>=75?'A':($s>=50?'B':($s>=25?'C':'D')));}
function gf_grade_color($s){return $s>=90?'#7c3aed':($s>=75?'#16a34a':($s>=50?'#2563eb':($s>=25?'#ca8a04':'#dc2626')));}
$gr=null!==$sc0?gf_grade_label($sc0):'—';$grc=null!==$sc0?gf_grade_color($sc0):'#94a3b8';
$ca=is_array($lk['category_scores']??null)?$lk['category_scores']:[];
$ck=is_array($lk['checks_result']??null)?$lk['checks_result']:[];
$ps=count(array_filter($ck,fn($c)=>($c['status']??'')==='pass'));$fl=count($ck)-$ps;
$fc=$fx?count($fx->list()):5;
global $wpdb;$ht=$wpdb->get_results("SELECT id,total_score,grade,grade_label,created_at FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 30",ARRAY_A)?:[];

// Category display name mapping
$cat_names=['ai-readability'=>'AI Readability','discoverability'=>'Discoverability','accessibility'=>'Content Accessibility','bot-control'=>'Bot Access Control','security'=>'Security & UX','protocol'=>'Protocol Discovery','commerce'=>'Commerce'];
?>
<div class="geo-forge-wrap">
<div class="gf-header"><div style="display:flex;align-items:center;justify-content:space-between;"><div><h1>Dashboard <span class="gf-subtitle">GEO Forge</span></h1><?php if($lt):?><span class="gf-muted">Last scan: <?php echo esc_html($lt);?></span><?php endif;?></div><div><?php if($hk):?><span class="gf-badge gf-badge-green">🔗 Connected</span><?php else:?><a href="<?php echo esc_url(admin_url('admin.php?page=geo-forge-settings'));?>" class="gf-btn">Add API Key</a><?php endif;?></div></div></div>

<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-stat-label">AI Score</div><div class="gf-stat"><?php echo null===$sc0?'—':esc_html($sc0);?><span style="font-size:14px;color:#94a3b8;"> /100</span></div></div>
	<div class="gf-card"><div class="gf-stat-label">Status</div><div class="gf-stat" style="font-size:20px;"><?php echo null===$sc0?'—':"<span style='color:#16a34a'>✅ $ps pass</span> · <span style='color:#dc2626'>❌ $fl fail</span>";?></div></div>
	<div class="gf-card"><div class="gf-stat-label">Grade</div><div class="gf-grade gf-grade-<?php echo strtolower($gr);?>" style="display:inline-flex;margin-top:4px;"><?php echo$gr;?></div></div>
</div>

<?php if($sc0):?>
<div class="gf-grid gf-grid-2" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-card-title">Category Breakdown</div>
		<table><?php foreach($ca as $c):$e=(int)($c['earned']??0);$m=max(1,(int)($c['max']??1));$p=round($e/$m*100);$cl=$p>=80?'#16a34a':($p>=50?'#ca8a04':'#dc2626');$nm=$cat_names[$c['id']]??ucfirst((string)($c['id']??''));?>
		<tr><td style="width:180px;font-weight:500;"><?php echo esc_html($nm);?></td><td><div class="gf-bar"><div class="gf-bar-fill" style="width:<?php echo$p;?>%;background:<?php echo$cl;?>;"></div></div></td><td style="width:48px;text-align:right;font-weight:600;color:<?php echo$cl;?>;"><?php echo$p;?>%</td></tr>
		<?php endforeach;?></table>
	</div>
	<div class="gf-card"><div class="gf-card-title">Check Results</div>
		<div style="max-height:360px;overflow-y:auto;"><?php foreach($ck as $ch):
			$st=$ch['status']??'fail';$ic=$st==='pass'?'✅':($st==='warn'?'⚠️':'❌');$label=$ch['label']??$ch['name']??$ch['id']??'?';
			$rec=$ch['recommendation']??'';$goal=$ch['goal']??'';$res=$ch['result']??'';$effort=$ch['effort']??'';
		?>
		<div class="gf-check-item">
			<span><?php echo$ic;?></span>
			<div style="flex:1;">
				<div style="font-weight:600;"><?php echo esc_html($label);?></div>
				<?php if($goal):?><div class="gf-check-meta">Goal: <?php echo esc_html($goal);?></div><?php endif;?>
				<?php if($res&&$st!=='pass'):?><div class="gf-check-meta">Result: <?php echo esc_html($res);?></div><?php endif;?>
				<?php if($rec):?><div class="gf-check-recommendation">💡 <?php echo esc_html($rec);?><?php if($effort):?> <span class="gf-muted">(≈<?php echo esc_html($effort);?>)</span><?php endif;?></div><?php endif;?>
			</div>
			<span style="font-size:11px;color:#94a3b8;"><?php echo(int)($ch['score']??0);?>/<?php echo(int)($ch['maxScore']??0);?></span>
		</div>
		<?php endforeach;?></div>
	</div>
</div>
<?php endif;?>

<?php if(count($ht)>=2):?>
<div class="gf-card"><div class="gf-card-title">Score History</div>
<table><thead><tr><th>Time</th><th>Score</th><th>Grade</th><th>Change</th><th></th></tr></thead><tbody>
<?php foreach($ht as $i=>$t):$pv=$i<count($ht)-1?$ht[$i+1]['total_score']:$t['total_score'];$up=$t['total_score']>$pv?'▲':($t['total_score']<$pv?'▼':'—');$cl=$t['total_score']>=80?'#16a34a':($t['total_score']>=50?'#ca8a04':'#dc2626');$lg=gf_grade_label($t['total_score']);?>
<tr><td style="font-size:12px;"><?php echo esc_html(substr($t['created_at'],0,16));?></td><td style="font-weight:700;color:<?php echo$cl;?>;"><?php echo$t['total_score'];?></td><td><span class="gf-badge" style="color:#fff;background:<?php echo gf_grade_color($t['total_score']);?>;"><?php echo$lg;?></span></td><td style="font-size:12px;color:<?php echo$up==='▲'?'#16a34a':($up==='▼'?'#dc2626':'#94a3b8');?>;"><?php echo$up;?> <?php echo abs($t['total_score']-$pv);?></td><td><button class="gf-btn gf-btn-sm" onclick="gfViewDetail('<?php echo esc_attr($t['id']??'');?>')" style="font-size:11px;padding:3px 8px;">View Details</button></td></tr>
<?php endforeach;?></tbody></table></div>
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
<?php endif;?>

<!-- Detail dialog -->
<div class="gf-detail-overlay" id="gf-detail-dialog"><div class="gf-detail-dialog"><button class="gf-detail-close" onclick="document.getElementById('gf-detail-dialog').classList.remove('open')">&times;</button><div id="gf-detail-content"></div></div></div>
</div>
<script>
function gfViewDetail(id){var d=document.getElementById('gf-detail-dialog'),c=document.getElementById('gf-detail-content');c.innerHTML='Loading...';d.classList.add('open');fetch(window.GeoForgeDashboard.restRoot+'scan/last',{credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeDashboard.restNonce}}).then(function(r){return r.json()}).then(function(b){if(b&&b.scan){var s=b.scan,ch=s.checks_result||[],cs=s.category_scores||[],h='<h2>Scan at '+s.created_at+'</h2><p class=\"gf-muted\">Score: <b>'+s.total_score+'</b> | Grade: <b>'+s.grade_label+'</b></p><hr style=\"margin:12px 0\">';h+='<h3>Checks</h3>';ch.forEach(function(x){var st=x.status||'fail',ic=st==='pass'?'✅':(st==='warn'?'⚠️':'❌');h+='<div class=\"gf-check-item\"><span>'+ic+'</span><div style=\"flex:1\"><div style=\"font-weight:600\">'+x.label+'</div>';if(x.goal)h+='<div class=\"gf-check-meta\">Goal: '+x.goal+'</div>';if(x.result)h+='<div class=\"gf-check-meta\">Result: '+x.result+'</div>';if(x.recommendation)h+='<div class=\"gf-check-recommendation\">💡 '+x.recommendation+(x.effort?' (≈'+x.effort+')':'')+'</div>';h+='</div><span style=\"font-size:11px;color:#94a3b8\">'+(x.score||0)+'/'+(x.maxScore||0)+'</span></div>'});c.innerHTML=h;}else{c.innerHTML='<p>Details not available.</p>'}}).catch(function(){c.innerHTML='<p>Failed to load.</p>'});}
</script>