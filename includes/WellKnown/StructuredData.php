<?php
/**
 * Structured Data enhancement.
 *
 * Adds aggregateRating Schema to product pages that have reviews.
 * Stored in `geo_forge_structured_data_enabled` option.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StructuredData {

	private const OPTION = 'geo_forge_structured_data_enabled';

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'wp_footer', array( self::class, 'inject_schema' ) );
	}

	/**
	 * Inject aggregateRating Schema on product pages.
	 */
	public static function inject_schema(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		// Only on single product pages
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$rating_count = $product->get_rating_count();
		if ( $rating_count < 1 ) {
			return;
		}

		$average = $product->get_average_rating();
		if ( empty( $average ) ) {
			return;
		}

		$schema = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round( (float) $average, 1 ),
				'reviewCount' => (int) $rating_count,
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Check if structured data enhancement is enabled.
	 */
	public static function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION, 'no' );
	}

	/**
	 * Enable structured data enhancement.
	 */
	public static function enable(): void {
		update_option( self::OPTION, 'yes' );
	}

	/**
	 * Disable structured data enhancement (rollback).
	 */
	public static function disable(): void {
		delete_option( self::OPTION );
	}
}
