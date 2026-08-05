<?php
/**
 * Major SEO plugin detection + surface ownership.
 *
 * GEO Forge never fights another plugin that already owns a surface
 * (llms.txt, robots.txt). When a major SEO plugin manages it — or a
 * physical file sits in the web root — GEO Forge switches that fix to
 * audit-only mode: it does not register competing routes and does not
 * write content. The GEO KAMI scan remains the audit.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SeoDetector {

	public const PLUGIN_NONE      = '';
	public const PLUGIN_RANK_MATH = 'rank-math';
	public const PLUGIN_YOAST     = 'yoast';
	public const PLUGIN_SEOPRESS  = 'seopress';
	public const PLUGIN_AIOSEO    = 'aioseo';

	/** Rank Math's llms.txt module id (from its module manager). */
	private const RANK_MATH_LLMS_MODULE = 'llms-txt';

	/**
	 * Which major SEO plugin is active, if any.
	 */
	public static function active_plugin(): string {
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return self::PLUGIN_RANK_MATH;
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			return self::PLUGIN_YOAST;
		}
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return self::PLUGIN_SEOPRESS;
		}
		if ( defined( 'AIOSEO_VERSION' ) ) {
			return self::PLUGIN_AIOSEO;
		}
		return self::PLUGIN_NONE;
	}

	/**
	 * Human label for a plugin slug.
	 */
	public static function plugin_label( string $slug ): string {
		return match ( $slug ) {
			self::PLUGIN_RANK_MATH => __( 'Rank Math', 'geo-forge' ),
			self::PLUGIN_YOAST     => __( 'Yoast SEO', 'geo-forge' ),
			self::PLUGIN_SEOPRESS  => __( 'SEOPress', 'geo-forge' ),
			self::PLUGIN_AIOSEO    => __( 'All in One SEO', 'geo-forge' ),
			default                => __( 'another SEO plugin', 'geo-forge' ),
		};
	}

	/**
	 * Is Rank Math's llms.txt module active?
	 */
	public static function rank_math_provides_llms_txt(): bool {
		if ( self::active_plugin() !== self::PLUGIN_RANK_MATH ) {
			return false;
		}
		if ( ! class_exists( 'RankMath\Helper' ) ) {
			return false;
		}

		try {
			return (bool) \RankMath\Helper::is_module_active( self::RANK_MATH_LLMS_MODULE );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Who owns /llms.txt?
	 * 'geo-forge' | 'rank-math' | 'physical' | 'none'
	 */
	public static function llms_txt_owner(): string {
		if ( file_exists( ABSPATH . 'llms.txt' ) ) {
			return 'physical';
		}
		if ( self::rank_math_provides_llms_txt() ) {
			return self::PLUGIN_RANK_MATH;
		}
		return 'geo-forge';
	}

	/**
	 * Who owns /robots.txt?
	 * 'geo-forge' | major-plugin slug | 'physical'
	 */
	public static function robots_txt_owner(): string {
		if ( file_exists( ABSPATH . 'robots.txt' ) ) {
			return 'physical';
		}
		$plugin = self::active_plugin();
		return '' !== $plugin ? $plugin : 'geo-forge';
	}

	/**
	 * Human label for an owner slug (covers 'physical' too).
	 */
	public static function owner_label( string $owner ): string {
		if ( 'physical' === $owner ) {
			return __( 'a physical file in the site root', 'geo-forge' );
		}
		if ( 'geo-forge' === $owner ) {
			return __( 'GEO Forge', 'geo-forge' );
		}
		return self::plugin_label( $owner );
	}
}
