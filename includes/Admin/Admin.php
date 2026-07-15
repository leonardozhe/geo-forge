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
			'slug'     => 'geo-forge-fixes',
			'title'    => 'Optimizations',
			'view'     => 'page-fix-center',
			'callback' => 'render_fix_center',
		),
		array(
			'slug'     => 'geo-forge-traffic',
			'title'    => 'Traffic',
			'view'     => 'page-traffic',
			'callback' => 'render_traffic',
		),
		array(
			'slug'     => 'geo-forge-settings',
			'title'    => 'Settings',
			'view'     => 'page-settings',
			'callback' => 'render_settings',
		),
		array(
			'slug'     => 'geo-forge-logs',
			'title'    => 'Logs',
			'view'     => 'page-logs',
			'callback' => 'render_logs',
		),
	);

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Wire the settings form handler (POST via admin-post.php).
		Settings::register();
	}

	/**
	 * Register top-level menu + submenu pages.
	 */
	public function register_menus(): void {
		// Top-level menu.
		add_menu_page(
			$this->page_title( 'Dashboard' ),
			'GEO Forge',
			'manage_woocommerce',
			'geo-forge',
			array( $this, 'render_dashboard' ),
			'dashicons-superhero-alt',
			58
		);

		// Submenu pages.
		foreach ( self::SUBPAGES as $page ) {
			add_submenu_page(
				'geo-forge',
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

	public function render_fix_center(): void {
		$this->render_view( 'page-fix-center' );
	}

	public function render_traffic(): void {
		$this->render_view( 'page-traffic' );
	}

	public function render_llms_editor(): void {
		$this->render_view( 'page-llms-editor' );
	}

	public function render_logs(): void {
		$this->render_view( 'page-logs' );
	}

	/**
	 * Enqueue admin CSS/JS.
	 * ONLY on our own pages — never site-wide.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		// Inter font (Stripi design system uses Sohne, we use Inter as open-source substitute)
		wp_enqueue_style(
			'inter-font',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400&display=swap',
			array(),
			null
		);

		// Our custom styles (self-contained, no external CSS framework dependency)
		wp_enqueue_style(
			'geo-forge-admin',
			GEO_FORGE_URL . 'assets/admin/css/admin.css',
			array( 'inter-font' ),
			GEO_FORGE_VERSION
		);

		// Shared data for any admin JS module: REST root + nonce + i18n strings.
		$shared = array(
			'restRoot'  => esc_url_raw( rest_url( 'geo-forge/v1/' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'i18n'      => array(
				'scanning'    => __( 'Scanning… this takes 15–90 seconds. The result will appear here when done.', 'geo-forge' ),
				'scanFailed'  => __( 'Scan failed.', 'geo-forge' ),
				'checking'    => __( 'Checking…', 'geo-forge' ),
				'ok'          => __( 'Connected.', 'geo-forge' ),
				'failed'      => __( 'Connection failed — check your API key.', 'geo-forge' ),
				'unknownError'=> __( 'An unknown error occurred.', 'geo-forge' ),
			),
		);

		// Page-specific JS.
		if ( str_contains( $hook, 'geo-forge-settings' ) ) {
			wp_enqueue_script(
				'geo-forge-settings',
				GEO_FORGE_URL . 'assets/admin/js/settings.js',
				array(),
				GEO_FORGE_VERSION,
				true
			);
			wp_localize_script( 'geo-forge-settings', 'GeoForgeSettings', $shared );
		}

		if ( str_contains( $hook, 'geo-forge-logs' ) ) {
			wp_enqueue_script(
				'geo-forge-logs',
				GEO_FORGE_URL . 'assets/admin/js/logs.js',
				array(),
				GEO_FORGE_VERSION,
				true
			);
			wp_localize_script( 'geo-forge-logs', 'GeoForgeLogs', $shared + array(
				'i18n' => $shared['i18n'] + array(
					'confirmClear' => __( 'Clear all log entries? This cannot be undone.', 'geo-forge' ),
					'cleared'      => __( 'Logs cleared.', 'geo-forge' ),
				),
			) );
		}

		if ( str_contains( $hook, 'geo-forge-llms' ) ) {
			wp_enqueue_script(
				'geo-forge-llms-editor',
				GEO_FORGE_URL . 'assets/admin/js/llms-editor.js',
				array(),
				GEO_FORGE_VERSION,
				true
			);
			wp_localize_script( 'geo-forge-llms-editor', 'GeoForgeLlms', $shared );
		}

		if ( str_contains( $hook, 'geo-forge-fixes' ) ) {
			wp_enqueue_script(
				'geo-forge-fixer',
				GEO_FORGE_URL . 'assets/admin/js/fix-center.js',
				array(),
				GEO_FORGE_VERSION,
				true
			);
			wp_localize_script( 'geo-forge-fixer', 'GeoForgeFixer', $shared );
		}

		// Dashboard gets its own JS (only on the main dashboard page, not settings/fixes/etc).
		if ( ! str_contains( $hook, 'geo-forge-settings' ) && ! str_contains( $hook, 'geo-forge-logs' ) && ! str_contains( $hook, 'geo-forge-llms' ) && ! str_contains( $hook, 'geo-forge-fixes' ) && str_contains( $hook, 'geo-forge' ) ) {
			wp_enqueue_script(
				'geo-forge-dashboard',
				GEO_FORGE_URL . 'assets/admin/js/dashboard.js',
				array(),
				GEO_FORGE_VERSION,
				true
			);
			wp_localize_script( 'geo-forge-dashboard', 'GeoForgeDashboard', $shared );
		}
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
