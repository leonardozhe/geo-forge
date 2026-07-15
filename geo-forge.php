<?php
/**
 * Plugin Name:       GEO Forge
 * Plugin URI:        https://geokami.com/geo-forge
 * Description:       Forge your WooCommerce store for the AI era — one-click scan, fix, and monitor for AI agent discoverability (llms.txt, MCP, A2A, structured data, markdown negotiation).
 * Version:           1.0.6-dev
 * Author:            GEO KAMI
 * Author URI:        https://geokami.com
 * License:           GPL v3+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       geo-forge
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * WC requires at least: 8.0
 *
 * @package GEO_Forge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Minimum PHP version.
 * Show an admin notice and bail before loading any namespaced code
 * (PHP 7.x would fatal on typed properties / enums).
 */
if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'GEO Forge requires PHP 8.1 or higher.', 'geo-forge' )
				. '</p></div>';
		}
	);
	return;
}

/*
 * Constants.
 * Kept minimal — only what other files genuinely need.
 */
define( 'GEO_FORGE_VERSION', '1.0.6-dev' );
define( 'GEO_FORGE_FILE', __FILE__ );
define( 'GEO_FORGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_FORGE_URL', plugin_dir_url( __FILE__ ) );
define( 'GEO_FORGE_BASENAME', plugin_basename( __FILE__ ) );

/*
 * PSR-4 style autoloader for `GEO_Forge\*` classes.
 * `GEO_Forge\Scanner\Scanner` → `includes/Scanner/Scanner.php`
 *
 * Manual — no Composer dependency. ~15 lines is cheaper than forcing
 * `composer install` on every user before the plugin can activate.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'GEO_Forge\\';
		$base_dir = GEO_FORGE_DIR . 'includes/';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class, $len );
		$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/*
 * Activation / deactivation hooks.
 * Must reference the class by FQCN; WordPress calls them at install/uninstall.
 */
register_activation_hook( __FILE__, array( \GEO_Forge\Install\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \GEO_Forge\Install\Installer::class, 'deactivate' ) );

/*
 * Boot on `plugins_loaded` so we can check for WooCommerce first.
 * If WC is missing, we show a notice and do NOT boot — keeps the site usable
 * for shop owners who accidentally deactivate WC.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>'
						. esc_html__( 'GEO Forge requires WooCommerce to be installed and active.', 'geo-forge' )
						. '</p></div>';
				}
			);
			return;
		}

		\GEO_Forge\GeoForge::instance();
	}
);
