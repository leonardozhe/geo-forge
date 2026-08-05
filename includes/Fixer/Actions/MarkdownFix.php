<?php
/**
 * Fix action: Markdown negotiation for AI agents.
 *
 * Wraps the WellKnown\Markdown generator. Apply = enable (the plugin answers
 * Accept: text/markdown and serves /{slug}.md variants); Rollback = disable.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\Markdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MarkdownFix implements FixInterface {

	public function get_id(): string {
		return 'markdown_negotiation';
	}

	public function get_label(): string {
		return __( 'Serve markdown to AI agents', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Answer Accept: text/markdown requests and serve /{slug}.md variants so AI agents get clean markdown instead of HTML.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'low';
	}

	public function get_priority(): int {
		return 2;
	}

	public function get_check_ids(): array {
		return array( 'markdown_negotiation', 'md_variants' );
	}

	public function get_status(): string {
		return Markdown::is_enabled() ? 'applied' : 'pending';
	}

	public function apply(): array {
		Markdown::enable();

		return array(
			'success'      => true,
			'message'      => __( 'Markdown negotiation enabled — AI agents get markdown versions of products and pages.', 'geo-forge' ),
			'score_change' => 10,
		);
	}

	public function rollback(): array {
		Markdown::disable();
		return array(
			'success' => true,
			'message' => __( 'Markdown negotiation disabled.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array();
	}
}
