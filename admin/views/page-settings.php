<?php if(!defined('ABSPATH'))exit;use GEO_Forge\Install\Installer;use GEO_Forge\WellKnown\LlmsTxt;use GEO_Forge\WellKnown\SecurityTxt;use GEO_Forge\WellKnown\RobotsTxt;
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
	<div class="gf-card">
		<div class="gf-card-title">llms.txt</div>
		<div class="gf-muted" style="margin-bottom:8px;">Served at <code><?php echo esc_html($lu);?></code></div>
		<div style="display:flex;gap:8px;margin-bottom:8px;">
			<button type="button" id="geo-forge-save-llms" class="gf-btn gf-btn-primary">Save</button>
			<button type="button" id="geo-forge-regen-llms" class="gf-btn">Regenerate</button>
			<span class="gf-muted"><?php echo strlen($lc);?> bytes</span>
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
	<div class="gf-card"><div class="gf-card-title">About GEO Forge</div><p>Transforms your WooCommerce store from "AI-blind" to "Agent-Ready" — connecting to the GEO KAMI API to audit 22+ AI-readiness checks, then auto-deploying fixes so ChatGPT, Claude, Perplexity, and Google AI can discover your products.</p></div>
	<div class="gf-card"><div class="gf-card-title">What We Scan</div>
		<div class="gf-grid gf-grid-3"><div class="gf-card" style="text-align:center;"><div style="font-size:32px;">🔍</div><div class="gf-stat">7</div><div class="gf-muted">Categories</div></div><div class="gf-card" style="text-align:center;"><div style="font-size:32px;">✅</div><div class="gf-stat">22+</div><div class="gf-muted">Checks</div></div><div class="gf-card" style="text-align:center;"><div style="font-size:32px;">🔧</div><div class="gf-stat">5</div><div class="gf-muted">Auto-fixes</div></div></div>
	</div>
	<div class="gf-card"><div class="gf-card-title">Scan Categories</div>
		<table><tr><td style="font-weight:600;">AI Readability</td><td>How well AI agents parse your structure</td><td class="gf-muted">llms.txt, structured data</td></tr>
		<tr><td style="font-weight:600;">Discoverability</td><td>How easily AI search finds your products</td><td class="gf-muted">robots.txt, sitemap</td></tr>
		<tr><td style="font-weight:600;">Content Accessibility</td><td>Can AI read key pages (About, FAQ)</td><td class="gf-muted">Markdown variants</td></tr>
		<tr><td style="font-weight:600;">Bot Access Control</td><td>AI bot permissions and rules</td><td class="gf-muted">robots.txt AI sections</td></tr>
		<tr><td style="font-weight:600;">Security & UX</td><td>HTTPS, headers, privacy</td><td class="gf-muted">security.txt, HSTS</td></tr>
		<tr><td style="font-weight:600;">Protocol Discovery</td><td>MCP, A2A, OpenAPI endpoints</td><td class="gf-muted">well-known routes</td></tr>
		<tr><td style="font-weight:600;">Commerce</td><td>Product data for AI shopping</td><td class="gf-muted">aggregateRating</td></tr></table>
	</div>
</div>
</div>
<script>
(function(){
document.querySelectorAll('.gf-tab').forEach(function(t){t.addEventListener('click',function(){document.querySelectorAll('.gf-tab').forEach(function(x){x.classList.remove('active');});t.classList.add('active');document.querySelectorAll('.gf-tab-content').forEach(function(c){c.classList.remove('active');});document.getElementById(t.dataset.tab).classList.add('active');});});
function ed(id,r,ta,p){var s=document.getElementById(id),b=document.getElementById(r),a=document.getElementById(ta);if(!s||!a)return;s.addEventListener('click',function(){s.disabled=true;fetch(window.GeoForgeSettings.restRoot+'well-known/'+p,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeSettings.restNonce,'Content-Type':'application/json'},body:JSON.stringify({content:a.value})}).finally(function(){s.disabled=false;});});if(b)b.addEventListener('click',function(){b.disabled=true;fetch(window.GeoForgeSettings.restRoot+'well-known/'+p+'/regenerate',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeSettings.restNonce,'Content-Type':'application/json'}}).then(function(r){return r.json();}).then(function(b){if(b&&b.success)a.value=b.content;}).finally(function(){b.disabled=false;});});}
ed('geo-forge-save-llms','geo-forge-regen-llms','geo-forge-llms-content','llms-txt');
})();
</script>