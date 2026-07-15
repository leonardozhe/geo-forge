<?php if(!defined('ABSPATH'))exit;use GEO_Forge\GeoForge;
$fx=GeoForge::fixer();$fs=$fx?$fx->list():[];
$si=fn($s)=>match($s){'applied'=>'✅','verified'=>'✅✅','rolled_back'=>'⏪','failed'=>'❌',default=>'○'};
$rc=fn($r)=>match($r){'none'=>'#16a34a','low'=>'#2563eb','medium'=>'#ca8a04','high'=>'#dc2626',default=>'#94a3b8'};
$gp=[];foreach($fs as $f){$l=$f['priority']===1&&in_array($f['risk_level'],['none','low'])?'Quick Fixes':($f['priority']<=2?'Server Config':'Advanced');$gp[$l][]=$f;}
?>
<div class="geo-forge-wrap">
<div class="gf-header"><h1>Optimizations <span class="gf-subtitle">Fix now to improve your AI score</span></h1><p class="gf-muted">These optimizations can be applied immediately on your server. Each fix records a snapshot and can be rolled back.</p></div>
<div id="geo-forge-fix-status" class="gf-notice" style="display:none;"><p></p></div>

<?php if(empty($gp)):?>
<div class="gf-card"><p class="gf-muted">No optimization actions registered.</p></div>
<?php else:foreach($gp as $gl=>$it):$is_p1=str_contains($gl,'no risk');?>
<div class="gf-card<?php echo $is_p1?' gf-card-highlight':'';?>"<?php echo $is_p1?' style="border-color:#16a34a;border-left:3px solid #16a34a;"':'';?>>
	<div class="gf-card-title"><?php echo esc_html($gl);?> <span class="gf-badge gf-badge-blue"><?php echo count($it);?></span></div>
	<?php if($is_p1):?><p class="gf-muted" style="margin-bottom:8px;color:#16a34a;">✅ Zero risk — can be applied immediately without affecting your store.</p><?php endif;?>
	<table class="striped"><thead><tr><th>Fix</th><th>Risk</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody>
	<?php foreach($it as $fx2):$id=esc_attr($fx2['id']);$ap=in_array($fx2['status'],['applied','verified']);?>
	<tr data-fix-id="<?php echo$id;?>">
		<td><strong style="font-size:13px;"><?php echo esc_html($fx2['label']);?></strong><br><span class="gf-muted"><?php echo esc_html($fx2['description']);?></span></td>
		<td><span style="color:<?php echo esc_attr($rc($fx2['risk_level']));?>;font-weight:600;font-size:12px;"><?php echo ucfirst($fx2['risk_level']);?></span></td>
		<td class="geo-forge-fix-status-cell"><?php echo $si($fx2['status']).' '.ucfirst($fx2['status']);?></td>
		<td class="gf-muted"><?php echo $fx2['applied_at']??'—';?></td>
		<td style="white-space:nowrap;">
			<button class="gf-btn gf-btn-primary geo-forge-fix-apply" data-fix="<?php echo$id;?>" <?php disabled($ap);?>>Apply</button>
			<button class="gf-btn geo-forge-fix-verify" data-fix="<?php echo$id;?>" <?php disabled(!$ap);?>>Verify</button>
			<button class="gf-btn geo-forge-fix-rollback" data-fix="<?php echo$id;?>" <?php disabled(!$ap);?>>Undo</button>
		</td>
	</tr>
	<?php endforeach;?></tbody></table>
</div>
<?php endforeach;endif;?></div>