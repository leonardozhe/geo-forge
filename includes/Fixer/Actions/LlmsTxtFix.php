<?php
/**
 * Fix action: llms.txt.
 *
 * Wraps the existing WellKnown\LlmsTxt generator. Apply = regenerate + save.
 * Rollback = delete the stored option (the virtual route returns an empty
 * spec-valid document in that case — see LlmsTxt::serve()).
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LlmsTxtFix implements FixInterface {

	public function get_id(): string {
		return 'llms_txt';
	}

	public function get_label(): string {
		return __( 'Generate / update llms.txt', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Create /llms.txt from current store data so AI agents can discover your products.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'none'; // writes to a virtual route, doesn't touch existing content
	}

	public function get_priority(): int {
		return 1;
	}

	public function get_check_ids(): array {
		return array( 'llms_txt', 'llms_txt_quality' );
	}

	public function get_status(): string {
		return '' === LlmsTxt::get_current() ? 'pending' : 'applied';
	}

	public function apply(): array {
		$content = LlmsTxt::regenerate();

		return array(
			'success'      => true,
			'message'      => __( 'llms.txt generated and saved.', 'geo-forge' ),
			'score_change' => 7, // Matches design doc estimate; GEO KAMI will confirm on verify.
		);
	}

	public function rollback(): array {
		delete_option( 'geo_forge_llms_txt' );
		return array(
			'success' => true,
			'message' => __( 'llms.txt removed.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array(); // handled by Fixer engine via /verify API
	}
}
