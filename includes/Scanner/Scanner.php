<?php
/**
 * Scan orchestrator.
 *
 * Responsibilities:
 *   1. Collect site info (WP version, WooCommerce version, product count, …).
 *   2. Call the API to start a scan.
 *   3. Poll until complete (with a timeout).
 *   4. Persist the result to the custom `scans` table and the transient cache.
 *   5. Fire a hook so other modules (Monitor, Dashboard) can react.
 *
 * Keeps all GEO KAMI interaction behind `Api\Client` — the scanner never
 * talks HTTP directly.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Scanner;

use GEO_Forge\Api\ApiException;
use GEO_Forge\Api\Client;
use GEO_Forge\Cache\TransientCache;
use GEO_Forge\Log\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scanner {

	private Client $api;

	public function __construct( ?Client $api = null ) {
		$this->api = $api ?? new Client();
	}

	/**
	 * Run a full scan end-to-end.
	 *
	 * @param int $max_wait_seconds How long to poll before giving up.
	 * @return array The stored scan row.
	 *
	 * @throws ApiException On API errors (propagated from Client).
	 * @throws \RuntimeException If polling exceeds max wait.
	 */
	public function run_scan( int $max_wait_seconds = 120 ): array {
		$site_info = $this->collect_site_info();

		Logger::info(
			'Starting scan.',
			array( 'domain' => $site_info['domain'], 'max_wait_seconds' => $max_wait_seconds )
		);

		// 1. Start scan. We ask the API to block so one round trip suffices
		//    for most scans. If the server ignores waitForResult, we fall back
		//    to polling in step 3.
		$response = $this->api->initiate_scan( $site_info['domain'], true );

		$scan_id = $response['scanId'] ?? '';
		if ( '' === $scan_id ) {
			// Synchronous response — result embedded.
			if ( isset( $response['result'] ) && is_array( $response['result'] ) ) {
				return $this->store_result( $response, $site_info );
			}
			Logger::warning( 'Scan response did not include a scanId.', array( 'response' => $response ) );
			throw new ApiException(
				esc_html( \GEO_Forge\Api\ErrorCode::InvalidResponse->value ),
				esc_html__( 'Scan response did not include a scanId.', 'geo-forge' ),
				array( 'response' => esc_html( wp_json_encode( $response ) ) )
			);
		}

		Logger::debug( 'Scan initiated, polling for result.', array( 'scan_id' => $scan_id ) );

		// 2. Poll until complete (or timeout).
		$result = $this->poll_until_complete( $scan_id, $max_wait_seconds );

		return $this->store_result( array(
			'scanId'     => $scan_id,
			'pointsCost' => $response['pointsCost'] ?? 0,
			'result'     => $result,
		), $site_info );
	}

	/**
	 * Poll GET /api/scans/{id} until status is 'completed' or 'failed'.
	 *
	 * @throws ApiException On API errors.
	 * @throws \RuntimeException On timeout.
	 */
	private function poll_until_complete( string $scan_id, int $max_wait_seconds ): array {
		$deadline = time() + $max_wait_seconds;
		$interval = 2; // seconds between polls

		while ( time() < $deadline ) {
			$response = $this->api->get_scan_result( $scan_id );
			$status   = $response['status'] ?? '';

			if ( 'completed' === $status ) {
				return $response['result'] ?? $response;
			}

			if ( 'failed' === $status ) {
				Logger::error(
					'Scan failed on GEO KAMI side.',
					array( 'scan_id' => $scan_id )
				);
				throw new ApiException(
					esc_html( \GEO_Forge\Api\ErrorCode::Api->value ),
					esc_html__( 'Scan failed on GEO KAMI side.', 'geo-forge' ),
					array( 'scan_id' => sanitize_text_field( $scan_id ), 'response' => esc_html( wp_json_encode( $response ) ) )
				);
			}

			sleep( $interval );
		}

		Logger::warning(
			sprintf(
				/* translators: %d: seconds */
				esc_html__( 'Scan polling timed out after %d seconds.', 'geo-forge' ),
				$max_wait_seconds
			),
			array( 'scan_id' => $scan_id )
		);

		throw new \RuntimeException(
			esc_html( sprintf(
				/* translators: %d: seconds */
				esc_html__( 'Scan did not complete within %d seconds.', 'geo-forge' ),
				absint( $max_wait_seconds )
			) )
		);
	}

	/**
	 * Persist scan result to the DB and warm the transient cache.
	 * Fires `geo_forge_scan_completed` so listeners can react.
	 */
	private function store_result( array $response, array $site_info ): array {
		global $wpdb;

		$result = $response['result'] ?? $response;

		$row = array(
			'scan_id'          => sanitize_text_field( $result['scanId'] ?? $result['id'] ?? '' ),
			'total_score'      => (int) ( $result['totalScore'] ?? 0 ),
			'grade'            => sanitize_text_field( $result['grade']['grade'] ?? '' ),
			'grade_label'      => sanitize_text_field( $result['grade']['label'] ?? '' ),
			'category_scores'  => wp_json_encode( $result['categories'] ?? array() ),
			'checks_result'    => wp_json_encode( $result['checks'] ?? array() ),
			'suggestions'      => wp_json_encode( $result['suggestions'] ?? array() ),
			'points_cost'      => (int) ( $response['pointsCost'] ?? 0 ),
			'scan_duration_ms' => isset( $result['scanDurationMs'] ) ? (int) $result['scanDurationMs'] : null,
			'completed_at'     => sanitize_text_field( $result['completedAt'] ?? current_time( 'mysql', true ) ),
		);

		$wpdb->replace( $wpdb->prefix . 'geo_forge_scans', $row );

		// Warm caches.
		TransientCache::set( 'last_scan', $row );
		update_option( 'geo_forge_last_scan_time', current_time( 'mysql' ) );

		Logger::info(
			'Scan completed and stored.',
			array(
				'scan_id'     => $row['scan_id'],
				'total_score' => $row['total_score'],
				'grade'       => $row['grade'],
			)
		);

		/**
		 * Fires after a scan result is persisted.
		 *
		 * @param array $row       The stored DB row.
		 * @param array $site_info Site info sent to the API.
		 */
		do_action( 'geo_forge_scan_completed', $row, $site_info );

		return $row;
	}

	/**
	 * Assemble the metadata payload the API wants alongside the URL.
	 */
	private function collect_site_info(): array {
		$product_count = 0;
		$wc_version    = '';
		if ( function_exists( 'WC' ) && WC() && isset( WC()->version ) ) {
			$wc_version    = (string) WC()->version;
			$product_count = (int) wp_count_posts( 'product' )->publish;
		}

		return array(
			'domain'           => home_url(),
			'platform'         => 'woocommerce',
			'wp_version'       => get_bloginfo( 'version' ),
			'wc_version'       => $wc_version,
			'theme'            => wp_get_theme()->get( 'Name' ),
			'active_plugins'   => array_values( (array) get_option( 'active_plugins', array() ) ),
			'product_count'    => $product_count,
			'language'         => get_locale(),
			'permalink_struct' => (string) get_option( 'permalink_structure', '' ),
			'ssl_enabled'      => is_ssl(),
		);
	}

	/**
	 * Fetch the latest scan row from the DB, if any.
	 */
	public function get_last_scan(): ?array {
		$cached = TransientCache::get( 'last_scan' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 1",
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		// Decode JSON fields BEFORE caching so callers always get arrays
		foreach ( array( 'category_scores', 'checks_result', 'suggestions' ) as $field ) {
			if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) ) {
				$decoded = json_decode( $row[ $field ], true );
				$row[ $field ] = is_array( $decoded ) ? $decoded : array();
			}
		}

		TransientCache::set( 'last_scan', $row );
		return $row;
	}

	/**
	 * Fetch the most recent scan rows for the score history chart.
	 *
	 * @param int $limit Number of rows to return (clamped to 1–100).
	 * @return array List of scan rows (associative arrays).
	 */
	public function get_score_history( int $limit = 30 ): array {
		global $wpdb;
		$limit = max( 1, min( $limit, 100 ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, total_score, grade, grade_label, created_at, checks_result FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		return $rows ?: array();
	}

	/**
	 * Fetch a specific scan row by its ID.
	 *
	 * @param int $scan_id Primary key in the scans table.
	 * @return array|null The scan row (with JSON fields decoded) or null if not found.
	 */
	public function get_scan_by_id( int $scan_id ): ?array {
		if ( $scan_id <= 0 ) {
			return null;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}geo_forge_scans WHERE id = %d LIMIT 1",
				$scan_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		foreach ( array( 'category_scores', 'checks_result', 'suggestions' ) as $field ) {
			if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) ) {
				$decoded = json_decode( $row[ $field ], true );
				$row[ $field ] = is_array( $decoded ) ? $decoded : array();
			}
		}

		return $row;
	}
}
