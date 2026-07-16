<?php if(!defined('ABSPATH'))exit;use GEO_Forge\Log\Level;use GEO_Forge\Log\Logger;
$fl=isset($_GET['level'])?Level::tryFrom(sanitize_text_field(wp_unslash($_GET['level']))):null;
$rw=Logger::recent(200,$fl);
?>
<div class="geo-forge-wrap">
<div class="gf-header"><h1>Logs <span class="gf-subtitle">Debug & error tracking</span></h1><p class="gf-muted">Auto-pruned after <?php echo(int)get_option('geo_forge_log_retention_days',30);?> days. Min level: <strong><?php echo esc_html(get_option('geo_forge_log_min_level','info'));?></strong> (change via <code>geo_forge_log_min_level</code> option).</p></div>

<div class="gf-card">
	<div class="gf-filter">
		<form method="get" style="display:flex;align-items:center;gap:8px;"><input type="hidden" name="page" value="geo-forge-logs"/>
		<select name="level" onchange="this.form.submit()" style="font-size:12px;"><option value="">All levels</option>
			<?php foreach(Level::cases() as $l):?><option value="<?php echo esc_attr($l->value);?>" <?php selected($fl?->value,$l->value);?>><?php echo esc_html($l->label());?></option><?php endforeach;?>
		</select></form>
		<button type="button" id="geo-forge-clear-logs" class="gf-btn">Clear Logs</button>
		<span id="geo-forge-clear-status" style="font-size:12px;"></span>
	</div>
</div>

<div class="gf-card">
<?php if(empty($rw)):?><p class="gf-muted">No log entries.</p>
<?php else:?><table class="striped"><thead><tr><th>Time</th><th>Level</th><th>Source</th><th>Message</th><th>Req</th></tr></thead><tbody>
<?php foreach($rw as $r):$lv=Level::tryFrom((string)$r['level'])??Level::Debug;$ct=is_array($r['context'])?$r['context']:[];?>
<tr><td style="font-size:10px;"><?php echo esc_html($r['created_at']);?></td><td><span class="gf-log-badge <?php echo esc_attr('gf-log-'.$lv->value);?>"><?php echo esc_html($lv->label());?></span></td><td style="font-size:11px;"><?php echo esc_html($r['source']);?></td><td style="font-size:11px;"><?php echo esc_html($r['message']);?><?php if(!empty($ct)):?> <details style="display:inline;"><summary style="font-size:10px;">ctx</summary><pre style="font-size:10px;max-width:300px;overflow:auto;"><?php echo esc_html(wp_json_encode($ct,JSON_PRETTY_PRINT));?></pre></details><?php endif;?></td><td style="font-size:10px;"><?php echo esc_html(substr((string)$r['request_id'],0,8));?></td></tr>
<?php endforeach;?></tbody></table><p class="gf-muted" style="margin-top:8px;"><?php echo count($rw);?> entries</p><?php endif;?>
</div>
</div>