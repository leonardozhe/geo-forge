<?php
/**
 * Known AI bot family identifiers.
 *
 * Used to tag captured traffic with a stable, human-readable family name.
 * User-Agent matching happens in `Traffic\Capture::detect()` — this enum
 * is the canonical label set, not the matcher itself.
 *
 * `Unknown` is the fallback for requests that matched some signal (e.g.
 * markdown Accept header) but didn't match any known bot pattern.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Traffic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum BotFamily: string {
	case OpenAI         = 'openai';
	case Anthropic      = 'anthropic';
	case Perplexity     = 'perplexity';
	case Google         = 'google';
	case Bytedance      = 'bytedance';
	case Amazon         = 'amazon';
	case CommonCrawl    = 'commoncrawl';
	case Cohere         = 'cohere';
	case Facebook       = 'facebook';
	case Apple          = 'apple';
	case Other          = 'other';
	case Unknown        = 'unknown';

	/** Display label for UI. */
	public function label(): string {
		return match ( $this ) {
			self::OpenAI      => 'OpenAI',
			self::Anthropic   => 'Anthropic',
			self::Perplexity  => 'Perplexity',
			self::Google      => 'Google',
			self::Bytedance   => 'ByteDance',
			self::Amazon      => 'Amazon',
			self::CommonCrawl => 'Common Crawl',
			self::Cohere      => 'Cohere',
			self::Facebook    => 'Facebook',
			self::Apple       => 'Apple',
			self::Other       => 'Other known',
			self::Unknown     => 'Unknown agent',
		};
	}

	/**
	 * Regex pattern that matches the bot's User-Agent.
	 * Order matters in Capture::detect() — first match wins.
	 *
	 * Returns null for synthetic families (Unknown/Other) that are detected
	 * by non-UA signals.
	 */
	public function ua_pattern(): ?string {
		return match ( $this ) {
			self::OpenAI      => '/\b(GPTBot|ChatGPT-User|oai-searchbot)\b/i',
			self::Anthropic   => '/\b(ClaudeBot|Claude-Web|anthropic-ai)\b/i',
			self::Perplexity  => '/\bPerplexityBot\b/i',
			self::Google      => '/\b(Google-Extended|GoogleOther|GOOGLE-EXTENDED)\b/i',
			self::Bytedance   => '/\bBytespider\b/i',
			self::Amazon      => '/\bAmazonbot\b/i',
			self::CommonCrawl => '/\bCCBot\b/i',
			self::Cohere      => '/\bcohere-ai\b/i',
			self::Facebook    => '/\b(FacebookBot|facebookexternalhit)\b/i',
			self::Apple       => '/\bApplebot-Extended\b/i',
			default           => null,
		};
	}
}
