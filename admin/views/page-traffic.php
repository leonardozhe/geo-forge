<?php
/**
 * Traffic view — unified compact design.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\Traffic\BotFamily;
use GEO_Forge\Traffic\Store as TrafficStore;

$filter_family = isset($_GET['family'])?BotFamily::tryFrom(sanitize_text_field(wp_unslash($_GET['family']))):null;
$filter_source = isset($_GET['source'])?sanitize_text_field(wp_unslash($_GET['source'])):null;
if($filter_source&&!in_array($filter_source,['bot_ua','well_known','markdown']))$filter_source=null;

$rows=TrafficStore::recent(100,$filter_family,$filter_source);
$summary=TrafficStore::summary_24h();
$chart=TrafficStore::chart_data(14);

$top_family=null;$top_max=0;
foreach($summary['by_family'] as $e){if((int)$e['n']>$top_max){$top_max=(int)$e['n'];$top_family=BotFamily::tryFrom($e['bot_family']);}}
$max_chart=1;foreach($chart['series'] as $s)foreach($s as $n)if($n>$max_chart)$max_chart=$n;
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
		<div><h1>GEO Forge <span class="geo-forge-subtitle">Traffic</span></h1><p class="geo-forge-muted">AI agent crawl records. IPs stored as SHA-256 hash only.</p></div>
	</div>

	<div class="pure-g geo-forge-stats">
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>24h Hits</h3><p class="geo-forge-stat"><?php echo esc_html((string)$summary['total_24h']);?></p><p class="geo-forge-muted">requests</p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>Top Bot</h3><p class="geo-forge-stat" style="font-size:18px;"><?php echo esc_html($top_family?$top_family->label():'—');?></p><p class="geo-forge-muted"><?php echo $top_max;?> hits</p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>Sample Rate</h3><p class="geo-forge-stat">1/<?php echo(int)get_option('geo_forge_traffic_sample_rate',10);?></p><p class="geo-forge-muted">regular bots</p>
		</div></div>
	</div>

	<div class="geo-forge-card">
		<h2>Trend (14d)</h2>
		<?php if(empty(array_filter($chart['series']))):?><p class="geo-forge-muted">No data yet.</p>
		<?php else: ?><table class="geo-forge-chart"><?php foreach($chart['series'] as $fk=>$series): $fam=BotFamily::tryFrom($fk);?>
			<tr><td class="geo-forge-chart-label"><?php echo esc_html($fam?$fam->label():$fk);?></td><td class="geo-forge-chart-bars"><?php foreach($series as $n):$pct=$max_chart>0?round($n/$max_chart*100):0;?><span class="geo-forge-chart-bar" style="height:<?php echo max(2,$pct);?>%;" title="<?php echo$n.' hits';?>"></span><?php endforeach;?></td><td class="geo-forge-chart-total"><?php echo array_sum($series);?></td></tr>
		<?php endforeach;?></table><?php endif;?>
	</div>

	<form method="get" class="pure-form geo-forge-filter">
		<input type="hidden" name="page" value="geo-forge-traffic"/>
		<select name="family" onchange="this.form.submit()" style="font-size:12px;"><option value="">All bots</option>
			<?php foreach(BotFamily::cases() as $fam):?><option value="<?php echo esc_attr($fam->value);?>" <?php selected($filter_family?->value,$fam->value);?>><?php echo esc_html($fam->label());?></option><?php endforeach;?>
		</select>
		<select name="source" onchange="this.form.submit()" style="font-size:12px;"><option value="">All sources</option>
			<option value="bot_ua" <?php selected($filter_source,'bot_ua');?>>Bot UA</option>
			<option value="well_known" <?php selected($filter_source,'well_known');?>>Well-known</option>
			<option value="markdown" <?php selected($filter_source,'markdown');?>>Markdown</option>
		</select>
	</form>

	<div class="geo-forge-card">
		<table class="pure-table"><thead><tr><th>Time</th><th>Bot</th><th>Source</th><th>Status</th><th>URL</th><th>IP Hash</th></tr></thead><tbody>
		<?php if(empty($rows)):?><tr><td colspan="6" class="geo-forge-muted">No traffic recorded yet.</td></tr>
		<?php else: foreach($rows as $row): $fam=BotFamily::tryFrom((string)$row['bot_family']);?>
			<tr><td style="font-size:11px;"><?php echo esc_html($row['recorded_at']);?></td><td><?php echo esc_html($fam?$fam->label():$row['bot_family']);?></td><td><?php echo esc_html($row['source']);?></td><td><?php echo(int)$row['response_status'];?></td><td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html($row['request_url']);?></td><td style="font-size:10px;"><?php echo esc_html(substr((string)$row['remote_ip_hash'],0,10));?>…</td></tr>
		<?php endforeach; endif;?></tbody></table>
		<p class="geo-forge-muted" style="margin-top:8px;">Showing recent <?php echo count($rows);?> entries.</p>
	</div>
</div>
