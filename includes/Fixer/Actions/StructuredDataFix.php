<?php
/**
 * Fix action: Structured Data enhancement.
 *
 * Adds aggregateRating Schema to product pages with reviews.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\StructuredData;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StructuredDataFix implements FixInterface {

	public function get_id(): string {
		return 'structured_data';
	}

	public function get_label(): string {
		return __( 'Enhance structured data (aggregateRating)', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Add aggregateRating Schema markup to product pages with customer reviews.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'none';
	}

	public function get_priority(): int {
		return 1;
	}

	public function get_check_ids(): array {
		return array( 'structured_data', 'aggregate_rating' );
	}

	public function get_status(): string {
		return StructuredData::is_enabled() ? 'applied' : 'pending';
	}

	public function apply(): array {
		StructuredData::enable();

		return array(
			'success'      => true,
			'message'      => __( 'Structured data enhancement enabled.', 'geo-forge' ),
			'score_change' => 5,
		);
	}

	public function rollback(): array {
		StructuredData::disable();
		return array(
			'success' => true,
			'message' => __( 'Structured data enhancement disabled.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array();
	}
}
