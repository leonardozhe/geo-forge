<?php
/**
 * Fix action: llms.txt.
 *
 * Wraps the existing WellKnown\LlmsTxt generator. Apply = regenerate + save.
 * Rollback = delete the stored options (the virtual route returns a minimal
 * placeholder document in that case — see LlmsTxt::serve()).
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Compat\SeoDetector;
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
		if ( 'geo-forge' !== SeoDetector::llms_txt_owner() ) {
			return 'covered';
		}
		return '' === LlmsTxt::get_current() ? 'pending' : 'applied';
	}

	public function apply(): array {
		if ( 'geo-forge' !== SeoDetector::llms_txt_owner() ) {
			return array(
				'success' => true,
				'status'  => 'covered',
				'message' => sprintf(
					/* translators: %s: owner label */
					__( 'llms.txt is managed by %s — GEO Forge audits only and did not overwrite it.', 'geo-forge' ),
					SeoDetector::owner_label( SeoDetector::llms_txt_owner() )
				),
			);
		}

		$content = LlmsTxt::regenerate_all();

		return array(
			'success'      => true,
			'message'      => __( 'llms.txt generated and saved.', 'geo-forge' ),
			'score_change' => 7, // Matches design doc estimate; GEO KAMI will confirm on verify.
		);
	}

	public function rollback(): array {
		if ( 'geo-forge' !== SeoDetector::llms_txt_owner() ) {
			return array(
				'success' => true,
				'message' => __( 'llms.txt is managed elsewhere — nothing to roll back (audit mode).', 'geo-forge' ),
			);
		}

		delete_option( 'geo_forge_llms_txt' );
		delete_option( 'geo_forge_llms_full_txt' );
		delete_option( 'geo_forge_llms_txt_source' );
		return array(
			'success' => true,
			'message' => __( 'llms.txt removed.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array(); // handled by Fixer engine via /verify API
	}
}
