<?php
/**
 * WP-CLI rollback command for GEO Forge fixes.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Cli;

use GEO_Forge\GeoForge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RollbackCommand {

	/**
	 * Register the CLI command when WP-CLI is running.
	 */
	public static function register(): void {
		if ( ! class_exists( '\\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command( 'geo-forge rollback', array( self::class, 'rollback' ) );
	}

	/**
	 * Roll back one fix.
	 *
	 * ## EXAMPLES
	 *
	 *     wp geo-forge rollback robots_txt
	 *
	 * @param array<int,string>   $args       Positional arguments.
	 * @param array<string,mixed> $assoc_args Associative arguments.
	 */
	public static function rollback( array $args, array $assoc_args ): void {
		$fix_id = isset( $args[0] ) ? sanitize_key( (string) $args[0] ) : '';

		if ( '' === $fix_id ) {
			\WP_CLI::error( 'Usage: wp geo-forge rollback <fix-id>' );
		}

		$fixer = GeoForge::fixer();
		if ( null === $fixer ) {
			\WP_CLI::error( 'GEO Forge fixer is not initialized.' );
		}

		$result = $fixer->rollback( $fix_id );
		if ( empty( $result['success'] ) ) {
			\WP_CLI::error( (string) ( $result['message'] ?? 'Rollback failed.' ) );
		}

		\WP_CLI::success( (string) ( $result['message'] ?? 'Rollback complete.' ) );
	}
}
