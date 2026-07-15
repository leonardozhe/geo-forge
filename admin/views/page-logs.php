<?php
/**
 * Logs view (Pico CSS)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\Log\Level;
use GEO_Forge\Log\Logger;
$filter_level = isset($_GET['level'])?Level::tryFrom(sanitize_text_field(wp_unslash($_GET['level']))):null;
$rows = Logger::recent(200,$filter_level);
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<h1>Logs <span class="geo-forge-subtitle">Debug and error tracking</span></h1>
		<p class="geo-forge-muted">Auto-pruned after <?php echo(int)get_option('geo_forge_log_retention_days',30);?> days. Min level: <?php echo esc_html(get_option('geo_forge_log_min_level','warning'));?>.</p>
	</div>

	<div class="geo-forge-card">
		<div style="display:flex;align-items:center;gap:8px;">
			<form method="get" style="display:flex;align-items:center;gap:8px;">
				<input type="hidden" name="page" value="geo-forge-logs"/>
				<select name="level" onchange="this.form.submit()"><option value="">All levels</option>
					<?php foreach(Level::cases() as $lvl):?><option value="<?php echo esc_attr($lvl->value);?>" <?php selected($filter_level?->value,$lvl->value);?>><?php echo esc_html($lvl->label());?></option><?php endforeach;?>
				</select>
			</form>
			<button type="button" id="geo-forge-clear-logs" class="button">Clear Logs</button>
			<span id="geo-forge-clear-status" class="geo-forge-muted" aria-live="polite"></span>
		</div>
	</div>

	<div class="geo-forge-card">
		<?php if(empty($rows)):?><p class="geo-forge-muted">No log entries.</p>
		<?php else: ?><table><thead><tr><th>Time</th><th>Level</th><th>Source</th><th>Message</th><th>Req</th></tr></thead><tbody>
		<?php foreach($rows as $row): $lvl=Level::tryFrom((string)$row['level'])??Level::Debug; $ctx=is_array($row['context'])?$row['context']:[];?>
			<tr><td style="font-size:11px;white-space:nowrap;"><?php echo esc_html($row['created_at']);?></td><td><span class="geo-forge-log-badge <?php echo esc_attr($lvl->css_class());?>"><?php echo esc_html($lvl->label());?></span></td><td style="font-size:12px;"><?php echo esc_html($row['source']);?></td><td style="font-size:12px;"><?php echo esc_html($row['message']);?><?php if(!empty($ctx)):?> <details style="display:inline;"><summary style="font-size:11px;">context</summary><pre style="font-size:11px;max-width:400px;"><?php echo esc_html(wp_json_encode($ctx,JSON_PRETTY_PRINT));?></pre></details><?php endif;?></td><td style="font-size:11px;"><?php echo esc_html(substr((string)$row['request_id'],0,8));?></td></tr>
		<?php endforeach;?></tbody></table>
		<p class="geo-forge-muted" style="margin-top:8px;"><?php echo count($rows);?> entries</p>
		<?php endif; ?>
	</div>
</div>
