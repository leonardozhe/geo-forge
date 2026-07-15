<?php
/**
 * Settings — API + Content Generation + About
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\Install\Installer;
use GEO_Forge\WellKnown\LlmsTxt;
use GEO_Forge\WellKnown\SecurityTxt;
use GEO_Forge\WellKnown\RobotsTxt;

$form_action = 'geo_forge_save_settings';
$nonce_field = 'geo_forge_settings_nonce';
$api_key  = (string) Installer::get_setting( 'api_key', '' );
$api_base = (string) Installer::get_setting( 'api_base', 'https://api.geokami.com' );

$llms_content     = LlmsTxt::get_current();
$security_content = SecurityTxt::get_current();
$robots_content   = RobotsTxt::get_current();
$live_url         = home_url('/llms.txt');
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header">
		<h1>Settings <span class="geo-forge-subtitle">GEO Forge configuration</span></h1>
	</div>

	<div class="geo-forge-tabs">
		<button class="geo-forge-tab active" data-tab="tab-api">API</button>
		<button class="geo-forge-tab" data-tab="tab-content">Content</button>
		<button class="geo-forge-tab" data-tab="tab-about">About</button>
	</div>

	<!-- API Tab -->
	<div class="geo-forge-tab-content active" id="tab-api">
		<?php if ( ! Installer::get_setting( 'api_key', '' ) ) : ?>
		<div class="geo-forge-card geo-forge-promo">
			<h2>🔑 Get Your GEO KAMI API Key</h2>
			<p>Sign up for free and get 100 points (5 comprehensive scans).</p>
			<ol class="geo-forge-steps">
				<li><a href="https://geokami.com/register?ref=geo-forge" target="_blank">Create a free GEO KAMI account</a></li>
				<li>Verify your email → Dashboard → API Keys → Copy key</li>
				<li>Paste below and save</li>
			</ol>
			<div class="geo-forge-promo-cta" style="margin-top:12px;">
				<a href="https://geokami.com/register?ref=geo-forge" target="_blank" class="pure-button pure-button-primary">Register for Free</a>
			</div>
		</div>
		<?php endif; ?>

		<?php settings_errors( 'geo_forge' ); ?>

		<div class="geo-forge-card">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pure-form pure-form-stacked">
				<?php wp_nonce_field( $form_action, $nonce_field ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>" />
				<fieldset>
					<div class="pure-control-group">
						<label for="geo_forge_api_key">GEO KAMI API Key</label>
						<div style="display:flex;align-items:center;gap:8px;">
							<input type="password" id="geo_forge_api_key" name="geo_forge_api_key" value="<?php echo esc_attr($api_key);?>" autocomplete="off" spellcheck="false" placeholder="gk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="flex:1;" />
							<button type="button" id="geo-forge-health-btn" class="pure-button">Health Check</button>
							<span id="geo-forge-health-status" aria-live="polite" style="font-size:13px;white-space:nowrap;"></span>
						</div>
						<span class="pure-form-message">35-character API key starting with gk_.</span>
					</div>
					<div class="pure-control-group">
						<label for="geo_forge_api_base">API Base URL</label>
						<input type="url" id="geo_forge_api_base" name="geo_forge_api_base" value="<?php echo esc_attr($api_base);?>" />
						<span class="pure-form-message">Default: https://api.geokami.com</span>
					</div>
					<button type="submit" class="pure-button pure-button-primary">Save Settings</button>
				</fieldset>
			</form>
		</div>
	</div>

	<!-- Content Tab -->
	<div class="geo-forge-tab-content" id="tab-content">
		<div class="geo-forge-card">
			<h2>llms.txt</h2>
			<p class="geo-forge-muted">AI-readable store summary. Served at <code><?php echo esc_html($live_url);?></code></p>
			<div style="display:flex;gap:8px;margin-bottom:12px;">
				<button type="button" id="geo-forge-save-llms" class="pure-button pure-button-primary">Save</button>
				<button type="button" id="geo-forge-regen-llms" class="pure-button">Regenerate</button>
				<span class="geo-forge-muted"><?php echo strlen($llms_content);?> bytes</span>
			</div>
			<textarea id="geo-forge-llms-content" class="geo-forge-editor" rows="16" spellcheck="false"><?php echo esc_textarea($llms_content);?></textarea>
		</div>
		<div class="geo-forge-card">
			<h2>security.txt</h2>
			<p class="geo-forge-muted">RFC 9116 security contact. Served at <code>/.well-known/security.txt</code></p>
			<div style="display:flex;gap:8px;margin-bottom:12px;">
				<button type="button" id="geo-forge-save-security" class="pure-button pure-button-primary">Save</button>
				<button type="button" id="geo-forge-regen-security" class="pure-button">Regenerate</button>
			</div>
			<textarea id="geo-forge-security-content" class="geo-forge-editor" rows="8" spellcheck="false"><?php echo esc_textarea($security_content);?></textarea>
		</div>
		<div class="geo-forge-card">
			<h2>AI Bot Rules (robots.txt)</h2>
			<p class="geo-forge-muted">Allow AI crawlers to index your store.</p>
			<div style="display:flex;gap:8px;margin-bottom:12px;">
				<button type="button" id="geo-forge-save-robots" class="pure-button pure-button-primary">Save</button>
				<button type="button" id="geo-forge-regen-robots" class="pure-button">Regenerate</button>
			</div>
			<textarea id="geo-forge-robots-content" class="geo-forge-editor" rows="12" spellcheck="false"><?php echo esc_textarea($robots_content);?></textarea>
		</div>
	</div>

	<!-- About Tab -->
	<div class="geo-forge-tab-content" id="tab-about">
		<div class="geo-forge-card">
			<h2>About GEO Forge</h2>
			<p>GEO Forge transforms your WooCommerce store from "AI-blind" to "Agent-Ready". It connects to the GEO KAMI Cloud API to audit your site across 22+ AI-readiness checks, then auto-deploys fixes so AI agents like ChatGPT, Claude, Perplexity, and Google AI can discover and understand your products.</p>
		</div>
		<div class="geo-forge-card">
			<h2>What We Scan</h2>
			<div class="pure-g">
				<div class="pure-u-1 pure-u-md-1-3"><div class="geo-forge-about-item">
					<div style="font-size:32px;">🔍</div><div class="geo-forge-stat-sm">7</div><p>Categories</p>
				</div></div>
				<div class="pure-u-1 pure-u-md-1-3"><div class="geo-forge-about-item">
					<div style="font-size:32px;">✅</div><div class="geo-forge-stat-sm">22+</div><p>Checks</p>
				</div></div>
				<div class="pure-u-1 pure-u-md-1-3"><div class="geo-forge-about-item">
					<div style="font-size:32px;">🔧</div><div class="geo-forge-stat-sm">5</div><p>Auto-fixes</p>
				</div></div>
			</div>
		</div>
		<div class="geo-forge-card">
			<h2>Categories</h2>
			<table class="pure-table">
				<tr><td style="font-weight:600;">AI Readability</td><td>How well AI agents can parse your store's structure and content</td><td class="geo-forge-muted">llms.txt, structured data</td></tr>
				<tr><td style="font-weight:600;">Discoverability</td><td>How easily AI search engines can find and index your products</td><td class="geo-forge-muted">robots.txt, sitemap</td></tr>
				<tr><td style="font-weight:600;">Content Accessibility</td><td>Whether AI agents can read your key pages (About, Contact, FAQ)</td><td class="geo-forge-muted">Markdown variants, page structure</td></tr>
				<tr><td style="font-weight:600;">Bot Access Control</td><td>AI bot permissions and crawling rules</td><td class="geo-forge-muted">robots.txt AI sections</td></tr>
				<tr><td style="font-weight:600;">Security & UX</td><td>HTTPS, headers, error pages, privacy compliance</td><td class="geo-forge-muted">security.txt, HSTS, CSP</td></tr>
				<tr><td style="font-weight:600;">Protocol Discovery</td><td>MCP, A2A, OpenAPI, and other AI protocol endpoints</td><td class="geo-forge-muted">well-known routes, agent cards</td></tr>
				<tr><td style="font-weight:600;">Commerce</td><td>Product data, reviews, pricing signals for AI shopping agents</td><td class="geo-forge-muted">aggregateRating, structured data</td></tr>
			</table>
		</div>
	</div>
</div>

<script>
(function(){
var tabs=document.querySelectorAll('.geo-forge-tab');
tabs.forEach(function(t){t.addEventListener('click',function(){
tabs.forEach(function(x){x.classList.remove('active');});
t.classList.add('active');
document.querySelectorAll('.geo-forge-tab-content').forEach(function(c){c.classList.remove('active');});
document.getElementById(t.dataset.tab).classList.add('active');
});});

// llms.txt editor buttons
function setupEditor(saveId,regenId,textareaId,restPath){
var s=document.getElementById(saveId),r=document.getElementById(regenId),ta=document.getElementById(textareaId);
if(!s||!ta)return;
s.addEventListener('click',function(){s.disabled=true;
fetch(window.GeoForgeSettings.restRoot+'well-known/'+restPath,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeSettings.restNonce,'Content-Type':'application/json'},body:JSON.stringify({content:ta.value})})
.then(function(r){return r.json();}).then(function(b){if(!b.success)alert(b.error?.message||'Failed');}).finally(function(){s.disabled=false;});});
if(r)r.addEventListener('click',function(){r.disabled=true;
fetch(window.GeoForgeSettings.restRoot+'well-known/'+restPath+'/regenerate',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeSettings.restNonce,'Content-Type':'application/json'}})
.then(function(r){return r.json();}).then(function(b){if(b.success)ta.value=b.content;}).finally(function(){r.disabled=false;});});
}
setupEditor('geo-forge-save-llms','geo-forge-regen-llms','geo-forge-llms-content','llms-txt');
// Security txt uses manual save only (no separate regenerate endpoint yet)
var ss=document.getElementById('geo-forge-save-security'),rs=document.getElementById('geo-forge-regen-security'),st=document.getElementById('geo-forge-security-content');
if(ss&&st)ss.addEventListener('click',function(){ss.disabled=true;SecurityTxt.save(st.value);setTimeout(function(){ss.disabled=false;},500);});
if(rs&&st)rs.addEventListener('click',function(){rs.disabled=true;SecurityTxt.regenerate().then(function(c){st.value=c;});setTimeout(function(){rs.disabled=false;},500);});
// Robots txt
var sb=document.getElementById('geo-forge-save-robots'),rb=document.getElementById('geo-forge-regen-robots'),rt=document.getElementById('geo-forge-robots-content');
if(sb&&rt)sb.addEventListener('click',function(){sb.disabled=true;fetch(window.GeoForgeSettings.restRoot+'well-known/robots-txt',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':window.GeoForgeSettings.restNonce,'Content-Type':'application/json'},body:JSON.stringify({content:rt.value})}).finally(function(){sb.disabled=false;});});
})();
</script>
