<?php
/**
 * Main plugin controller.
 *
 * Boots every subsystem. Currently: Admin UI only.
 * Future milestones add Scanner, Fixer, Monitor, WellKnown, etc.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge;

use GEO_Forge\Admin\Admin;
use GEO_Forge\Api\RestController;
use GEO_Forge\Log\ErrorCapture;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GeoForge {

	private static ?self $instance = null;

	private function __construct() {
		$this->register_hooks();
	}

	/** Not clonable. */
	private function __clone() {}

	/** Not unserializable. */
	public function __wakeup() {
		throw new \RuntimeException( 'GeoForge is a singleton.' );
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Reset the singleton. Test-only.
	 *
	 * @internal
	 */
	public static function reset_for_tests(): void {
		self::$instance = null;
	}

	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Capture fatals in our own code. Registered early — runs on every request,
		// including cron and REST. Cheap when there's no fatal.
		ErrorCapture::register();

		if ( is_admin() ) {
			$admin = new Admin();
			$admin->register();
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'geo-forge',
			false,
			dirname( GEO_FORGE_BASENAME ) . '/languages/'
		);
	}

	public function register_rest_routes(): void {
		( new RestController() )->register_routes();
	}
}
