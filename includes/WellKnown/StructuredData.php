<?php
/**
 * Structured Data enhancement.
 *
 * Adds aggregateRating to Product schema WITHOUT emitting a second JSON-LD
 * block — so it stays compatible with WooCommerce core and SEO plugins
 * (Rank Math, Yoast, SEOPress) that already output Product schema.
 *
 * Two merge points, both no-ops when the rating is already present:
 *   1. woocommerce_structured_data_product — merges into WooCommerce's own
 *      Product schema (the canonical source).
 *   2. rank_math/json_ld — merges into Rank Math's schema graph when Rank
 *      Math is active.
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
		add_filter(
			'woocommerce_structured_data_product',
			array( self::class, 'merge_rating_into_wc_schema' ),
			20,
			2
		);

		if ( defined( 'RANK_MATH_VERSION' ) ) {
			add_filter(
				'rank_math/json_ld',
				array( self::class, 'merge_rating_into_rank_math_schema' ),
				90,
				2
			);
		}
	}

	/**
	 * Merge aggregateRating into WooCommerce's Product schema when missing.
	 *
	 * @param array      $markup  The Product schema array about to be output.
	 * @param \WC_Product $product The current product.
	 * @return array
	 */
	public static function merge_rating_into_wc_schema( array $markup, $product ): array {
		if ( ! self::is_enabled() ) {
			return $markup;
		}

		if ( ! empty( $markup['aggregateRating'] ) ) {
			return $markup;
		}

		$rating = self::rating_for_product( $product );
		if ( null === $rating ) {
			return $markup;
		}

		$markup['aggregateRating'] = $rating;
		return $markup;
	}

	/**
	 * Merge aggregateRating into Rank Math's schema graph when missing.
	 *
	 * @param array $data  Rank Math's schema entities (keyed by type).
	 * @param mixed $jsonld Rank Math JsonLD context (unused).
	 * @return array
	 */
	public static function merge_rating_into_rank_math_schema( array $data, $jsonld ): array {
		if ( ! self::is_enabled() ) {
			return $data;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $data;
		}

		global $product;
		$rating = self::rating_for_product( $product );
		if ( null === $rating ) {
			return $data;
		}

		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || ! empty( $entity['aggregateRating'] ) ) {
				continue;
			}
			$type  = $entity['@type'] ?? '';
			$types = is_array( $type ) ? $type : array( $type );
			if ( in_array( 'Product', $types, true ) ) {
				$data[ $key ]['aggregateRating'] = $rating;
			}
		}

		return $data;
	}

	/**
	 * Build the aggregateRating node, or null when there is nothing to rate.
	 *
	 * @param mixed $product A WC_Product or null.
	 * @return array|null
	 */
	private static function rating_for_product( $product ): ?array {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return null;
		}

		$rating_count = $product->get_rating_count();
		if ( $rating_count < 1 ) {
			return null;
		}

		$average = (float) $product->get_average_rating();
		if ( empty( $average ) ) {
			return null;
		}

		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => round( $average, 1 ),
			'reviewCount' => (int) $rating_count,
		);
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
