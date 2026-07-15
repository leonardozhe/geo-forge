<?php
/**
 * llms.txt Editor — unified compact design.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use GEO_Forge\WellKnown\LlmsTxt;
$content = LlmsTxt::get_current();
$live_url = home_url('/llms.txt');
?>
<div class="geo-forge-wrap">
	<div class="geo-forge-header" style="margin-bottom:14px;">
		<h1>GEO Forge <span class="geo-forge-subtitle">llms.txt Editor</span></h1>
		<p class="geo-forge-muted">Served at <code><a href="<?php echo esc_url($live_url);?>" target="_blank"><?php echo esc_html($live_url);?></a></code> — save and AI agents see it immediately.</p>
	</div>

	<div id="geo-forge-editor-status" class="geo-forge-notice" style="display:none;"><p></p></div>

	<div class="geo-forge-card">
		<div class="geo-forge-actions" style="margin-bottom:10px;">
			<button type="button" id="geo-forge-save-llms" class="pure-button pure-button-primary">Save</button>
			<button type="button" id="geo-forge-regen-llms" class="pure-button">Regenerate</button>
			<span class="geo-forge-muted"><?php echo strlen($content);?> bytes</span>
		</div>
		<textarea id="geo-forge-llms-content" class="geo-forge-editor" rows="24" spellcheck="false"><?php echo esc_textarea($content);?></textarea>
	</div>
</div>
