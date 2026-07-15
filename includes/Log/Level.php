<?php
/**
 * Log level enum.
 *
 * Numeric priority values match Monolog/Psr\Log conventions so we can
 * easily filter ">= warning" later without hardcoding level names.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum Level: string {
	case Debug    = 'debug';
	case Info     = 'info';
	case Warning  = 'warning';
	case Error    = 'error';
	case Critical = 'critical';

	/**
	 * Higher number = more severe. Used for "at least this level" filters.
	 */
	public function priority(): int {
		return match ( $this ) {
			self::Debug    => 100,
			self::Info     => 200,
			self::Warning  => 300,
			self::Error    => 400,
			self::Critical => 500,
		};
	}

	/**
	 * Human label for UI.
	 */
	public function label(): string {
		return match ( $this ) {
			self::Debug    => __( 'Debug', 'geo-forge' ),
			self::Info     => __( 'Info', 'geo-forge' ),
			self::Warning  => __( 'Warning', 'geo-forge' ),
			self::Error    => __( 'Error', 'geo-forge' ),
			self::Critical => __( 'Critical', 'geo-forge' ),
		};
	}

	/**
	 * CSS class suffix used in the admin log table.
	 */
	public function css_class(): string {
		return 'geo-forge-log-' . $this->value;
	}
}
