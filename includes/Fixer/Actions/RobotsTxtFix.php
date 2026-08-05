<?php
/**
 * Fix action: Robots.txt AI bot rules.
 *
 * Adds explicit Allow rules for AI crawlers to robots.txt.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer\Actions;

use GEO_Forge\Compat\SeoDetector;
use GEO_Forge\Fixer\FixInterface;
use GEO_Forge\WellKnown\RobotsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RobotsTxtFix implements FixInterface {

	public function get_id(): string {
		return 'robots_txt';
	}

	public function get_label(): string {
		return __( 'Add AI bot rules to robots.txt', 'geo-forge' );
	}

	public function get_description(): string {
		return __( 'Allow AI crawlers (GPTBot, ClaudeBot, PerplexityBot, etc.) to index your content.', 'geo-forge' );
	}

	public function get_risk_level(): string {
		return 'low'; // modifies the public robots.txt output
	}

	public function get_priority(): int {
		return 1;
	}

	public function get_check_ids(): array {
		return array( 'robots_txt_ai_rules' );
	}

	public function get_status(): string {
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			return 'covered';
		}
		return '' === RobotsTxt::get_current() ? 'pending' : 'applied';
	}

	public function apply(): array {
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			return array(
				'success' => true,
				'status'  => 'covered',
				'message' => sprintf(
					/* translators: %s: owner label */
					__( 'robots.txt is managed by %s — GEO Forge audits only and did not modify it.', 'geo-forge' ),
					SeoDetector::owner_label( SeoDetector::robots_txt_owner() )
				),
			);
		}

		RobotsTxt::regenerate();

		return array(
			'success'      => true,
			'message'      => __( 'AI bot rules added to robots.txt.', 'geo-forge' ),
			'score_change' => 6,
		);
	}

	public function rollback(): array {
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			return array(
				'success' => true,
				'message' => __( 'robots.txt is managed elsewhere — nothing to roll back (audit mode).', 'geo-forge' ),
			);
		}

		RobotsTxt::rollback();
		return array(
			'success' => true,
			'message' => __( 'AI bot rules removed from robots.txt.', 'geo-forge' ),
		);
	}

	public function verify(): array {
		return array();
	}
}
