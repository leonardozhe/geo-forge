<?php
/**
 * Fix action: Content Signals meta tags.
 *
 * Adds AI-readable meta tags to every page.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\ContentSignals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentSignalsFix implements FixInterface {

	public function get_id(): string {
		return 'content_signals';
	}

	public function get_label(): string {
		return __( 'Add Content Signals meta tags', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Inject AI-readable meta tags (geo_forge:ai_ready, scan_version, last_scan) into every page.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'none';
	}

	public function get_priority(): int {
		return 1;
	}

	public function get_check_ids(): array {
		return array( 'content_signals' );
	}

	public function get_status(): string {
		return ContentSignals::is_enabled() ? 'applied' : 'pending';
	}

	public function apply(): array {
		ContentSignals::enable();

		return array(
			'success'      => true,
			'message'      => __( 'Content Signals meta tags enabled.', 'geo-forge' ),
			'score_change' => 5,
		);
	}

	public function rollback(): array {
		ContentSignals::disable();
		return array(
			'success' => true,
			'message' => __( 'Content Signals meta tags disabled.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array();
	}
}
