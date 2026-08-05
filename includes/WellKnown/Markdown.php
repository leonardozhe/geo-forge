<?php
/**
 * Markdown negotiation for AI agents.
 *
 * When enabled, the site answers AI agents that ask for markdown:
 *   1. `Accept: text/markdown` on a product/page/post → markdown version.
 *   2. `/{slug}.md` variant → markdown of that page.
 *
 * Products use rich WooCommerce data (name, price, description, categories,
 * images, URL); regular pages/posts use an HTML→Markdown conversion of the
 * post content. Enabled via the `geo_forge_markdown_enabled` option (Settings
 * toggle / Fix Center "Serve markdown to AI agents").
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markdown {

	private const OPTION = 'geo_forge_markdown_enabled';

	/** Policy sent with markdown responses — matches Content-Signals default. */
	private const SIGNAL = 'Content-Signal: ai-train=yes, search=yes, ai-input=yes';

	/**
	 * Register the negotiation hook. Runs on `wp` at priority 5 — after the
	 * main query is resolved but BEFORE Rank Math's redirections (wp p10/p11),
	 * which would otherwise 301 `.md` URLs to the homepage before we can serve
	 * them. Accept/`.md` requests never reach template_redirect on sites where
	 * Rank Math redirection is active, so `wp` is the reliable place to act.
	 */
	public static function register(): void {
		add_action( 'wp', array( self::class, 'maybe_serve' ), 5 );
	}

	public static function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION, 'no' );
	}

	public static function enable(): void {
		update_option( self::OPTION, 'yes' );
	}

	public static function disable(): void {
		delete_option( self::OPTION );
	}

	/**
	 * Entry point — decide whether to serve markdown for this request.
	 */
	public static function maybe_serve(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
			return;
		}
		if ( ! self::is_enabled() ) {
			return;
		}

		// 1. /{slug}.md variant.
		$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		if ( preg_match( '#^(/.+)\.md$#', $path, $matches ) ) {
			$post = self::resolve_slug( trim( $matches[1], '/' ) );
			if ( $post instanceof \WP_Post ) {
				self::output( self::post_to_markdown( $post ) );
			}
			return; // unresolved .md → let WP handle (404)
		}

		// 2. Content negotiation: Accept: text/markdown.
		$accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ?? '' ) );
		if ( ! str_contains( $accept, 'text/markdown' ) && ! str_contains( $accept, 'text/x-markdown' ) ) {
			return;
		}

		$object = get_queried_object();
		if ( $object instanceof \WP_Post ) {
			self::output( self::post_to_markdown( $object ) );
		}
	}

	/**
	 * Resolve a URL slug to a product/page/post.
	 */
	private static function resolve_slug( string $slug ): ?\WP_Post {
		if ( '' === $slug ) {
			return null;
		}

		// Homepage aliases — /index.md, /home.md, /front.md serve the front
		// page, which is what "md variants of key pages" scanners probe.
		if ( in_array( $slug, array( 'index', 'home', 'front' ), true ) ) {
			$front_id = (int) get_option( 'page_on_front' );
			if ( $front_id ) {
				$front = get_post( $front_id );
				if ( $front instanceof \WP_Post && 'publish' === $front->post_status ) {
					return $front;
				}
			}
		}

		foreach ( array( 'product', 'page', 'post' ) as $post_type ) {
			$post = get_page_by_path( $slug, OBJECT, $post_type );
			if ( $post instanceof \WP_Post && 'publish' === $post->post_status ) {
				return $post;
			}
		}
		return null;
	}

	/**
	 * Build a markdown document for a post.
	 */
	private static function post_to_markdown( \WP_Post $post ): string {
		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$md = self::product_to_markdown( $post );
			if ( '' !== $md ) {
				return $md;
			}
		}
		return self::content_to_markdown( $post );
	}

	/**
	 * Rich markdown for a WooCommerce product.
	 */
	private static function product_to_markdown( \WP_Post $post ): string {
		$product = wc_get_product( $post );
		if ( ! $product ) {
			return '';
		}

		$lines   = array();
		$lines[] = '# ' . $product->get_name();
		$lines[] = '';

		$price = $product->get_price();
		if ( '' !== $price && null !== $price ) {
			$lines[] = '**Price:** ' . wp_strip_all_tags( wc_price( $price ) );
			$lines[] = '';
		}

		$description = (string) $product->get_short_description();
		if ( '' === $description ) {
			$description = (string) $product->get_description();
		}
		if ( '' !== $description ) {
			$lines[] = '## Description';
			$lines[] = '';
			$lines[] = self::html_to_markdown( do_shortcode( wpautop( $description ) ) );
			$lines[] = '';
		}

		$categories = wc_get_product_category_list( $product->get_id(), ', ' );
		if ( $categories ) {
			$lines[] = '**Categories:** ' . wp_strip_all_tags( $categories );
			$lines[] = '';
		}

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$src = wp_get_attachment_image_url( $image_id, 'full' );
			if ( $src ) {
				$lines[] = '![Product image](' . $src . ')';
				$lines[] = '';
			}
		}

		$lines[] = '**URL:** ' . get_permalink( $post );
		$lines[] = '';

		return implode( "\n", $lines );
	}

	/**
	 * Markdown for a regular page/post from its content.
	 */
	private static function content_to_markdown( \WP_Post $post ): string {
		$lines   = array();
		$lines[] = '# ' . get_the_title( $post );
		$lines[] = '';

		$content = (string) $post->post_content;
		$content = do_shortcode( $content );
		$content = self::html_to_markdown( $content );

		$lines[] = $content;
		$lines[] = '';
		$lines[] = '**URL:** ' . get_permalink( $post );
		$lines[] = '';

		return implode( "\n", $lines );
	}

	/**
	 * Convert HTML to markdown. Uses DOMDocument when available, otherwise a
	 * conservative regex fallback.
	 */
	private static function html_to_markdown( string $html ): string {
		$html = trim( $html );
		if ( '' === $html ) {
			return '';
		}
		if ( class_exists( 'DOMDocument' ) ) {
			return self::dom_to_markdown( $html );
		}
		return self::regex_to_markdown( $html );
	}

	/**
	 * DOM-based conversion — the primary path.
	 */
	private static function dom_to_markdown( string $html ): string {
		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();

		$xpath = new \DOMXPath( $doc );
		foreach ( array( '//script', '//style', '//noscript', '//iframe', '//form', '//button' ) as $query ) {
			foreach ( $xpath->query( $query ) as $node ) {
				if ( $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body ) {
			$body = $doc;
		}

		$out = '';
		self::convert_node( $body, $out, 0 );

		// Collapse 3+ blank lines.
		return preg_replace( "/\n{3,}/", "\n\n", trim( $out ) ) . "\n";
	}

	/**
	 * Recursively convert DOM nodes to markdown.
	 */
	private static function convert_node( \DOMNode $node, string &$out, int $depth ): void {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = preg_replace( '/[ \t]+/', ' ', (string) $node->nodeValue );
			$out .= $text;
			return;
		}
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return;
		}

		$tag = strtolower( $node->nodeName );

		switch ( $tag ) {
			case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
				$level = (int) substr( $tag, 1 );
				self::convert_children( $node, $out, $depth );
				$out = rtrim( $out ) . "\n\n";
				// Prefix the just-written text with heading markers.
				$out = preg_replace( '/^(?!#)(.+)$/m', str_repeat( '#', $level ) . ' $1', trim( $out ) ) . "\n\n";
				break;

			case 'p':
				self::convert_children( $node, $out, $depth );
				$out = rtrim( $out ) . "\n\n";
				break;

			case 'br':
				$out .= "\n";
				break;

			case 'strong': case 'b':
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$out .= '**' . trim( $inner ) . '**';
				break;

			case 'em': case 'i':
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$out .= '*' . trim( $inner ) . '*';
				break;

			case 'a':
				$href = $node->getAttribute( 'href' );
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$text = trim( $inner );
				if ( '' !== $href && '' !== $text ) {
					$out .= '[' . $text . '](' . $href . ')';
				} else {
					$out .= $text;
				}
				break;

			case 'img':
				$src = $node->getAttribute( 'src' );
				$alt = $node->getAttribute( 'alt' );
				if ( '' !== $src ) {
					$out .= '![' . $alt . '](' . $src . ')';
				}
				break;

			case 'ul':
				self::convert_list( $node, $out, $depth, false );
				break;

			case 'ol':
				self::convert_list( $node, $out, $depth, true );
				break;

			case 'blockquote':
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$quoted = preg_replace( '/^/m', '> ', trim( $inner ) );
				$out .= $quoted . "\n\n";
				break;

			case 'pre':
				$code = '';
				self::convert_children( $node, $code, $depth );
				$out .= "```\n" . trim( $code ) . "\n```\n\n";
				break;

			case 'code':
				$out .= '`' . trim( (string) $node->textContent ) . '`';
				break;

			case 'li':
				self::convert_children( $node, $out, $depth );
				$out .= "\n";
				break;

			case 'tr':
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$cells = preg_split( '/\s*\|\s*/', trim( $inner ) );
				$out .= '| ' . implode( ' | ', array_filter( $cells ) ) . " |\n";
				break;

			case 'td': case 'th':
				$inner = '';
				self::convert_children( $node, $inner, $depth );
				$out .= ' ' . trim( $inner ) . ' |';
				break;

			case 'hr':
				$out .= "---\n\n";
				break;

			default:
				self::convert_children( $node, $out, $depth );
				if ( in_array( $tag, array( 'div', 'section', 'article', 'main', 'table', 'thead', 'tbody', 'tr' ), true ) ) {
					$out = rtrim( $out ) . "\n";
				}
				break;
		}
	}

	/**
	 * Convert a <ul>/<ol> into markdown list lines.
	 */
	private static function convert_list( \DOMNode $list, string &$out, int $depth, bool $ordered ): void {
		$index = 0;
		foreach ( $list->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType && 'li' === strtolower( $child->nodeName ) ) {
				$index++;
				$prefix = $ordered ? ( $index . '. ' ) : '- ';
				$inner  = '';
				self::convert_children( $child, $inner, $depth );
				$out .= $prefix . trim( $inner ) . "\n";
			}
		}
		$out .= "\n";
	}

	/**
	 * Recurse over element children (skips the element itself).
	 */
	private static function convert_children( \DOMNode $node, string &$out, int $depth ): void {
		foreach ( $node->childNodes as $child ) {
			self::convert_node( $child, $out, $depth + 1 );
		}
	}

	/**
	 * Regex fallback when ext-dom is unavailable: strip tags, keep headings,
	 * links, images and lists as best-effort markdown.
	 */
	private static function regex_to_markdown( string $html ): string {
		$html = preg_replace( '#<(script|style|noscript|iframe|form)[^>]*>.*?</\1>#is', '', $html );
		$html = preg_replace( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', "\n\n" . str_repeat( '#', 1 ) . ' $2', $html );
		$html = preg_replace( '/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/is', '[$2]($1)', $html );
		$html = preg_replace( '/<img[^>]+src="([^"]+)"[^>]*>/i', '![image]($1)', $html );
		$html = preg_replace( '/<li[^>]*>/i', "\n- ", $html );
		$html = preg_replace( '/<(br|hr|p|div|section|article|li|tr)[^>]*>/i', "\n", $html );
		$html = preg_replace( '/<\/(p|div|section|article|li|tr|table|ul|ol|blockquote)>/i', "\n", $html );
		$html = preg_replace( '/<[^>]+>/', '', $html );
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$html = preg_replace( "/\n{3,}/", "\n\n", $html );
		return trim( $html ) . "\n";
	}

	/**
	 * Send the markdown response with negotiation-aware headers.
	 */
	private static function output( string $markdown ): void {
		if ( '' === $markdown ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Vary: Accept' );
		header( self::SIGNAL );
		header( 'Cache-Control: public, max-age=3600' );
		header( 'X-GEO-Forge: true' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated markdown.
		echo $markdown;
		exit;
	}
}
