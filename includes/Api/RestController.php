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
 *   GET  /geo-forge/v1/logs           — recent log entries
 *   POST /geo-forge/v1/logs/clear     — drop all log entries
 *
 * All endpoints require `manage_woocommerce` capability.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

use GEO_Forge\Api\ApiException;
use GEO_Forge\Log\Level;
use GEO_Forge\Log\Logger;
use GEO_Forge\Scanner\Scanner;
use GEO_Forge\WellKnown\LlmsTxt;

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

		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => 'READABLE',
				'callback'            => array( $this, 'handle_get_logs' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'limit' => array(
						'type'              => 'integer',
						'default'           => 100,
						'minimum'           => 1,
						'maximum'           => 1000,
						'sanitize_callback' => 'absint',
					),
					'level' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/logs/clear',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_clear_logs' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/well-known/llms-txt',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_save_llms_txt' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'content' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
				array(
					'methods'             => 'READABLE',
					'callback'            => array( $this, 'handle_get_llms_txt' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/well-known/llms-txt/regenerate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_regenerate_llms_txt' ),
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
			Logger::info( 'Scan triggered via REST.' );
			$scanner = new Scanner();
			$row     = $scanner->run_scan();

			Logger::info(
				'Scan completed.',
				array( 'score' => (int) ( $row['total_score'] ?? 0 ), 'grade' => $row['grade'] ?? '' )
			);

			return new \WP_REST_Response( array(
				'success' => true,
				'scan'    => $this->format_scan_row( $row ),
			), 200 );
		} catch ( ApiException $e ) {
			Logger::error(
				'Scan failed: ' . $e->getMessage(),
				array( 'code' => $e->getCodeEnum()->value )
			);
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => $e->getCodeEnum()->value,
					'message' => $e->getMessage(),
				),
			), $this->http_status_for( $e->getCodeEnum() ) );
		} catch ( \RuntimeException $e ) {
			Logger::error( 'Scan timed out: ' . $e->getMessage() );
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
	 * GET /logs — recent log entries.
	 */
	public function handle_get_logs( \WP_REST_Request $request ): \WP_REST_Response {
		$limit     = (int) $request->get_param( 'limit' );
		$level_raw = (string) $request->get_param( 'level' );
		$min_level = '' !== $level_raw ? Level::tryFrom( $level_raw ) : null;

		$rows = Logger::recent( $limit, $min_level );

		return new \WP_REST_Response( array(
			'success' => true,
			'count'   => count( $rows ),
			'logs'    => $rows,
		), 200 );
	}

	/**
	 * POST /logs/clear — drop all log entries.
	 */
	public function handle_clear_logs(): \WP_REST_Response {
		try {
			Logger::clear();
			Logger::info( 'Logs cleared via REST.', array( 'source' => 'RestController::handle_clear_logs' ) );

			return new \WP_REST_Response( array(
				'success' => true,
				'message' => __( 'Logs cleared.', 'geo-forge' ),
			), 200 );
		} catch ( \Throwable $e ) {
			Logger::error( 'Failed to clear logs: ' . $e->getMessage(), array(
				'exception' => get_class( $e ),
			) );
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'clear_failed',
					'message' => __( 'Could not clear logs.', 'geo-forge' ),
				),
			), 500 );
		}
	}

	/**
	 * POST /well-known/llms-txt — save user-edited content.
	 */
	public function handle_save_llms_txt( \WP_REST_Request $request ): \WP_REST_Response {
		$content = (string) $request->get_param( 'content' );
		LlmsTxt::save( $content );

		return new \WP_REST_Response( array(
			'success' => true,
			'bytes'   => strlen( $content ),
		), 200 );
	}

	/**
	 * GET /well-known/llms-txt — fetch current stored content (for the editor).
	 */
	public function handle_get_llms_txt(): \WP_REST_Response {
		return new \WP_REST_Response( array(
			'success' => true,
			'content' => LlmsTxt::get_current(),
		), 200 );
	}

	/**
	 * POST /well-known/llms-txt/regenerate — rebuild from store data.
	 */
	public function handle_regenerate_llms_txt(): \WP_REST_Response {
		$content = LlmsTxt::regenerate();
		return new \WP_REST_Response( array(
			'success' => true,
			'content' => $content,
			'bytes'   => strlen( $content ),
		), 200 );
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
