<?php
/**
 * AI traffic record store.
 *
 * Persists records to `wp_geo_forge_traffic`. Implements the hybrid storage
 * policy chosen in the design review:
 *
 *   - "High-value" sources (well-known routes, markdown negotiation) are
 *     recorded every time — they're infrequent but important.
 *   - "Regular" sources (known AI bot crawling a product page) are sampled
 *     at 1 in N, controlled by the `geo_forge_traffic_sample_rate` option.
 *
 * Privacy: the client IP is hashed with SHA-256 + a per-site salt before
 * storage. Raw IPs never touch the DB.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Traffic;

use GEO_Forge\Log\Logger as PluginLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Store {

	private const TABLE = 'geo_forge_traffic';

	/** Sources that are recorded at 100% regardless of sample rate. */
	private const ALWAYS_RECORD = array( 'well_known', 'markdown' );

	/**
	 * Record one traffic event. Cheap to call — the heavy work (DB write)
	 * is skipped for sampled-out regular bot traffic.
	 *
	 * @param string    $family         Detected bot family (e.g. 'openai', 'anthropic', 'unknown').
	 * @param string    $source         One of: 'bot_ua', 'well_known', 'markdown'.
	 * @param string    $url            Requested URL.
	 * @param int       $status         Response status code.
	 * @param string    $ip_hash        SHA-256 of the remote IP + salt.
	 * @param string    $method         HTTP method (GET/POST/…).
	 * @param int|null  $response_bytes Body size in bytes, if known.
	 */
	public static function record(
		string $family,
		string $source,
		string $url,
		int $status,
		string $ip_hash,
		string $method = 'GET',
		?int $response_bytes = null
	): void {
		// Apply sampling for regular bot traffic.
		if ( ! in_array( $source, self::ALWAYS_RECORD, true ) && ! self::should_sample() ) {
			return;
		}

		global $wpdb;

		$row = array(
			'recorded_at'     => current_time( 'mysql', true ),
			'bot_family'      => $family,
			'request_url'     => mb_substr( $url, 0, 500 ),
			'response_status' => $status,
			'remote_ip_hash'  => $ip_hash,
			'request_method'  => strtoupper( substr( $method, 0, 10 ) ),
			'source'          => $source,
			'response_size'   => $response_bytes,
		);

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . self::TABLE ) );
		if ( ! $table_exists ) {
			PluginLogger::error(
				'Store::record: Traffic table does not exist.',
				array( 'table' => $wpdb->prefix . self::TABLE )
			);
			return;
		}

		$ok = $wpdb->insert( $wpdb->prefix . self::TABLE, $row );

		if ( false === $ok ) {
			// Traffic recording is best-effort — don't break the request.
			PluginLogger::error(
				'Store::record: Database insert failed.',
				array(
					'family' => $family,
					'source' => $source,
					'error'  => $wpdb->last_error,
					'query'  => $wpdb->last_query,
				)
			);
		} else {
			PluginLogger::debug(
				'Store::record: Successfully inserted traffic record.',
				array( 'family' => $family, 'source' => $source, 'insert_id' => $wpdb->insert_id )
			);
		}
	}

	/**
	 * Fetch recent traffic rows, newest first.
	 *
	 * @param int         $limit  Max rows.
	 * @param string|null $family Filter by bot family (e.g. 'openai', 'anthropic').
	 * @param string|null $source Filter by source ('bot_ua'|'well_known'|'markdown').
	 * @return array<int,array<string,mixed>>
	 */
	public static function recent( int $limit = 100, ?string $family = null, ?string $source = null ): array {
		global $wpdb;
		$limit = max( 1, min( $limit, 1000 ) );

		if ( null !== $family && null !== $source ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}geo_forge_traffic WHERE bot_family = %s AND source = %s ORDER BY recorded_at DESC LIMIT %d",
					$family,
					$source,
					$limit
				),
				ARRAY_A
			);
		} elseif ( null !== $family ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}geo_forge_traffic WHERE bot_family = %s ORDER BY recorded_at DESC LIMIT %d",
					$family,
					$limit
				),
				ARRAY_A
			);
		} elseif ( null !== $source ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}geo_forge_traffic WHERE source = %s ORDER BY recorded_at DESC LIMIT %d",
					$source,
					$limit
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}geo_forge_traffic ORDER BY recorded_at DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		}

		return $rows ?? array();
	}

	/**
	 * Aggregate counts per bot family, per day, for the last N days.
	 * Used by the dashboard chart.
	 *
	 * @param int $days Lookback window.
	 * @return array{labels:string[], series:array<string,int[]>}
	 */
	public static function chart_data( int $days = 14 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS day, bot_family, COUNT(*) AS n
				 FROM {$wpdb->prefix}geo_forge_traffic
				 WHERE recorded_at >= DATE_SUB(%s, INTERVAL %d DAY)
				 GROUP BY day, bot_family
				 ORDER BY day ASC",
				current_time( 'mysql', true ),
				$days
			),
			ARRAY_A
		) ?? array();

		$labels = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$labels[] = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		}

		$series = array();
		foreach ( $rows as $r ) {
			$series[ $r['bot_family'] ][ $r['day'] ] = (int) $r['n'];
		}

		foreach ( $series as $family => $by_day ) {
			$filled = array();
			foreach ( $labels as $day ) {
				$filled[] = $by_day[ $day ] ?? 0;
			}
			$series[ $family ] = $filled;
		}

		return array( 'labels' => $labels, 'series' => $series );
	}

	/**
	 * Summary counts (total, by family) for the last 24h.
	 */
	public static function summary_24h(): array {
		global $wpdb;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}geo_forge_traffic WHERE recorded_at >= DATE_SUB(%s, INTERVAL 1 DAY)",
				current_time( 'mysql', true )
			)
		);

		$by_family = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT bot_family, COUNT(*) AS n FROM {$wpdb->prefix}geo_forge_traffic
				 WHERE recorded_at >= DATE_SUB(%s, INTERVAL 1 DAY)
				 GROUP BY bot_family",
				current_time( 'mysql', true )
			),
			ARRAY_A
		) ?? array();

		return array(
			'total_24h' => $total,
			'by_family' => $by_family,
		);
	}

	/**
	 * Wipe all traffic rows. Used by admin "Clear" button.
	 */
	public static function clear(): void {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}geo_forge_traffic`" );
	}

	/**
	 * Sample decision for regular bot traffic.
	 * Rate is read from options so admins can tune it. Default: 1 in 10.
	 */
	private static function should_sample(): bool {
		$rate = max( 1, (int) get_option( 'geo_forge_traffic_sample_rate', 10 ) );
		return 0 === wp_rand( 0, $rate - 1 );
	}
}
