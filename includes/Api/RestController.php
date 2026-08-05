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
 * All endpoints require `manage_options` capability.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

use GEO_Forge\Api\ApiException;
use GEO_Forge\Compat\SeoDetector;
use GEO_Forge\Fixer\Fixer;
use GEO_Forge\GeoForge;
use GEO_Forge\Log\Level;
use GEO_Forge\Log\Logger;
use GEO_Forge\Scanner\Scanner;
use GEO_Forge\Traffic\Store;
use GEO_Forge\WellKnown\LlmsTxt;
use GEO_Forge\WellKnown\RobotsTxt;
use GEO_Forge\WellKnown\SecurityTxt;

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
			'/scan/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_scan_status' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/last',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_last_scan' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_scan_by_id' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
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
				'methods'             => \WP_REST_Server::READABLE,
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
			'/traffic/404/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'handle_delete_404' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/traffic/404',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'handle_clear_404' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/logs/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_reset_logs' ),
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
					'methods'             => \WP_REST_Server::READABLE,
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

		// security.txt — save, get, regenerate
		register_rest_route(
			self::NAMESPACE,
			'/well-known/security-txt',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_save_security_txt' ),
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
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_get_security_txt' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/well-known/security-txt/regenerate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_regenerate_security_txt' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// AI bot rules (robots.txt) — save, get, regenerate
		register_rest_route(
			self::NAMESPACE,
			'/well-known/robots-txt',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_save_robots_txt' ),
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
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_get_robots_txt' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/well-known/robots-txt/regenerate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_regenerate_robots_txt' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// Fixer endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/account',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_account' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// Fixer endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/fixes',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_list_fixes' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		$fix_actions = array( 'apply', 'rollback', 'verify', 'audit', 'cover', 'ignore' );
		foreach ( $fix_actions as $action ) {
			register_rest_route(
				self::NAMESPACE,
				'/fixes/(?P<id>[a-z0-9_]+)/' . $action,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_fix_action' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'_action'             => $action, // smuggled to callback via route args
				)
			);
		}
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * POST /scan — start a scan asynchronously and return immediately.
	 * The client polls GET /scan/status until it reports 'completed'.
	 */
	public function handle_trigger_scan(): \WP_REST_Response {
		try {
			Logger::info( 'Scan triggered via REST (async).' );
			$scanner = new Scanner();
			$result  = $scanner->start_scan();

			return new \WP_REST_Response( array(
				'success' => true,
				'scan_id' => $result['scan_id'] ?? '',
				'status'  => $result['status'] ?? 'running',
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
	 * GET /scan/status — poll the in-flight scan; persists + returns the
	 * result row once the API reports completion.
	 */
	public function handle_scan_status(): \WP_REST_Response {
		$scanner = new Scanner();
		$status  = $scanner->check_scan_status();

		$payload = array(
			'success' => true,
			'status'  => $status['status'] ?? 'idle',
		);

		if ( ! empty( $status['scan_id'] ) ) {
			$payload['scan_id'] = $status['scan_id'];
		}
		if ( ! empty( $status['row'] ) ) {
			$payload['scan'] = $this->format_scan_row( $status['row'] );
		}
		if ( ! empty( $status['message'] ) ) {
			$payload['message'] = $status['message'];
		}

		return new \WP_REST_Response( $payload, 200 );
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
	 * GET /scan/{id} — return a specific scan row by primary key.
	 */
	public function handle_get_scan_by_id( \WP_REST_Request $request ): \WP_REST_Response {
		$id      = (int) $request->get_param( 'id' );
		$scanner = new Scanner();
		$row     = $scanner->get_scan_by_id( $id );

		if ( null === $row ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'scan_not_found',
					'message' => __( 'Scan not found.', 'geo-forge' ),
				),
			), 404 );
		}

		return new \WP_REST_Response( array(
			'success' => true,
			'scan'    => $this->format_scan_row( $row ),
		), 200 );
	}

	/**
	 * POST /health-check — verify API key validity + reachability.
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

		// Check connectivity first (no auth needed)
		if ( ! $client->health_check() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'ok'      => false,
				'error'   => array(
					'code'    => ErrorCode::Api->value,
					'message' => __( 'Cannot reach GEO KAMI API.', 'geo-forge' ),
				),
			), 502 );
		}

		// Now actually verify the API key
		if ( ! $client->auth_check() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'ok'      => false,
				'error'   => array(
					'code'    => ErrorCode::Auth->value,
					'message' => __( 'API key is invalid or expired. Please check your key at geokami.com.', 'geo-forge' ),
				),
			), 401 );
		}

		return new \WP_REST_Response( array(
			'success' => true,
			'ok'      => true,
		), 200 );
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
	 * DELETE /traffic/404/{id} — remove one recorded LLM 404.
	 */
	public function handle_delete_404( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request['id'];
		if ( Store::delete_404( $id ) ) {
			return new \WP_REST_Response( array(
				'success' => true,
				'message' => __( '404 record deleted.', 'geo-forge' ),
			), 200 );
		}
		return new \WP_REST_Response( array(
			'success' => false,
			'error'   => array( 'message' => __( '404 record not found.', 'geo-forge' ) ),
		), 404 );
	}

	/**
	 * DELETE /traffic/404 — clear every recorded LLM 404.
	 */
	public function handle_clear_404(): \WP_REST_Response {
		$deleted = Store::delete_all_404();
		return new \WP_REST_Response( array(
			'success' => true,
			/* translators: %d: number of records deleted */
			'message' => sprintf( __( 'Deleted %d LLM 404 record(s).', 'geo-forge' ), $deleted ),
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
	 * POST /logs/reset — rebuild the logs table from scratch.
	 *
	 * Drops the table, recreates it with the current schema, and resets the
	 * min_level option to the default. Use this when:
	 *   - Logs page shows nothing despite recent plugin activity.
	 *   - Table schema is out of sync with the current plugin version.
	 *   - The min_level option is stuck on an old value.
	 */
	public function handle_reset_logs(): \WP_REST_Response {
		try {
			$result = Logger::reset();

			if ( $result['success'] ) {
				Logger::info( 'Logs table rebuilt via REST.', array(
					'source'      => 'RestController::handle_reset_logs',
					'rows_before' => $result['rows_before'],
				) );
			}

			return new \WP_REST_Response( $result, $result['success'] ? 200 : 500 );
		} catch ( \Throwable $e ) {
			Logger::error( 'Failed to reset logs table: ' . $e->getMessage(), array(
				'exception' => get_class( $e ),
			) );
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'reset_failed',
					'message' => $e->getMessage(),
				),
			), 500 );
		}
	}

	/**
	 * POST /well-known/llms-txt — save user-edited content.
	 */
	public function handle_save_llms_txt( \WP_REST_Request $request ): \WP_REST_Response {
		if ( 'geo-forge' !== SeoDetector::llms_txt_owner() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'audit_only',
					'message' => sprintf(
						/* translators: %s: owner label */
						__( 'llms.txt is managed by %s — GEO Forge audits only and did not save.', 'geo-forge' ),
						SeoDetector::owner_label( SeoDetector::llms_txt_owner() )
					),
				),
			), 400 );
		}

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
		if ( 'geo-forge' !== SeoDetector::llms_txt_owner() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'audit_only',
					'message' => sprintf(
						/* translators: %s: owner label */
						__( 'llms.txt is managed by %s — GEO Forge audits only and did not regenerate.', 'geo-forge' ),
						SeoDetector::owner_label( SeoDetector::llms_txt_owner() )
					),
				),
			), 400 );
		}

		try {
			$content = LlmsTxt::regenerate_all();
			return new \WP_REST_Response( array(
				'success' => true,
				'content' => $content,
				'bytes'   => strlen( $content ),
			), 200 );
		} catch ( \Throwable $e ) {
			\GEO_Forge\Log\Logger::error(
				'llms.txt regenerate failed: ' . $e->getMessage(),
				array( 'exception' => get_class( $e ), 'file' => $e->getFile(), 'line' => $e->getLine() )
			);
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'regenerate_failed',
					'message' => $e->getMessage(),
				),
			), 500 );
		}
	}

	/* ---- security.txt handlers ---- */

	public function handle_save_security_txt( \WP_REST_Request $request ): \WP_REST_Response {
		$content = (string) $request->get_param( 'content' );
		SecurityTxt::save( $content );
		return new \WP_REST_Response( array(
			'success' => true,
			'bytes'   => strlen( $content ),
		), 200 );
	}

	public function handle_get_security_txt(): \WP_REST_Response {
		return new \WP_REST_Response( array(
			'success' => true,
			'content' => SecurityTxt::get_current(),
		), 200 );
	}

	public function handle_regenerate_security_txt(): \WP_REST_Response {
		try {
			$content = SecurityTxt::regenerate();
			return new \WP_REST_Response( array(
				'success' => true,
				'content' => $content,
			), 200 );
		} catch ( \Throwable $e ) {
			\GEO_Forge\Log\Logger::error( 'security.txt regenerate failed: ' . $e->getMessage(), array( 'exception' => get_class( $e ) ) );
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array( 'code' => 'regenerate_failed', 'message' => $e->getMessage() ),
			), 500 );
		}
	}

	/* ---- robots.txt handlers ---- */

	public function handle_save_robots_txt( \WP_REST_Request $request ): \WP_REST_Response {
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'audit_only',
					'message' => sprintf(
						/* translators: %s: owner label */
						__( 'robots.txt is managed by %s — GEO Forge audits only and did not save.', 'geo-forge' ),
						SeoDetector::owner_label( SeoDetector::robots_txt_owner() )
					),
				),
			), 400 );
		}

		$content = (string) $request->get_param( 'content' );
		RobotsTxt::save( $content );
		return new \WP_REST_Response( array(
			'success' => true,
			'bytes'   => strlen( $content ),
		), 200 );
	}

	public function handle_get_robots_txt(): \WP_REST_Response {
		return new \WP_REST_Response( array(
			'success' => true,
			'content' => RobotsTxt::get_current(),
		), 200 );
	}

	public function handle_regenerate_robots_txt(): \WP_REST_Response {
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner() ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'audit_only',
					'message' => sprintf(
						/* translators: %s: owner label */
						__( 'robots.txt is managed by %s — GEO Forge audits only and did not regenerate.', 'geo-forge' ),
						SeoDetector::owner_label( SeoDetector::robots_txt_owner() )
					),
				),
			), 400 );
		}

		try {
			$content = RobotsTxt::regenerate();
			return new \WP_REST_Response( array(
				'success' => true,
				'content' => $content,
			), 200 );
		} catch ( \Throwable $e ) {
			\GEO_Forge\Log\Logger::error( 'robots.txt regenerate failed: ' . $e->getMessage(), array( 'exception' => get_class( $e ) ) );
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array( 'code' => 'regenerate_failed', 'message' => $e->getMessage() ),
			), 500 );
		}
	}

	/* =====================================================================
	 * Fixer endpoints
	 * ===================================================================== */

	/**
	 * GET /fixes — list all registered fixes with current status.
	 */
	public function handle_list_fixes(): \WP_REST_Response {
		$fixer = GeoForge::fixer();
		if ( null === $fixer ) {
			return new \WP_REST_Response( array( 'success' => true, 'fixes' => array() ), 200 );
		}

		return new \WP_REST_Response( array(
			'success' => true,
			'fixes'   => array_values( $fixer->list() ),
		), 200 );
	}

	/**
	 * POST /fixes/{id}/apply|rollback|verify — dispatch the named action.
	 *
	 * The action name is smuggled in via the `_action` key in the route
	 * registration array; we read it back from the route's registered args.
	 */
	public function handle_fix_action( \WP_REST_Request $request ): \WP_REST_Response {
		$id     = (string) $request->get_param( 'id' );
		$action = $this->resolve_action( $request );
		$fixer  = GeoForge::fixer();

		if ( null === $fixer ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array( 'code' => 'no_fixer', 'message' => __( 'Fixer not initialized.', 'geo-forge' ) ),
			), 500 );
		}

		$result = match ( $action ) {
			'apply'    => $fixer->apply( $id ),
			'rollback' => $fixer->rollback( $id ),
			'verify'   => $fixer->verify( $id ),
			'audit'    => $fixer->audit( $id ),
			'cover'    => $fixer->cover( $id ),
			'ignore'   => $fixer->ignore( $id ),
			default    => array( 'success' => false, 'message' => __( 'Unknown action.', 'geo-forge' ) ),
		};

		if ( empty( $result['success'] ) ) {
			return new \WP_REST_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 'fix_' . $action . '_failed',
					'message' => $result['message'] ?? __( 'Action failed.', 'geo-forge' ),
				),
			), 400 );
		}

		// Refresh status and applied_at from the fix so the UI can update in place.
		$fixes = $fixer->list();
		$row   = $fixes[ $id ] ?? null;

		$result['status']      = $row['status'] ?? 'pending';
		$result['applied_at']  = $row['applied_at'] ?? null;

		return new \WP_REST_Response( array_merge( array( 'success' => true ), $result ), 200 );
	}

	/**
	 * Determine which fix action (apply/rollback/verify) the request maps to.
	 * We encoded this in the route registration args as `_action`; fall back
	 * to parsing the route path if that's missing.
	 */
	private function resolve_action( \WP_REST_Request $request ): string {
		$route = $request->get_route();
		foreach ( array( 'apply', 'rollback', 'verify', 'audit', 'cover', 'ignore' ) as $candidate ) {
			if ( str_ends_with( (string) $route, '/' . $candidate ) ) {
				return $candidate;
			}
		}
		return 'apply';
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
