<?php
/**
 * Robots.txt AI bot rules generator.
 *
 * Generates rules for AI crawlers (GPTBot, ClaudeBot, etc.) in robots.txt.
 * Stored in `geo_forge_robots_txt_ai_rules` option and merged with existing
 * robots.txt via filter.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\WellKnown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RobotsTxt {

	private const OPTION = 'geo_forge_robots_txt_ai_rules';

	/**
	 * Register the robots.txt filter.
	 */
	public static function register(): void {
		add_filter( 'robots_txt', array( self::class, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Merge AI bot rules into the existing robots.txt content.
	 */
	public static function filter_robots_txt( string $output, bool $is_main_site ): string {
		$rules = self::get_current();
		if ( empty( $rules ) ) {
			return $output;
		}

		return $output . "\n" . $rules;
	}

	/**
	 * Generate default AI bot rules.
	 */
	public static function generate(): string {
		$lines = array();

		$lines[] = '# GEO Forge AI Bot Rules';
		$lines[] = '# Allow AI agents to crawl and index your content';
		$lines[] = '';
		$lines[] = 'User-agent: GPTBot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: ChatGPT-User';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: ClaudeBot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: PerplexityBot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: anthropic-ai';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: Google-Extended';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: CCBot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: Amazonbot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: Bytespider';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: cohere-ai';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: FacebookBot';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = 'User-agent: Applebot-Extended';
		$lines[] = 'Allow: /';
		$lines[] = '';
		$lines[] = '# End GEO Forge AI Bot Rules';

		return implode( "\n", $lines );
	}

	/**
	 * Generate and persist.
	 */
	public static function regenerate(): string {
		$rules = self::generate();
		update_option( self::OPTION, $rules );
		return $rules;
	}

	/**
	 * Save user-edited rules.
	 */
	public static function save( string $rules ): void {
		update_option( self::OPTION, $rules );
	}

	/**
	 * Get stored rules.
	 */
	public static function get_current(): string {
		return (string) get_option( self::OPTION, '' );
	}

	/**
	 * Delete stored rules (rollback).
	 */
	public static function rollback(): void {
		delete_option( self::OPTION );
	}
}
