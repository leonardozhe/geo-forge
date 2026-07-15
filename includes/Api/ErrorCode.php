<?php
/**
 * Error codes for GEO KAMI API failures.
 *
 * PHP 8.1 backed enum — each case carries a human-readable label.
 * The API client throws `ApiException` with one of these codes so callers
 * can `match` on `$e->code` instead of string comparisons.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum ErrorCode: string {
	case Network          = 'network_error';
	case Auth             = 'auth_error';
	case RateLimit        = 'rate_limited';
	case InsufficientPts  = 'insufficient_points';
	case Api              = 'api_error';
	case Timeout          = 'timeout';
	case InvalidResponse  = 'invalid_response';

	public function label(): string {
		return match ( $this ) {
			self::Network         => __( 'Network error contacting GEO KAMI.', 'geo-forge' ),
			self::Auth            => __( 'API key rejected — check your key in GEO Forge settings.', 'geo-forge' ),
			self::RateLimit       => __( 'GEO KAMI rate limit reached — try again in a few minutes.', 'geo-forge' ),
			self::InsufficientPts => __( 'Not enough GEO KAMI points to run this scan.', 'geo-forge' ),
			self::Api             => __( 'GEO KAMI returned an error.', 'geo-forge' ),
			self::Timeout         => __( 'Request to GEO KAMI timed out.', 'geo-forge' ),
			self::InvalidResponse => __( 'Unexpected response from GEO KAMI.', 'geo-forge' ),
		};
	}

	/**
	 * Map an HTTP status code to the matching error code.
	 * Returns null for 2xx (success).
	 */
	public static function from_http_status( int $status ): ?self {
		return match ( true ) {
			$status >= 200 && $status < 300 => null,
			401 === $status, 403 === $status => self::Auth,
			402 === $status                  => self::InsufficientPts,
			429 === $status                  => self::RateLimit,
			$status >= 500                  => self::Api,
			default                        => self::Api,
		};
	}
}
