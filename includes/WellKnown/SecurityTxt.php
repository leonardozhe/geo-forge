<?php
/**
 * security.txt generator.
 *
 * Produces RFC 9116 content describing the site's security contact,
 * expiry, and (optional) PGP key. Stored in the `geo_forge_security_txt`
 * option and served by the Router at /.well-known/security.txt.
 *
 * Spec: https://www.rfc-editor.org/rfc/rfc9116
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

use GEO_Forge\Log\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SecurityTxt {

	private const OPTION = 'geo_forge_security_txt';

	/**
	 * Serve stored content. Returns a minimal RFC-compliant document if
	 * nothing has been generated yet — so the site always has *something*
	 * at /.well-known/security.txt even before the fix is applied.
	 */
	public static function serve(): string {
		$content = (string) get_option( self::OPTION, '' );

		if ( '' === $content ) {
			$content = self::generate();
		}

		/**
		 * Filter the served security.txt content.
		 *
		 * @param string $content The RFC 9116 content about to be served.
		 */
		return (string) apply_filters( 'geo_forge_security_txt_content', $content );
	}

	/**
	 * Build fresh content from current WordPress data.
	 */
	public static function generate(): string {
		$email = (string) get_option( 'admin_email', '' );
		$site  = home_url();
		$expires = gmdate( 'Y-m-d\TH:i:s.000\Z', strtotime( '+1 year' ) );

		$lines = array();

		if ( '' !== $email ) {
			$lines[] = 'Contact: mailto:' . $email;
		}

		$lines[] = 'Expires: ' . $expires;
		$lines[] = 'Canonical: ' . trailingslashit( $site ) . '.well-known/security.txt';

		// Preferred languages — English first, then the site's locale.
		$locale = get_locale(); // e.g. 'zh_CN'
		$langs  = array( 'en' );
		if ( ! str_starts_with( $locale, 'en' ) ) {
			$langs[] = substr( $locale, 0, 2 );
		}
		$lines[] = 'Preferred-Languages: ' . implode( ', ', $langs );

		// Optional: link to a security policy page if one exists.
		$policy_page = get_page_by_path( 'security-policy' );
		if ( $policy_page && 'publish' === $policy_page->post_status ) {
			$lines[] = 'Policy: ' . get_permalink( $policy_page );
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Generate and persist.
	 */
	public static function regenerate(): string {
		$content = self::generate();
		update_option( self::OPTION, $content );

		Logger::info(
			'security.txt regenerated.',
			array( 'bytes' => strlen( $content ) )
		);

		return $content;
	}

	/**
	 * Save user-edited content.
	 */
	public static function save( string $content ): void {
		$lines = array_map( 'rtrim', explode( "\n", $content ) );
		update_option( self::OPTION, implode( "\n", $lines ) );
		Logger::info( 'security.txt saved from editor.', array( 'bytes' => strlen( $content ) ) );
	}

	public static function get_current(): string {
		return (string) get_option( self::OPTION, '' );
	}
}
