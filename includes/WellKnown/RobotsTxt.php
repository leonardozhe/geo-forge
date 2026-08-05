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

use GEO_Forge\Compat\SeoDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RobotsTxt {

	private const OPTION        = 'geo_forge_robots_txt_ai_rules';
	private const SOURCE_OPTION = 'geo_forge_robots_txt_ai_rules_source';

	/**
	 * Register the robots.txt filter — only when GEO Forge owns robots.txt.
	 * If a major SEO plugin or a physical file manages it, we switch to
	 * audit-only mode and never fight for the output.
	 */
	public static function register(): void {
		// Yield to other owners unless the user explicitly chose to override.
		if ( 'geo-forge' !== SeoDetector::robots_txt_owner()
			&& 'yes' !== get_option( 'geo_forge_override_robots_txt', '' ) ) {
			return;
		}

		add_filter( 'robots_txt', array( self::class, 'filter_robots_txt' ), 30, 2 );
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
	 * The AI crawler user-agent tokens GEO Forge explicitly allows.
	 * Shared by the robots.txt generator and the robots audit.
	 *
	 * @return string[]
	 */
	public static function ai_user_agents(): array {
		return array(
			// OpenAI
			'GPTBot', 'OAI-SearchBot', 'OAI-SearchGPT', 'OAI-AdsBot', 'ChatGPT-User',
			// Anthropic
			'ClaudeBot', 'Claude-Web', 'Claude-SearchBot', 'Claude-User', 'anthropic-ai',
			// Perplexity
			'PerplexityBot', 'Perplexity-User',
			// Google
			'Google-Extended', 'Google-CloudVertexBot', 'Google-CloudVertex-Crawl', 'Googlebot',
			// Meta
			'meta-externalagent', 'meta-externalfetcher', 'FacebookBot',
			// xAI (Grok)
			'GrokBot', 'xAI-Grok',
			// Other major AI crawlers
			'CCBot', 'Amazonbot', 'Bytespider', 'cohere-ai', 'DuckAssistBot',
			'Applebot', 'Applebot-Extended', 'MistralAI-User', 'PanguBot',
		);
	}

	/**
	 * Generate default AI bot rules.
	 */
	public static function generate(): string {
		$lines = array();

		$lines[] = '# GEO Forge AI Bot Rules';
		$lines[] = '# Allow AI agents to crawl and index your content';
		$lines[] = '';

		foreach ( self::ai_user_agents() as $geo_forge_ua ) {
			$lines[] = 'User-agent: ' . $geo_forge_ua;
			$lines[] = 'Allow: /';
			$lines[] = '';
		}

		$lines[] = '# End GEO Forge AI Bot Rules';

		return implode( "\n", $lines );
	}

	/**
	 * Generate and persist.
	 */
	public static function regenerate(): string {
		$rules = self::generate();
		update_option( self::OPTION, $rules, false );
		update_option( self::SOURCE_OPTION, 'generated', false );
		return $rules;
	}

	/**
	 * Save user-edited rules.
	 */
	public static function save( string $rules ): void {
		update_option( self::OPTION, $rules, false );
		update_option( self::SOURCE_OPTION, 'manual', false );
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
		delete_option( self::SOURCE_OPTION );
	}

	/**
	 * Was the AI-bot block last written by the user via the editor?
	 */
	public static function is_manual(): bool {
		return 'manual' === get_option( self::SOURCE_OPTION, '' );
	}

	/**
	 * Is there a physical robots.txt in the web root? If so the web server
	 * serves it directly and the robots_txt filter never runs.
	 */
	public static function physical_file_exists(): bool {
		return file_exists( ABSPATH . 'robots.txt' );
	}
}
