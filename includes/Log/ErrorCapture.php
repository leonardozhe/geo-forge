<?php
/**
 * Fatal-error capture.
 *
 * Registers a PHP shutdown function that, on fatal error, logs a critical
 * entry — but ONLY if the fatal happened inside our plugin's directory.
 *
 * Why not set_error_handler() too?
 *   Overriding the global error handler is hostile — it breaks other plugins
 *   and WP itself. We only need the shutdown hook, which is additive and
 *   cannot conflict with anyone else.
 *
 * Why filter by plugin directory?
 *   Other plugins (or WP core) fatals are not our problem to log. We'd just
 *   be noise in their debugging. Stay in our lane.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ErrorCapture {

	private static bool $registered = false;

	/** PHP error constants that count as fatal (script-terminating). */
	private const FATAL_TYPES = array(
		E_ERROR,
		E_PARSE,
		E_CORE_ERROR,
		E_COMPILE_ERROR,
		E_USER_ERROR,
	);

	public static function register(): void {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;

		register_shutdown_function( array( self::class, 'on_shutdown' ) );
	}

	/**
	 * Shutdown callback. Called by PHP after the script ends — including
	 * after a fatal. We check error_get_last() and log if it's one of ours.
	 *
	 * @internal Public only because register_shutdown_function requires it.
	 */
	public static function on_shutdown(): void {
		$error = error_get_last();
		if ( ! $error ) {
			return;
		}
		if ( ! in_array( $error['type'], self::FATAL_TYPES, true ) ) {
			return;
		}

		// Only log fatals that happened inside our plugin directory.
		$file = (string) $error['file'];
		if ( ! str_contains( $file, 'geo-forge' ) ) {
			return;
		}

		// Logger itself must not throw here — swallow exceptions to avoid
		// masking the original fatal.
		try {
			Logger::critical(
				(string) $error['message'],
				array(
					'source' => 'php_fatal',
					'type'   => $error['type'],
					'file'   => $file,
					'line'   => $error['line'],
				)
			);
		} catch ( \Throwable $e ) {
			// Last-ditch: write to PHP's error log if DB is unavailable.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GEO Forge] Logger failed during fatal capture: ' . $e->getMessage() );
		}
	}
}
