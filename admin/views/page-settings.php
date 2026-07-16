<?php if(!defined('ABSPATH'))exit;
use GEO_Forge\Install\Installer;use GEO_Forge\WellKnown\LlmsTxt;use GEO_Forge\WellKnown\SecurityTxt;use GEO_Forge\WellKnown\RobotsTxt;
$ak=(string)Installer::get_setting('api_key','');$ab=(string)Installer::get_setting('api_base','https://api.geokami.com');
$lc=LlmsTxt::get_current();$sc=SecurityTxt::get_current();$rc=RobotsTxt::get_current();$lu=home_url('/llms.txt');
?>
<div class="geo-forge-wrap">
<div class="gf-header"><h1>Settings <span class="gf-subtitle">GEO Forge</span></h1></div>

<div class="gf-tabs">
	<button class="gf-tab active" data-tab="tab-api">API</button>
	<button class="gf-tab" data-tab="tab-content">Content</button>
	<button class="gf-tab" data-tab="tab-about">About</button>
</div>

<div class="gf-tab-content active" id="tab-api">
<?php if(!$ak):?>
<div class="gf-card gf-promo">
	<div class="gf-card-title" style="color:#fff;">🔑 Get Your GEO KAMI API Key</div>
	<p>Sign up for free and get 100 points (5 comprehensive scans).</p>
	<ol style="background:rgba(255,255,255,.08);padding:12px 16px 12px 32px;border-radius:4px;margin:10px 0;font-size:13px;">
		<li style="margin:4px 0;color:#fff;"><a href="https://geokami.com/register?ref=geo-forge" target="_blank" style="color:#fff;text-decoration:underline;">Create a free GEO KAMI account</a></li>
		<li style="margin:4px 0;color:#fff;">Verify your email → Dashboard → API Keys → Copy key</li>
		<li style="margin:4px 0;color:#fff;">Paste below and save</li>
	</ol>
	<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="gf-btn gf-btn-primary" style="background:#fff!important;color:#4338ca!important;border-color:#fff!important;">Register for Free</a>
</div>
<?php endif;?>

<?php settings_errors('geo_forge');?>

<div class="gf-card">
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
		<?php wp_nonce_field('geo_forge_save_settings','geo_forge_settings_nonce');?>
		<input type="hidden" name="action" value="geo_forge_save_settings"/>

		<div class="gf-form-group">
			<label for="geo_forge_api_key">GEO KAMI API Key</label>
			<div class="gf-form-inline">
				<input type="password" id="geo_forge_api_key" name="geo_forge_api_key" value="<?php echo esc_attr($ak);?>" placeholder="gk_xxxxxxxxxxxxxxx" style="flex:1;"/>
				<button type="button" id="geo-forge-health-btn" class="gf-btn">Health Check</button>
				<span id="geo-forge-health-status" style="font-size:12px;"></span>
			</div>
			<div class="gf-hint">35-character API key starting with gk_.</div>
		</div>

		<div class="gf-form-group">
			<label for="geo_forge_api_base">API Base URL</label>
			<input type="url" id="geo_forge_api_base" name="geo_forge_api_base" value="<?php echo esc_attr($ab);?>"/>
			<div class="gf-hint">Default: https://api.geokami.com</div>
		</div>

		<button type="submit" class="gf-btn gf-btn-primary">Save Settings</button>
	</form>
</div>
</div>

<div class="gf-tab-content" id="tab-content">
	<div id="geo-forge-editor-status" class="gf-notice" style="display:none;"></div>
	<div class="gf-card">
		<div class="gf-card-title">llms.txt</div>
		<div class="gf-muted" style="margin-bottom:8px;">Served at <code><?php echo esc_html($lu);?></code></div>
		<div style="display:flex;gap:8px;margin-bottom:8px;">
			<button type="button" id="geo-forge-save-llms" class="gf-btn gf-btn-primary">Save</button>
			<button type="button" id="geo-forge-regen-llms" class="gf-btn">Regenerate</button>
			<span class="gf-muted"><?php echo esc_html( (string) strlen( $lc ) ); ?> bytes</span>
		</div>
		<textarea id="geo-forge-llms-content" class="gf-editor" rows="16"><?php echo esc_textarea($lc);?></textarea>
	</div>
	<div class="gf-card">
		<div class="gf-card-title">security.txt</div>
		<div class="gf-muted" style="margin-bottom:8px;">Served at <code>/.well-known/security.txt</code></div>
		<div style="display:flex;gap:8px;margin-bottom:8px;">
			<button type="button" id="geo-forge-save-security" class="gf-btn gf-btn-primary">Save</button>
			<button type="button" id="geo-forge-regen-security" class="gf-btn">Regenerate</button>
		</div>
		<textarea id="geo-forge-security-content" class="gf-editor" rows="8"><?php echo esc_textarea($sc);?></textarea>
	</div>
	<div class="gf-card">
		<div class="gf-card-title">AI Bot Rules (robots.txt)</div>
		<div class="gf-muted" style="margin-bottom:8px;">Allow AI crawlers to index your store.</div>
		<div style="display:flex;gap:8px;margin-bottom:8px;">
			<button type="button" id="geo-forge-save-robots" class="gf-btn gf-btn-primary">Save</button>
			<button type="button" id="geo-forge-regen-robots" class="gf-btn">Regenerate</button>
		</div>
		<textarea id="geo-forge-robots-content" class="gf-editor" rows="12"><?php echo esc_textarea($rc);?></textarea>
	</div>
</div>

<div class="gf-tab-content" id="tab-about">
	<?php
	// Inline SVG line icons (20x20, stroke 2, currentColor) — keeps styling CSS-driven.
	$geo_forge_svg_tags = array(
		'svg' => array('width'=>true,'height'=>true,'viewBox'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'stroke-linecap'=>true,'stroke-linejoin'=>true,'aria-hidden'=>true),
		'circle' => array('cx'=>true,'cy'=>true,'r'=>true),
		'path' => array('d'=>true),
		'polyline' => array('points'=>true),
		'rect' => array('x'=>true,'y'=>true,'width'=>true,'height'=>true,'rx'=>true),
		'line' => array('x1'=>true,'y1'=>true,'x2'=>true,'y2'=>true),
	);
	$ico = function ( string $path ) use ( $geo_forge_svg_tags ): string {
		return wp_kses( '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>', $geo_forge_svg_tags );
	};
	$ic = array(
		'search'   => $ico( '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>' ),
		'file'     => $ico( '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>' ),
		'wrench'   => $ico( '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>' ),
		'bot'      => $ico( '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M12 8V4"/><path d="M8 8V4"/><path d="M16 8V4"/><circle cx="8" cy="14" r="1"/><circle cx="16" cy="14" r="1"/>' ),
		'globe'    => $ico( '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/>' ),
		'shield'   => $ico( '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' ),
		'zap'      => $ico( '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>' ),
		'cart'     => $ico( '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>' ),
		'sparkles' => $ico( '<path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/>' ),
	);
	?>
	<style>
		.gf-about-hero{background:linear-gradient(135deg,#4338ca 0%,#6d28d9 100%);color:#fff;border-radius:10px;padding:24px 28px;margin-bottom:14px;}
		.gf-about-hero h2{margin:0 0 8px;font-size:17px;font-weight:700;color:#fff!important;}
		.gf-about-hero p{margin:0;font-size:13px;line-height:1.6;color:rgba(255,255,255,.92)!important;}
		.gf-about-hero p strong{color:#fff!important;}
		.gf-about-hero a{color:#fff;text-decoration:underline;}
		.gf-about-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;}
		.gf-about-action{text-align:center;padding:22px 14px;}
		.gf-about-action .gf-about-icon{width:40px;height:40px;border-radius:10px;background:#eef2ff;color:#4338ca;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;}
		.gf-about-action h3{margin:0 0 4px;font-size:14px;font-weight:700;color:#1e293b;}
		.gf-about-action p{margin:0;font-size:12px;color:#64748b;line-height:1.5;}
		.gf-about-caps{display:grid;grid-template-columns:repeat(2,1fr);gap:6px 20px;}
		.gf-about-cap{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;}
		.gf-about-cap:last-child,.gf-about-cap:nth-last-child(2){border-bottom:none;}
		.gf-about-cap-icon{color:#4338ca;flex-shrink:0;}
		.gf-about-cap-text{font-size:12.5px;line-height:1.4;}
		.gf-about-cap-text strong{display:block;font-size:12.5px;color:#1e293b;margin-bottom:1px;}
		.gf-about-cap-text span{color:#64748b;font-size:11.5px;}
	</style>

	<div class="gf-about-hero">
		<h2>Why Generative Engine Optimization?</h2>
		<p>
			Over <strong>40% of searches</strong> now return AI-generated answers. If AI agents can't parse your store, <strong>your products don't exist</strong> to ChatGPT, Perplexity, Claude, or Google AI — no matter how well you rank in traditional search.<br>
			<strong>GEO Forge bridges this gap:</strong> audit 22+ AI-readiness checks, auto-deploy fixes, and make your store visible to the agents that now drive real purchase decisions.
		</p>
	</div>

	<div class="gf-about-grid3">
		<div class="gf-card gf-about-action">
			<div class="gf-about-icon"><?php echo wp_kses( $ic['search'], $geo_forge_svg_tags ); ?></div>
			<h3>Scan</h3>
			<p>One-click audit across 7 AI-readiness categories. Get a 0–100 score with a clear grade.</p>
		</div>
		<div class="gf-card gf-about-action">
			<div class="gf-about-icon"><?php echo wp_kses( $ic['file'], $geo_forge_svg_tags ); ?></div>
			<h3>Publish</h3>
			<p>Auto-generate llms.txt, security.txt, AI-friendly robots.txt, and MCP/A2A agent cards.</p>
		</div>
		<div class="gf-card gf-about-action">
			<div class="gf-about-icon"><?php echo wp_kses( $ic['wrench'], $geo_forge_svg_tags ); ?></div>
			<h3>Fix</h3>
			<p>One-click apply for top issues. Every change is snapshotted and fully reversible.</p>
		</div>
	</div>

	<div class="gf-card">
		<div class="gf-card-title" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
			<span style="color:#4338ca;"><?php echo wp_kses( $ic['sparkles'], $geo_forge_svg_tags ); ?></span>
			What GEO Forge Covers
		</div>
		<div class="gf-about-caps">
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['bot'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>AI Readability</strong><span>llms.txt, structured data, semantic structure</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['globe'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Discoverability</strong><span>AI-friendly robots.txt, sitemap, indexing hints</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['file'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Content Accessibility</strong><span>Markdown variants of key pages (About, FAQ)</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['shield'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Bot Access Control</strong><span>Granular AI bot permissions and rules</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['shield'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Security &amp; UX</strong><span>HTTPS, HSTS, security.txt, privacy headers</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['zap'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Protocol Discovery</strong><span>MCP server, A2A agent, OpenAPI endpoints</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['cart'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Commerce Signals</strong><span>aggregateRating, product schema, AI shopping</span></div>
			</div>
			<div class="gf-about-cap">
				<div class="gf-about-cap-icon"><?php echo wp_kses( $ic['sparkles'], $geo_forge_svg_tags ); ?></div>
				<div class="gf-about-cap-text"><strong>Continuous Monitoring</strong><span>Track AI traffic, score trends, fix regressions</span></div>
			</div>
		</div>
	</div>
</div>
</div>
<script>
(function(){
	document.querySelectorAll('.gf-tab').forEach(function(t){
		t.addEventListener('click',function(){
			document.querySelectorAll('.gf-tab').forEach(function(x){x.classList.remove('active');});
			t.classList.add('active');
			document.querySelectorAll('.gf-tab-content').forEach(function(c){c.classList.remove('active');});
			document.getElementById(t.dataset.tab).classList.add('active');
		});
	});
})();
</script>
