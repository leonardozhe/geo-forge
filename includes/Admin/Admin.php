<?php
/**
 * Admin area registration.
 *
 * Responsibilities (Milestone 0):
 *   - Register submenu under WooCommerce.
 *   - Enqueue admin assets ONLY on plugin pages.
 *   - Route each page slug to its view file.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/** Top-level page slug (also used for the dashboard view). */
	private const PAGE_SLUG = 'geo-forge';

	/** Submenu pages, in display order. */
	private const SUBPAGES = array(
		array(
			'slug'     => 'geo-forge',
			'title'    => 'Dashboard',
			'view'     => 'page-dashboard',
			'callback' => 'render_dashboard',
		),
		array(
			'slug'     => 'geo-forge-settings',
			'title'    => 'Settings',
			'view'     => 'page-settings',
			'callback' => 'render_settings',
		),
	);

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Wire the settings form handler (POST via admin-post.php).
		Settings::register();
	}

	/**
	 * Register submenu pages under WooCommerce.
	 */
	public function register_menus(): void {
		foreach ( self::SUBPAGES as $page ) {
			add_submenu_page(
				'woocommerce',
				$this->page_title( $page['title'] ),
				$page['title'],
				'manage_woocommerce',
				$page['slug'],
				array( $this, $page['callback'] )
			);
		}
	}

	public function render_dashboard(): void {
		$this->render_view( 'page-dashboard' );
	}

	public function render_settings(): void {
		$this->render_view( 'page-settings' );
	}

	/**
	 * Enqueue admin CSS/JS.
	 * ONLY on our own pages — never site-wide.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'geo-forge-admin',
			GEO_FORGE_URL . 'assets/admin/css/admin.css',
			array(),
			GEO_FORGE_VERSION
		);
	}

	/**
	 * Build a full page title like "GEO Forge — Dashboard".
	 */
	private function page_title( string $subtitle ): string {
		return sprintf(
			/* translators: %s: page subtitle */
			__( 'GEO Forge — %s', 'geo-forge' ),
			$subtitle
		);
	}

	/**
	 * Load a view file from `admin/views/`.
	 * View files receive `$this` (the Admin instance) as a local variable.
	 */
	private function render_view( string $view_name ): void {
		$view_file = GEO_FORGE_DIR . 'admin/views/' . $view_name . '.php';

		if ( ! file_exists( $view_file ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html( sprintf( 'View not found: %s', $view_name ) )
				. '</p></div>';
			return;
		}

		$admin = $this; // Expose to view if needed.
		include $view_file;
	}

	/**
	 * Is the current admin screen one of ours?
	 *
	 * WP generates hook suffixes like:
	 *   - `woocommerce_page_geo-forge`          (submenu page)
	 *   - `toplevel_page_geo-forge`             (if it were top-level)
	 *   - `woocommerce_page_geo-forge-settings` (sub-submenu)
	 */
	private function is_plugin_page( string $hook ): bool {
		foreach ( self::SUBPAGES as $page ) {
			if ( str_contains( $hook, $page['slug'] ) ) {
				return true;
			}
		}
		return false;
	}
}
