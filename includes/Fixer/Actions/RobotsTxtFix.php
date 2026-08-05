<?php
/**
 * Fix action: Robots.txt AI bot rules.
 *
 * Adds explicit Allow rules for AI crawlers to robots.txt.
 *
 * When a physical file or Rank Math's custom robots content owns robots.txt,
 * the fix enters "covered" mode: Audit checks for global/AI-bot Disallow
 * rules, Cover (override) backs up + removes a physical file or force-
 * registers the append, and Ignore accepts the status quo.
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

	private const AUDIT_OPTION    = 'geo_forge_audit_robots_txt';
	private const OVERRIDE_OPTION = 'geo_forge_override_robots_txt';
	private const IGNORE_OPTION   = 'geo_forge_ignored_robots_txt';
	private const PHYSICAL_FILE   = 'robots.txt';

	/** AI crawlers GEO Forge explicitly wants allowed. */
	private const AI_USER_AGENTS = array(
		'GPTBot',
		'ChatGPT-User',
		'ClaudeBot',
		'Claude-Web',
		'PerplexityBot',
		'anthropic-ai',
		'Google-Extended',
		'CCBot',
		'Amazonbot',
		'Bytespider',
		'Applebot-Extended',
	);

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
		if ( 'yes' === get_option( self::IGNORE_OPTION, '' ) ) {
			return 'ignored';
		}

		$owner = SeoDetector::robots_txt_owner();
		if ( 'geo-forge' !== $owner && 'yes' !== get_option( self::OVERRIDE_OPTION, '' ) ) {
			return 'covered';
		}

		return '' === RobotsTxt::get_current() ? 'pending' : 'applied';
	}

	public function apply(): array {
		if ( 'covered' === $this->get_status() ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: owner label */
					__( 'robots.txt is managed by %s — run Audit first, then Cover to override.', 'geo-forge' ),
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
		delete_option( self::OVERRIDE_OPTION );
		delete_option( self::IGNORE_OPTION );
		delete_option( self::AUDIT_OPTION );

		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			$backup = ABSPATH . self::PHYSICAL_FILE . '.geo-forge-backup';
			if ( file_exists( $backup ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rename
				rename( $backup, ABSPATH . self::PHYSICAL_FILE );
			}
			return array(
				'success' => true,
				'message' => __( 'Override removed — ownership returned to the original provider.', 'geo-forge' ),
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

	/**
	 * Audit the externally-managed /robots.txt: no global Disallow, and no
	 * known AI crawler explicitly blocked.
	 */
	public function audit(): array {
		$owner = SeoDetector::robots_txt_owner();
		if ( 'geo-forge' === $owner ) {
			$result = array(
				'pass'    => true,
				'message' => __( 'robots.txt AI rules are already managed by GEO Forge.', 'geo-forge' ),
			);
			$this->store_audit( $result );
			return array( 'success' => true ) + $result;
		}

		$content = $this->fetch_owned_content( $owner );
		if ( null === $content ) {
			$result = array(
				'pass'    => false,
				'message' => __( 'Could not read the robots.txt currently being served — audit inconclusive.', 'geo-forge' ),
			);
			$this->store_audit( $result );
			return array( 'success' => true ) + $result;
		}

		$failures = array();

		// Global "Disallow: /" blocks everything, including AI crawlers.
		if ( preg_match( '/User-agent:\s*\*\s*[\r\n]+Disallow:\s*\/\s*[\r\n]+/i', $content ) ) {
			$failures[] = 'global Disallow: /';
		}

		foreach ( self::AI_USER_AGENTS as $ua ) {
			if ( preg_match( '/User-agent:\s*' . preg_quote( $ua, '/' ) . '\s*[\r\n]+Disallow:\s*\/\s*[\r\n]*/i', $content ) ) {
				$failures[] = $ua . ' blocked';
			}
		}

		$result = array(
			'pass'    => empty( $failures ),
			'message' => empty( $failures )
				? __( 'No AI crawler is blocked — robots.txt meets the requirement.', 'geo-forge' )
				/* translators: %s: list of issues */
				: sprintf( __( 'Below GEO Forge standard — found: %s.', 'geo-forge' ), implode( ', ', $failures ) ),
		);
		$this->store_audit( $result );
		return array( 'success' => true ) + $result;
	}

	/**
	 * Take over robots.txt. Backs up + removes a physical file, force-
	 * registers the append, and regenerates the AI rules.
	 */
	public function cover(): array {
		$owner = SeoDetector::robots_txt_owner();

		if ( 'physical' === $owner ) {
			$file = ABSPATH . self::PHYSICAL_FILE;
			if ( file_exists( $file ) ) {
				if ( ! is_writable( $file ) ) {
					return array(
						'success' => false,
						'message' => __( 'The physical robots.txt is not writable — remove it via FTP/panel first, then run Cover again.', 'geo-forge' ),
					);
				}
				$backup = $file . '.geo-forge-backup';
				if ( ! file_exists( $backup ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
					copy( $file, $backup );
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink
				unlink( $file );
			}
		}

		update_option( self::OVERRIDE_OPTION, 'yes', false );
		delete_option( self::IGNORE_OPTION );
		RobotsTxt::regenerate();

		return array(
			'success' => true,
			'status'  => 'applied',
			'message' => 'physical' === $owner
				? __( 'Physical robots.txt backed up and removed — AI rules now appended by GEO Forge.', 'geo-forge' )
				: __( 'GEO Forge now appends AI bot rules (override).', 'geo-forge' ),
		);
	}

	/**
	 * Accept the external output — GEO Forge will not touch robots.txt.
	 */
	public function ignore(): array {
		update_option( self::IGNORE_OPTION, 'yes', false );
		return array(
			'success' => true,
			'status'  => 'ignored',
			'message' => __( 'Ignored — GEO Forge will not touch the robots.txt managed elsewhere.', 'geo-forge' ),
		);
	}

	/* =================================================================
	 * Helpers
	 * ================================================================ */

	private function store_audit( array $result ): void {
		update_option( self::AUDIT_OPTION, array(
			'pass'       => (bool) $result['pass'],
			'message'    => (string) $result['message'],
			'checked_at' => current_time( 'mysql' ),
		), false );
	}

	private function fetch_owned_content( string $owner ): ?string {
		if ( 'physical' === $owner ) {
			$file = ABSPATH . self::PHYSICAL_FILE;
			if ( file_exists( $file ) && is_readable( $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
				return (string) file_get_contents( $file );
			}
			return null;
		}

		$resp = wp_remote_get( home_url( '/' . self::PHYSICAL_FILE ), array(
			'timeout'    => 10,
			'redirection' => 3,
		) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return null;
		}
		return (string) wp_remote_retrieve_body( $resp );
	}
}
