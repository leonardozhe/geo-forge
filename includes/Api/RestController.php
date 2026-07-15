<?php
/**
 * REST API controller for the plugin's admin-facing endpoints.
 *
 * Mounted under: `wp-json/geo-forge/v1/*`
 *
 * Endpoints:
 *   POST /geo-forge/v1/scan           — trigger a scan, returns the stored row
 *   GET  /geo-forge/v1/scan/last      — last scan row (cached)
 *   POST /geo-forge/v1/health-check   — test API connectivity, returns { ok: bool }
 *
 * All endpoints require `manage_woocommerce` capability.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

use GEO_Forge\Api\ApiException;
use GEO_Forge\Scanner\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestController {

	private const NAMESPACE = 'geo-forge/v1';

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/scan',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_trigger_scan' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/last',
			array(
				'methods'             => 'READABLE',
				'callback'            => array( $this, 'handle_get_last_scan' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/health-check',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_health_check' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * POST /scan — trigger a scan synchronously and return the result row.
	 */
	public function handle_trigger_scan(): \WP_REST_Response {
		try {
			$scanner = new Scanner();
			$row     = $scanner->run_scan();

			return new \WP_REST_Response( array(
				'success' => true,
				'scan'    => $this->format_scan_row( $row ),
			), 200 );
		} catch ( ApiException $e ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => $e->getCodeEnum()->value,
					'message' => $e->getMessage(),
				),
			), $this->http_status_for( $e->getCodeEnum() ) );
		} catch ( \RuntimeException $e ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'timeout',
					'message' => $e->getMessage(),
				),
			), 504 );
		}
	}

	/**
	 * GET /scan/last — return the latest stored scan row.
	 */
	public function handle_get_last_scan(): \WP_REST_Response {
		$scanner = new Scanner();
		$row     = $scanner->get_last_scan();

		if ( null === $row ) {
			return new \WP_REST_Response( array(
				'success' => true,
				'scan'    => null,
			), 200 );
		}

		return new \WP_REST_Response( array(
			'success' => true,
			'scan'    => $this->format_scan_row( $row ),
		), 200 );
	}

	/**
	 * POST /health-check — verify API key + reachability.
	 */
	public function handle_health_check(): \WP_REST_Response {
		$client = new Client();

		if ( ! $client->has_api_key() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => ErrorCode::Auth->value,
					'message' => __( 'No API key configured.', 'geo-forge' ),
				),
			), 400 );
		}

		$ok = $client->health_check();

		return new \WP_REST_Response( array(
			'success' => $ok,
			'ok'      => $ok,
			'error'   => $ok ? null : array(
				'code'    => ErrorCode::Api->value,
				'message' => __( 'Health check failed.', 'geo-forge' ),
			),
		), $ok ? 200 : 502 );
	}

	/**
	 * Normalize a DB row for JSON output.
	 * Decodes JSON strings back to arrays so the JS client gets structured data.
	 */
	private function format_scan_row( array $row ): array {
		$json_fields = array( 'category_scores', 'checks_result', 'suggestions' );

		foreach ( $json_fields as $field ) {
			if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) ) {
				$decoded = json_decode( $row[ $field ], true );
				$row[ $field ] = is_array( $decoded ) ? $decoded : array();
			}
		}

		// Cast numeric fields to int (DB returns strings via $wpdb).
		foreach ( array( 'id', 'total_score', 'points_cost', 'scan_duration_ms' ) as $field ) {
			if ( isset( $row[ $field ] ) ) {
				$row[ $field ] = (int) $row[ $field ];
			}
		}

		return $row;
	}

	/**
	 * Map ErrorCode → HTTP status for REST responses.
	 */
	private function http_status_for( ErrorCode $code ): int {
		return match ( $code ) {
			ErrorCode::Auth            => 401,
			ErrorCode::InsufficientPts => 402,
			ErrorCode::RateLimit       => 429,
			ErrorCode::Timeout         => 504,
			ErrorCode::InvalidResponse => 502,
			default                    => 500,
		};
	}
}
