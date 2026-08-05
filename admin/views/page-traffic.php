<?php if(!defined('ABSPATH'))exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
use GEO_Forge\Traffic\BotFamily;use GEO_Forge\Traffic\Store as TS;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ff=isset($_GET['family'])&&!empty($_GET['family'])?sanitize_text_field(wp_unslash($_GET['family'])):null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$fs=isset($_GET['source'])&&!empty($_GET['source'])?sanitize_text_field(wp_unslash($_GET['source'])):null;
if($fs&&!in_array($fs,['bot_ua','well_known','markdown']))$fs=null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$tp=isset($_GET['tpage'])?max(1,absint(wp_unslash($_GET['tpage']))):1;
$rpg=TS::page(50,$tp,$ff,$fs);$rws=$rpg['rows'];$rtt=(int)$rpg['total'];$rpp=(int)$rpg['pages'];
TS::maybe_rollup();$rw=TS::recent(100,$ff,$fs);$sm=TS::summary_24h();$ch=TS::chart_data(14);$nf=TS::not_found(20,$ff);
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
		<table><tr><td style="font-weight:500;">Well-known routes</td><td style="text-align:right;font-weight:600;"><?php echo esc_html( (string) $bs['well_known'] ); ?></td><td class="gf-muted">llms.txt, security.txt</td></tr>
		<tr><td style="font-weight:500;">Markdown requests</td><td style="text-align:right;font-weight:600;"><?php echo esc_html( (string) $bs['markdown'] ); ?></td><td class="gf-muted">Accept: text/markdown</td></tr>
		<tr><td style="font-weight:500;">Bot crawls</td><td style="text-align:right;font-weight:600;"><?php echo esc_html( (string) $bs['bot_ua'] ); ?></td><td class="gf-muted">GPTBot, ClaudeBot etc.</td></tr>
		<tr style="border-top:2px solid #e2e8f0;"><td style="font-weight:600;">Total</td><td style="text-align:right;font-weight:700;"><?php echo esc_html( (string) array_sum( $bs ) ); ?></td><td></td></tr></table>
	</div>
	<div class="gf-card"><div class="gf-card-title">Top Bot Families (24h)</div>
		<?php if(empty($fm)):?><p class="gf-muted">No traffic yet. AI bots will appear after scanning.</p>
		<?php else:?><table><?php foreach(array_slice($fm,0,8) as $f):?><tr><td><?php echo esc_html($f['name']);?></td><td style="text-align:right;font-weight:600;"><?php echo esc_html( (string) $f['count'] ); ?></td></tr><?php endforeach;?></table><?php endif;?>
	</div>
</div>

<?php if ( ! empty( $nf ) ) : ?>
<div class="gf-card" style="border-color:#dc2626;border-left:3px solid #dc2626;">
	<div class="gf-card-title">❌ Missing Content — LLM 404s <span class="gf-badge" style="background:#dc2626;color:#fff;"><?php echo esc_html( (string) count( $nf ) ); ?></span></div>
	<p class="gf-muted" style="margin-bottom:8px;"><?php esc_html_e( 'AI agents requested these URLs but we returned 404 — data they wanted that isn\'t provided (e.g. MCP/A2A cards, markdown variants). Adding these can improve your GEO score.', 'geo-forge' ); ?></p>
	<table class="striped"><thead><tr><th>Time</th><th>Bot</th><th>Source</th><th>Requested URL</th></tr></thead><tbody>
	<?php foreach ( $nf as $geo_forge_r ) : ?>
	<tr><td style="font-size:11px;"><?php echo esc_html( $geo_forge_r['recorded_at'] ); ?></td><td><?php echo esc_html( BotFamily::label( (string) $geo_forge_r['bot_family'] ) ); ?></td><td style="font-size:12px;"><?php echo esc_html( $geo_forge_r['source'] ); ?></td><td style="max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:#dc2626;"><?php echo esc_html( $geo_forge_r['request_url'] ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table>
</div>
<?php endif; ?>

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
	<?php if(empty($rws)):?><tr><td colspan="5" class="gf-muted" style="padding:20px;">No traffic yet. AI agents will start appearing after your site is scanned and optimized.</td></tr>
	<?php else:foreach($rws as $r):$bot_family_str=(string)$r['bot_family'];$ok=((int)$r['response_status'])<400;?>
	<tr><td style="font-size:11px;"><?php echo esc_html($r['recorded_at']);?></td><td><?php echo esc_html(BotFamily::label($bot_family_str));?></td><td style="font-size:12px;"><?php echo esc_html($r['source']);?></td><td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"><?php echo esc_html($r['request_url']);?></td><td><span style="color:<?php echo$ok?'#16a34a':'#dc2626';?>;"><?php echo$ok?'✅':'❌';?></span></td></tr>
	<?php endforeach;endif;?></tbody></table>
	<?php if ( $rpp > 1 ) : $gf_base = remove_query_arg( 'tpage', add_query_arg( array( 'page' => 'geo-forge-traffic' ), admin_url( 'admin.php' ) ) ); if ( $ff ) { $gf_base = add_query_arg( 'family', $ff, $gf_base ); } if ( $fs ) { $gf_base = add_query_arg( 'source', $fs, $gf_base ); } ?>
	<div style="display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap;">
		<?php if ( $tp > 1 ) : ?><a class="gf-btn" href="<?php echo esc_url( add_query_arg( 'tpage', $tp - 1, $gf_base ) ); ?>">← <?php esc_html_e( 'Prev', 'geo-forge' ); ?></a><?php else : ?><span class="gf-btn" style="opacity:.4;pointer-events:none;">← <?php esc_html_e( 'Prev', 'geo-forge' ); ?></span><?php endif; ?>
		<span class="gf-muted"><?php echo esc_html( sprintf( /* translators: 1: current page, 2: total pages, 3: total records */ __( 'Page %1$d of %2$d · %3$d records', 'geo-forge' ), $tp, $rpp, $rtt ) ); ?></span>
		<?php if ( $tp < $rpp ) : ?><a class="gf-btn" href="<?php echo esc_url( add_query_arg( 'tpage', $tp + 1, $gf_base ) ); ?>"><?php esc_html_e( 'Next', 'geo-forge' ); ?> →</a><?php else : ?><span class="gf-btn" style="opacity:.4;pointer-events:none;"><?php esc_html_e( 'Next', 'geo-forge' ); ?> →</span><?php endif; ?>
	</div>
	<?php endif; ?>
</div>
</div>