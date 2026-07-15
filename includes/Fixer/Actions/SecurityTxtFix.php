<?php
/**
 * Fix action: security.txt.
 *
 * Wraps the WellKnown\SecurityTxt generator. Apply = regenerate + save.
 * Rollback = delete option (Router returns a minimal RFC-valid document
 * even without the option, so the site stays compliant).
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\SecurityTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SecurityTxtFix implements FixInterface {

	public function get_id(): string {
		return 'security_txt';
	}

	public function get_label(): string {
		return __( 'Deploy /.well-known/security.txt', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Publish an RFC 9116 security.txt with contact, expiry, canonical, and language info.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'none';
	}

	public function get_priority(): int {
		return 1;
	}

	public function get_check_ids(): array {
		return array( 'security_txt' );
	}

	public function get_status(): string {
		return '' === SecurityTxt::get_current() ? 'pending' : 'applied';
	}

	public function apply(): array {
		SecurityTxt::regenerate();

		return array(
			'success'      => true,
			'message'      => __( 'security.txt deployed.', 'geo-forge' ),
			'score_change' => 8, // Matches design doc estimate.
		);
	}

	public function rollback(): array {
		delete_option( 'geo_forge_security_txt' );
		return array(
			'success' => true,
			'message' => __( 'security.txt removed.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array();
	}
}
