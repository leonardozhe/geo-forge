<?php
/**
 * Best-effort SaaS reporter for fix action timeline events.
 *
 * Reporting is deliberately isolated from the fix operation: transport or
 * logger failures must never change the result of the local fix.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer;

use GEO_Forge\Api\Client;
use GEO_Forge\Log\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityReporter {

	/**
	 * Report one fix action without throwing into the fix execution path.
	 *
	 * The optional callables are test seams. Production callers use the
	 * default API transport and structured logger.
	 *
	 * @param array<string,mixed> $context Event context supplied by Fixer.
	 * @param callable|null        $transport Test transport.
	 * @param callable|null        $failure_logger Test failure observer.
	 */
	public static function report(
		string $fix_id,
		string $action,
		string $status,
		array $context = array(),
		?callable $transport = null,
		?callable $failure_logger = null
	): bool {
		$event = array();

		try {
			$event = array(
				'event_id'    => bin2hex( random_bytes( 16 ) ),
				'event'       => 'fix_action',
				'fix_id'      => $fix_id,
				'action'      => $action,
				'status'      => $status,
				'occurred_at' => gmdate( 'c' ),
			);
			$event = array_merge( $event, $context );

			if ( null === $transport ) {
				$transport = static function ( array $payload ): void {
					( new Client( '', '', 5, 1 ) )->report_fix_event( $payload );
				};
			}

			$transport( $event );
			return true;
		} catch ( \Throwable $e ) {
			$failure = array(
				'fix_id'    => $fix_id,
				'action'    => $action,
				'status'    => $status,
				'exception' => get_class( $e ),
				'message'   => $e->getMessage(),
			);

			try {
				if ( null !== $failure_logger ) {
					$failure_logger( $event, $e );
				} else {
					Logger::warning( 'Fix activity report failed.', $failure );
				}
			} catch ( \Throwable $log_error ) {
				// Last-resort observability if the local logger itself fails.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'GEO Forge fix activity report failed: ' . $e->getMessage() );
			}

			return false;
		}
	}
}
