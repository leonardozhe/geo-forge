<?php if(!defined('ABSPATH'))exit;use GEO_Forge\Traffic\BotFamily;use GEO_Forge\Traffic\Store as TS;
$ff=isset($_GET['family'])&&!empty($_GET['family'])?sanitize_text_field(wp_unslash($_GET['family'])):null;
$fs=isset($_GET['source'])&&!empty($_GET['source'])?sanitize_text_field(wp_unslash($_GET['source'])):null;
if($fs&&!in_array($fs,['bot_ua','well_known','markdown']))$fs=null;
$rw=TS::recent(100,$ff,$fs);$sm=TS::summary_24h();$ch=TS::chart_data(14);
$th=$sm['total_24h'];$ub=count($sm['by_family']);
$bs=['well_known'=>0,'markdown'=>0,'bot_ua'=>0];foreach($rw as $r)if(isset($bs[$r['source']]))$bs[$r['source']]++;
$fm=[];foreach($sm['by_family'] as $e)$fm[]=['name'=>BotFamily::label($e['bot_family']),'count'=>(int)$e['n']];
usort($fm,fn($a,$b)=>$b['count']<=>$a['count']);
$at=0;foreach($ch['series'] as $s)$at+=array_sum($s);
?>
<div class="geo-forge-wrap">
<div class="gf-header"><h1>Traffic <span class="gf-subtitle">AI agent engagement</span></h1><p class="gf-muted">See how AI agents interact with your store since installing GEO Forge.</p></div>

<div class="gf-grid gf-grid-3" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-stat-label">AI Bots Served</div><div class="gf-stat"><?php echo esc_html((string)$ub);?></div><div class="gf-muted">unique bot families in last 24h</div></div>
	<div class="gf-card"><div class="gf-stat-label">Successful Queries</div><div class="gf-stat"><?php echo esc_html((string)$th);?></div><div class="gf-muted">responses served in 24h</div></div>
	<div class="gf-card"><div class="gf-stat-label">All-Time Total</div><div class="gf-stat"><?php echo esc_html((string)$at);?></div><div class="gf-muted">AI requests since install</div></div>
</div>

<div class="gf-grid gf-grid-2" style="margin-bottom:12px;">
	<div class="gf-card"><div class="gf-card-title">Traffic Sources</div>
		<table><tr><td style="font-weight:500;">Well-known routes</td><td style="text-align:right;font-weight:600;"><?php echo$bs['well_known'];?></td><td class="gf-muted">llms.txt, security.txt</td></tr>
		<tr><td style="font-weight:500;">Markdown requests</td><td style="text-align:right;font-weight:600;"><?php echo$bs['markdown'];?></td><td class="gf-muted">Accept: text/markdown</td></tr>
		<tr><td style="font-weight:500;">Bot crawls</td><td style="text-align:right;font-weight:600;"><?php echo$bs['bot_ua'];?></td><td class="gf-muted">GPTBot, ClaudeBot etc.</td></tr>
		<tr style="border-top:2px solid #e2e8f0;"><td style="font-weight:600;">Total</td><td style="text-align:right;font-weight:700;"><?php echo array_sum($bs);?></td><td></td></tr></table>
	</div>
	<div class="gf-card"><div class="gf-card-title">Top Bot Families (24h)</div>
		<?php if(empty($fm)):?><p class="gf-muted">No traffic yet. AI bots will appear after scanning.</p>
		<?php else:?><table><?php foreach(array_slice($fm,0,8) as $f):?><tr><td><?php echo esc_html($f['name']);?></td><td style="text-align:right;font-weight:600;"><?php echo$f['count'];?></td></tr><?php endforeach;?></table><?php endif;?>
	</div>
</div>

<div class="gf-card">
	<div class="gf-card-title">Recent Activity</div>
	<form method="get" class="gf-filter"><input type="hidden" name="page" value="geo-forge-traffic"/>
		<select name="family" onchange="this.form.submit()" style="font-size:12px;"><option value="">All bot families</option>
			<?php foreach(BotFamily::get_all_families() as $fam_key => $fam_label):?><option value="<?php echo esc_attr($fam_key);?>" <?php selected($ff,$fam_key);?>><?php echo esc_html($fam_label);?></option><?php endforeach;?>
		</select>
		<select name="source" onchange="this.form.submit()" style="font-size:12px;"><option value="">All sources</option>
			<option value="bot_ua" <?php selected($fs,'bot_ua');?>>Bot Crawl</option>
			<option value="well_known" <?php selected($fs,'well_known');?>>Well-known</option>
			<option value="markdown" <?php selected($fs,'markdown');?>>Markdown</option>
		</select>
	</form>
	<table class="striped"><thead><tr><th>Time</th><th>Bot</th><th>Source</th><th>URL</th><th>Status</th></tr></thead><tbody>
	<?php if(empty($rw)):?><tr><td colspan="5" class="gf-muted" style="padding:20px;">No traffic yet. AI agents will start appearing after your site is scanned and optimized.</td></tr>
	<?php else:foreach(array_slice($rw,0,50) as $r):$bot_family_str=(string)$r['bot_family'];$ok=((int)$r['response_status'])<400;?>
	<tr><td style="font-size:11px;"><?php echo esc_html($r['recorded_at']);?></td><td><?php echo esc_html(BotFamily::label($bot_family_str));?></td><td style="font-size:12px;"><?php echo esc_html($r['source']);?></td><td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"><?php echo esc_html($r['request_url']);?></td><td><span style="color:<?php echo$ok?'#16a34a':'#dc2626';?>;"><?php echo$ok?'✅':'❌';?></span></td></tr>
	<?php endforeach;endif;?></tbody></table>
</div>
</div>