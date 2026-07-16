<?php
/**
 * HTTP client for the GEO KAMI Cloud API.
 *
 * All remote calls go through here — nothing else in the plugin should
 * use `wp_remote_*` directly. Keeps auth, retries, timeouts, and error
 * mapping in one place.
 *
 * Endpoints:
 *   POST /scan
 *   GET  /scans/{id}
 *   GET  /scans/history
 *   GET  /health
 *   GET  /account
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

use GEO_Forge\Install\Installer;
use GEO_Forge\Log\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Client {

	private string $api_base;
	private string $api_key;
	private int    $timeout;
	private int    $max_retries;

	public function __construct(
		string $api_base = '',
		string $api_key = '',
		int $timeout = 30,
		int $max_retries = 3
	) {
		$this->api_base    = '' !== $api_base ? untrailingslashit( $api_base ) : (string) Installer::get_setting( 'api_base', 'https://api.geokami.com' );
		$this->api_key     = $api_key ?: (string) Installer::get_setting( 'api_key', '' );
		$this->timeout     = $timeout;
		$this->max_retries = $max_retries;
	}

	/**
	 * POST /scan
	 *
	 * @param string $url             URL to scan (defaults to home_url()).
	 * @param bool   $wait_for_result If true, server blocks until scan completes.
	 * @return array{success:bool, scanId?:string, status:string, pointsCost?:int}
	 *
	 * @throws ApiException On network/auth/HTTP errors.
	 */
	public function initiate_scan( string $url = '', bool $wait_for_result = false ): array {
		if ( '' === $url ) {
			$url = home_url();
		}

		$query = $wait_for_result ? '?waitForResult=true' : '';

		return $this->request_json( 'POST', '/scan' . $query, array(
			'url' => $url,
		) );
	}

	/**
	 * GET /api/scans/{scan_id}
	 *
	 * @throws ApiException
	 */
	public function get_scan_result( string $scan_id ): array {
		$scan_id = sanitize_text_field( $scan_id );
		return $this->request_json( 'GET', '/scans/' . rawurlencode( $scan_id ) );
	}

	/**
	 * GET /api/scans/history - list all scans for current user
	 *
	 * @throws ApiException
	 */
	public function get_scan_history(): array {
		return $this->request_json( 'GET', '/scans/history' );
	}

	/**
	 * GET /api/scans/user - get user scans with pagination
	 *
	 * @param int $page Page number (1-based)
	 * @param int $limit Items per page
	 * @throws ApiException
	 */
	public function get_user_scans( int $page = 1, int $limit = 20 ): array {
		return $this->request_json( 'GET', '/scans/history?page=' . absint( $page ) . '&limit=' . absint( $limit ) );
	}

	/**
	 * GET /api/health — basic connectivity check (no auth needed).
	 * Use auth_check() to verify API key validity.
	 */
	public function health_check(): bool {
		try {
			$this->request_json( 'GET', '/health' );
			return true;
		} catch ( ApiException $e ) {
			return false;
		}
	}

	/**
	 * Test whether the API key is valid by hitting an authenticated endpoint.
	 * GET /api/scans/history returns 401 if the key is invalid.
	 */
	public function auth_check(): bool {
		try {
			$this->request_json( 'GET', '/scans/history' );
			return true;
		} catch ( ApiException $e ) {
			Logger::debug(
				'Auth check failed.',
				array( 'code' => $e->getCodeEnum()->value, 'message' => $e->getMessage() )
			);
			return false;
		}
	}

	/**
	 * GET /account — user plan, points balance, subscription status.
	 *
	 * @throws ApiException
	 */
	public function get_account(): array {
		return $this->request_json( 'GET', '/account' );
	}

	/**
	 * Check whether an API key is configured (non-empty).
	 * This is a local check only — it does not hit the API.
	 */
	public function has_api_key(): bool {
		return '' !== $this->api_key;
	}

	/**
	 * Execute an HTTP request with retry on transient failures.
	 *
	 * Retries only on: WP_Error (network) and 5xx responses.
	 * 4xx responses are NOT retried — they are auth/config problems.
	 *
	 * @throws ApiException
	 */
	private function request_json( string $method, string $path, array $body = array() ): array {
		if ( ! $this->has_api_key() && '/health' !== $path ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new ApiException( ErrorCode::Auth, esc_html__( 'GEO KAMI API key is not configured.', 'geo-forge' ) );
		}

		$url = $this->api_base . $path;

		$args = array(
			'method'      => $method,
			'timeout'     => $this->timeout,
			'redirection' => 0,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Accept'        => 'application/json',
				'User-Agent'    => 'GEO-Forge/' . GEO_FORGE_VERSION . ' (WordPress plugin; https://wordpress.org/plugins/geo-forge/)',
			),
		);

		if ( 'POST' === $method && ! empty( $body ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$last_error = null;

		for ( $attempt = 1; $attempt <= $this->max_retries; $attempt++ ) {
			$response = wp_remote_request( $url, $args );

			// Network / WP_Error — retry.
			if ( is_wp_error( $response ) ) {
				$last_error = new ApiException(
					ErrorCode::Network,
					$response->get_error_message(),
					array( 'wp_error_code' => $response->get_error_code() )
				);

				if ( $attempt < $this->max_retries ) {
					usleep( 200000 * $attempt ); // 200ms, 400ms, ...
					continue;
				}
				throw $last_error;
			}

			$status = (int) wp_remote_retrieve_response_code( $response );

			// 5xx — retry (server-side transient failure).
			if ( $status >= 500 && $attempt < $this->max_retries ) {
				usleep( 300000 * $attempt );
				continue;
			}

			// Map status → ErrorCode. 2xx returns null (success).
			$error_code = ErrorCode::from_http_status( $status );
			if ( null !== $error_code ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new ApiException(
					$error_code,
					sprintf(
						/* translators: 1: HTTP status, 2: response body */
						esc_html__( 'GEO KAMI API error (HTTP %1$d): %2$s', 'geo-forge' ),
						$status,
						wp_trim_words( wp_remote_retrieve_body( $response ), 20, '...' )
					),
					array(
						'status' => $status,
						'body'   => wp_remote_retrieve_body( $response ),
						'url'    => $url,
					)
				);
			}

			// 2xx — decode JSON.
			$raw_body = wp_remote_retrieve_body( $response );
			$decoded  = json_decode( $raw_body, true );

			if ( ! is_array( $decoded ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new ApiException(
					ErrorCode::InvalidResponse,
					esc_html__( 'GEO KAMI returned a non-JSON response.', 'geo-forge' ),
					array( 'raw_body' => $raw_body )
				);
			}

			return $decoded;
		}

		// Unreachable in practice — loop always returns or throws.
		throw $last_error ?? new ApiException( ErrorCode::Api, __( 'Unknown API failure.', 'geo-forge' ) );
	}
}
