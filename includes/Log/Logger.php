<?php
/**
 * Structured logger.
 *
 * Writes to a custom table (`wp_geo_forge_logs`). Designed to be called
 * frequently without killing performance:
 *   - Uses a single INSERT per log call (cheap).
 *   - Lazy pruning: ~1% of the time, delete entries older than the
 *     retention window. Avoids needing a cron job in v1.
 *
 * Redaction:
 *   - `api_key` context key is always redacted before storage.
 *   - Bearer tokens in `request_headers` are masked.
 *   - Caller-provided context is JSON-encoded verbatim otherwise —
 *     don't log secrets (PII, customer data).
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	private const TABLE_SUFFIX = 'geo_forge_logs';
	private const RETENTION_DAYS = 30;

	/** Keys whose values must never be stored in plaintext. */
	private const REDACT_KEYS = array( 'api_key', 'password', 'secret', 'authorization', 'cookie' );

	/**
	 * Log at a specific level.
	 *
	 * @param string            $message Free-form message. Keep short — UI has limited width.
	 * @param array<string,mixed> $context Arbitrary context. JSON-encoded on write.
	 */
	public static function log( Level $level, string $message, array $context = array() ): void {
		global $wpdb;

		$min_level = self::get_min_level();
		if ( $level->priority() < $min_level->priority() ) {
			return;
		}

		$row = array(
			'level'      => $level->value,
			'message'    => mb_substr( $message, 0, 500 ),
			'context'    => wp_json_encode( self::redact( $context ) ),
			'source'     => self::guess_source(),
			'request_id' => self::request_id(),
			'created_at' => current_time( 'mysql', true ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $wpdb->prefix . self::TABLE_SUFFIX, $row );

		// Lazy pruning — ~1% of writes trigger a cleanup.
		if ( mt_rand( 1, 100 ) === 1 ) {
			self::prune();
		}
	}

	/** Convenience shortcuts. */
	public static function debug( string $message, array $context = array() ): void {
		self::log( Level::Debug, $message, $context );
	}
	public static function info( string $message, array $context = array() ): void {
		self::log( Level::Info, $message, $context );
	}
	public static function warning( string $message, array $context = array() ): void {
		self::log( Level::Warning, $message, $context );
	}
	public static function error( string $message, array $context = array() ): void {
		self::log( Level::Error, $message, $context );
	}
	public static function critical( string $message, array $context = array() ): void {
		self::log( Level::Critical, $message, $context );
	}

	/**
	 * Fetch recent log rows, newest first.
	 *
	 * @param int        $limit     Max rows to return (clamped 1..1000).
	 * @param Level|null $min_level Only return rows at or above this severity. null = all.
	 * @return array<int,array<string,mixed>>
	 */
	public static function recent( int $limit = 100, ?Level $min_level = null ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;
		$limit = max( 1, min( $limit, 1000 ) );

		// Single query; filter by level in PHP when needed. Level cardinality
		// is low (5 values) so the PHP filter is cheap, and we avoid a dynamic
		// IN clause with variable bindings.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ),
			ARRAY_A
		) ?? array();

		if ( null !== $min_level ) {
			$min_priority = $min_level->priority();
			$rows         = array_values(
				array_filter(
					$rows,
					static function ( $r ) use ( $min_priority ): bool {
						$l = Level::tryFrom( (string) $r['level'] );
						return null !== $l && $l->priority() >= $min_priority;
					}
				)
			);
		}

		// Decode context JSON for display.
		foreach ( $rows as &$row ) {
			$decoded      = json_decode( (string) $row['context'], true );
			$row['context'] = is_array( $decoded ) ? $decoded : array();
		}

		return $rows;
	}

	/**
	 * Drop all log rows. Used by the admin "Clear logs" button.
	 */
	public static function clear(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}" . self::TABLE_SUFFIX . '`' );
	}

	/**
	 * Delete rows older than the retention window.
	 * Also caps the table at a max row count (safety backstop).
	 */
	public static function prune(): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		$retention_days = (int) get_option( 'geo_forge_log_retention_days', self::RETENTION_DAYS );
		$retention_days = max( 1, min( $retention_days, 365 ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < DATE_SUB(%s, INTERVAL %d DAY)",
				current_time( 'mysql', true ),
				$retention_days
			)
		);

		// Safety cap: if still > 50k rows after date pruning, drop oldest.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 50000 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query(
				"DELETE FROM {$table} ORDER BY created_at ASC LIMIT " . (int) ( $count - 50000 )
			);
		}
	}

	/**
	 * Minimum severity from options. Anything below this is dropped silently.
	 */
	private static function get_min_level(): Level {
		$v = (string) get_option( 'geo_forge_log_min_level', 'warning' );
		return Level::tryFrom( $v ) ?? Level::Warning;
	}

	/**
	 * Best-effort source identifier: "ClassName::method" or "file.php:42".
	 * Cheap — just walks debug_backtrace looking for our namespace.
	 */
	private static function guess_source(): string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 8 );
		foreach ( $trace as $frame ) {
			$class = $frame['class'] ?? '';
			if ( str_starts_with( $class, 'GEO_Forge\\' ) ) {
				return $class . ( isset( $frame['function'] ) ? '::' . $frame['function'] : '' );
			}
			$file = $frame['file'] ?? '';
			if ( '' !== $file && str_contains( $file, 'geo-forge' ) ) {
				return basename( $file ) . ':' . ( $frame['line'] ?? '?' );
			}
		}
		return 'unknown';
	}

	/**
	 * Per-request ID so we can correlate multiple log lines from one request.
	 * Generated once per PHP process and reused.
	 */
	private static function request_id(): string {
		static $id = null;
		if ( null === $id ) {
			$id = substr( md5( (string) mt_rand() . microtime( true ) ), 0, 12 );
		}
		return $id;
	}

	/**
	 * Strip sensitive keys from a context array. Shallow — we don't recurse
	 * because log contexts should be flat anyway.
	 */
	private static function redact( array $context ): array {
		foreach ( $context as $key => $value ) {
			$lower = strtolower( (string) $key );
			foreach ( self::REDACT_KEYS as $secret_key ) {
				if ( str_contains( $lower, $secret_key ) ) {
					$context[ $key ] = '[REDACTED]';
					continue 2;
				}
			}
			// Bearer tokens in string values.
			if ( is_string( $value ) && preg_match( '/Bearer\s+[A-Za-z0-9._-]{8,}/i', $value ) ) {
				$context[ $key ] = preg_replace( '/(Bearer\s+)([A-Za-z0-9._-]{4})[A-Za-z0-9._-]+/i', '$1$2****', $value );
			}
		}
		return $context;
	}
}
