<?php
/**
 * Traffic — AI agent engagement stats.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\Traffic\BotFamily;
use GEO_Forge\Traffic\Store as TrafficStore;

$filter_family = isset($_GET['family'])?BotFamily::tryFrom(sanitize_text_field(wp_unslash($_GET['family']))):null;
$filter_source = isset($_GET['source'])?sanitize_text_field(wp_unslash($_GET['source'])):null;
if($filter_source&&!in_array($filter_source,['bot_ua','well_known','markdown']))$filter_source=null;

$rows    = TrafficStore::recent(100,$filter_family,$filter_source);
$summary = TrafficStore::summary_24h();
$chart   = TrafficStore::chart_data(14);

// Compute value metrics
$total_hits   = $summary['total_24h'];
$unique_bots  = count($summary['by_family']);
$success_rate = !empty($rows) ? round(count(array_filter($rows,fn($r)=>((int)($r['response_status']??0))<400))/max(1,count($rows))*100) : 100;

// All-time totals from chart data
$all_time = 0;
foreach($chart['series'] as $s) $all_time += array_sum($s);

// Source breakdown
$by_source = ['well_known'=>0,'markdown'=>0,'bot_ua'=>0];
foreach($rows as $r) if(isset($by_source[$r['source']])) $by_source[$r['source']]++;

// Top bot families from 24h
$families = [];
foreach($summary['by_family'] as $e) $families[] = ['name'=>BotFamily::tryFrom($e['bot_family'])?->label()??$e['bot_family'],'count'=>(int)$e['n']];
usort($families,fn($a,$b)=>$b['count']<=>$a['count']);
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header" style="margin-bottom:14px;">
		<h1>GEO Forge <span class="geo-forge-subtitle">Traffic</span></h1>
		<p class="geo-forge-muted">See how AI agents interact with your store since installing GEO Forge.</p>
	</div>

	<!-- Value metrics -->
	<div class="pure-g geo-forge-stats">
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>AI Bots Served</h3>
			<p class="geo-forge-stat"><?php echo esc_html((string)$unique_bots);?></p>
			<p class="geo-forge-muted">unique bot families in last 24h</p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>Successful Queries</h3>
			<p class="geo-forge-stat"><?php echo esc_html((string)$total_hits);?></p>
			<p class="geo-forge-muted">responses served, <?php echo$success_rate;?>% success rate</p>
		</div></div>
		<div class="pure-u-1-3"><div class="geo-forge-card">
			<h3>All-Time Total</h3>
			<p class="geo-forge-stat"><?php echo esc_html((string)$all_time);?></p>
			<p class="geo-forge-muted">AI requests handled since install</p>
		</div></div>
	</div>

	<!-- Source breakdown + top bots -->
	<div class="pure-g geo-forge-stats">
		<div class="pure-u-1-2"><div class="geo-forge-card">
			<h2>Traffic Sources</h2>
			<table class="pure-table" style="margin-top:8px;">
				<tr><td>Well-known routes</td><td style="text-align:right;"><strong><?php echo$by_source['well_known'];?></strong></td><td class="geo-forge-muted">llms.txt, security.txt</td></tr>
				<tr><td>Markdown requests</td><td style="text-align:right;"><strong><?php echo$by_source['markdown'];?></strong></td><td class="geo-forge-muted">Accept: text/markdown</td></tr>
				<tr><td>Bot crawls</td><td style="text-align:right;"><strong><?php echo$by_source['bot_ua'];?></strong></td><td class="geo-forge-muted">GPTBot, ClaudeBot, etc.</td></tr>
				<tr style="border-top:2px solid #e2e8f0;"><td>Total</td><td style="text-align:right;"><strong><?php echo array_sum($by_source);?></strong></td><td></td></tr>
			</table>
		</div></div>
		<div class="pure-u-1-2"><div class="geo-forge-card">
			<h2>Top Bot Families (24h)</h2>
			<?php if(empty($families)):?>
				<p class="geo-forge-muted" style="margin-top:8px;">No traffic recorded yet. AI bots will appear here once they start crawling your store.</p>
			<?php else:?>
				<table class="pure-table" style="margin-top:8px;">
					<?php foreach(array_slice($families,0,8) as $f):?>
					<tr><td><?php echo esc_html($f['name']);?></td><td style="text-align:right;"><strong><?php echo$f['count'];?></strong></td></tr>
					<?php endforeach;?>
				</table>
			<?php endif;?>
		</div></div>
	</div>

	<!-- Timeline chart -->
	<div class="geo-forge-card">
		<h2>14-Day Trend</h2>
		<?php if(empty(array_filter($chart['series']))):?><p class="geo-forge-muted">Not enough data yet. Traffic data accumulates over time.</p>
		<?php else:?><table class="geo-forge-chart"><?php foreach($chart['series'] as $fk=>$series): $fam=BotFamily::tryFrom($fk); $max_chart=max(1,max($series));?>
			<tr><td class="geo-forge-chart-label"><?php echo esc_html($fam?$fam->label():$fk);?></td><td class="geo-forge-chart-bars"><?php foreach($series as $n):$pct=round($n/$max_chart*100);?><span class="geo-forge-chart-bar" style="height:<?php echo max(2,$pct);?>%;" title="<?php echo$n;?>"></span><?php endforeach;?></td><td class="geo-forge-chart-total"><?php echo array_sum($series);?></td></tr>
		<?php endforeach;?></table><?php endif;?>
	</div>

	<!-- Filter + recent table -->
	<div class="geo-forge-card">
		<h2>Recent Activity</h2>
		<form method="get" class="pure-form geo-forge-filter">
			<input type="hidden" name="page" value="geo-forge-traffic"/>
			<select name="family" onchange="this.form.submit()" style="font-size:12px;"><option value="">All bots</option>
				<?php foreach(BotFamily::cases() as $fam):?><option value="<?php echo esc_attr($fam->value);?>" <?php selected($filter_family?->value,$fam->value);?>><?php echo esc_html($fam->label());?></option><?php endforeach;?>
			</select>
			<select name="source" onchange="this.form.submit()" style="font-size:12px;"><option value="">All sources</option>
				<option value="bot_ua" <?php selected($filter_source,'bot_ua');?>>Bot Crawl</option>
				<option value="well_known" <?php selected($filter_source,'well_known');?>>Well-known</option>
				<option value="markdown" <?php selected($filter_source,'markdown');?>>Markdown</option>
			</select>
		</form>
		<table class="pure-table" style="margin-top:10px;"><thead><tr><th>Time</th><th>Bot</th><th>Source</th><th>URL</th><th>Status</th></tr></thead><tbody>
		<?php if(empty($rows)):?><tr><td colspan="5" class="geo-forge-muted">No traffic yet. AI agents will start appearing after your site is scanned and optimized.</td></tr>
		<?php else: foreach(array_slice($rows,0,50) as $row): $fam=BotFamily::tryFrom((string)$row['bot_family']); $ok=((int)$row['response_status'])<400;?>
			<tr><td style="font-size:10px;white-space:nowrap;"><?php echo esc_html($row['recorded_at']);?></td><td><?php echo esc_html($fam?$fam->label():$row['bot_family']);?></td><td style="font-size:11px;"><?php echo esc_html($row['source']);?></td><td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;"><?php echo esc_html($row['request_url']);?></td><td><span style="color:<?php echo$ok?'#16a34a':'#dc2626';?>;"><?php echo$ok?'✅':'❌';?> <?php echo(int)$row['response_status'];?></span></td></tr>
		<?php endforeach; endif;?></tbody></table>
	</div>
</div>
