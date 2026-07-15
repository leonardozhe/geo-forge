<?php
/**
 * Fix Center / Optimizations view — unified compact design.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\GeoForge;
$fixer = GeoForge::fixer();
$fixes = $fixer ? $fixer->list() : array();

$status_icon = fn($s) => match($s){'applied'=>'✅','verified'=>'✅✅','rolled_back'=>'⏪','failed'=>'❌',default=>'○'};
$risk_color  = fn($r) => match($r){'none'=>'#16a34a','low'=>'#2563eb','medium'=>'#ca8a04','high'=>'#dc2626',default=>'#94a3b8'};

$grouped = array();
foreach($fixes as $f){
	$label = $f['priority']===1&&in_array($f['risk_level'],['none','low'])?'P1 Critical':($f['priority']<=2?'P2 Warning':'P3 Optional');
	$grouped[$label][]=$f;
}
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header" style="margin-bottom:14px;">
		<h1>GEO Forge <span class="geo-forge-subtitle">Optimizations</span></h1>
		<p class="geo-forge-muted">Apply auto-fixes to improve your AI readiness score. Each fix is reversible.</p>
	</div>

	<div id="geo-forge-fix-status" class="geo-forge-notice" style="display:none;"><p></p></div>

	<?php if(empty($grouped)): ?>
		<div class="geo-forge-card"><p class="geo-forge-muted">No optimization actions registered.</p></div>
	<?php else: foreach($grouped as $group_label=>$items): ?>
		<div class="geo-forge-card">
			<h2><?php echo esc_html($group_label); ?> <span class="geo-forge-badge geo-forge-badge-info"><?php echo count($items);?></span></h2>
			<table class="pure-table">
				<thead><tr><th>Fix</th><th>Risk</th><th>Status</th><th>Applied</th><th></th></tr></thead>
				<tbody>
					<?php foreach($items as $fix):
						$id=esc_attr($fix['id']); $applied=in_array($fix['status'],['applied','verified']); ?>
					<tr data-fix-id="<?php echo$id;?>">
						<td><strong style="font-size:13px;"><?php echo esc_html($fix['label']);?></strong><br><span class="geo-forge-muted"><?php echo esc_html($fix['description']);?></span></td>
						<td><span style="color:<?php echo esc_attr($risk_color($fix['risk_level']));?>;font-weight:600;font-size:11px;"><?php echo ucfirst($fix['risk_level']);?></span></td>
						<td><?php echo $status_icon($fix['status']).' '.ucfirst($fix['status']);?></td>
						<td class="geo-forge-muted"><?php echo $fix['applied_at']??'—';?></td>
						<td style="white-space:nowrap;">
							<button class="pure-button pure-button-primary geo-forge-fix-apply" data-fix="<?php echo$id;?>" <?php disabled($applied);?>>Apply</button>
							<button class="pure-button geo-forge-fix-verify" data-fix="<?php echo$id;?>" <?php disabled(!$applied);?>>Verify</button>
							<button class="pure-button geo-forge-fix-rollback" data-fix="<?php echo$id;?>" <?php disabled(!$applied);?>>Undo</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; endif; ?>
</div>
