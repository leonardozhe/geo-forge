<?php
/**
 * Bot family detection for AI traffic monitoring.
 *
 * Uses comprehensive AI bot list from Cloudflare and other sources:
 * https://developers.cloudflare.com/ai-crawl-control/reference/bots/
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Traffic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect AI bot family from User-Agent string.
 */
class BotFamily {

	/**
	 * Map of AI bot User-Agent patterns to family names.
	 * Order matters: more specific patterns should come first.
	 *
	 * @var array<string, string>
	 */
	private static array $bots = array(
		// OpenAI bots
		'OAI-SearchBot'      => 'openai',
		'ChatGPT Agent'      => 'openai',
		'ChatGPT-User'       => 'openai',
		'GPTBot'             => 'openai',
		'OpenAI'             => 'openai',

		// Anthropic bots
		'Claude-Code'        => 'anthropic',
		'Claude-SearchBot'   => 'anthropic',
		'Claude-User'        => 'anthropic',
		'Claude-Web'         => 'anthropic',
		'ClaudeBot'          => 'anthropic',
		'anthropic-ai'       => 'anthropic',

		// Google bots
		'Google-CloudVertexBot' => 'google',
		'Google-Extended'    => 'google',
		'Google-Firebase'    => 'google',
		'Google-Gemini-CLI'  => 'google',
		'Google-NotebookLM'  => 'google',
		'GoogleAgent-Mariner' => 'google',
		'GoogleAgent-URLContext' => 'google',
		'GoogleOther'        => 'google',
		'GoogleOther-Image'  => 'google',
		'GoogleOther-Video'  => 'google',
		'CloudVertexBot'     => 'google',
		'Gemini-Deep-Research' => 'google',
		'NotebookLM'         => 'google',

		// Perplexity bots
		'Perplexity-User'    => 'perplexity',
		'PerplexityBot'      => 'perplexity',

		// Meta bots
		'Meta-ExternalAgent' => 'meta',
		'meta-externalagent' => 'meta',
		'Meta-ExternalFetcher' => 'meta',
		'meta-externalfetcher' => 'meta',
		'meta-webindexer'    => 'meta',
		'FacebookBot'        => 'meta',
		'facebookexternalhit' => 'meta',

		// Microsoft/Bing bots
		'bingbot'            => 'bing',
		'AzureAI-SearchBot'  => 'bing',
		'amazon-kendra'      => 'amazon',
		'amazon-QBusiness'   => 'amazon',

		// Amazon bots
		'AmazonBuyForMe'     => 'amazon',
		'Amazonbot'          => 'amazon',
		'Amzn-SearchBot'     => 'amazon',
		'Amzn-User'          => 'amazon',
		'bedrockbot'         => 'amazon',

		// ByteDance bots
		'Bytespider'         => 'bytedance',
		'TikTokSpider'       => 'bytedance',

		// Common Crawl
		'CCBot'              => 'commoncrawl',

		// Apple bots
		'Applebot'           => 'apple',
		'Applebot-Extended'  => 'apple',

		// DuckDuckGo bots
		'DuckAssistBot'      => 'duckduckgo',

		// Mistral AI
		'MistralAI-User'     => 'mistral',

		// DeepSeek
		'DeepSeekBot'        => 'deepseek',

		// Cohere bots
		'cohere-ai'          => 'cohere',
		'cohere-training-data-crawler' => 'cohere',

		// AI2 bots
		'AI2Bot'             => 'ai2',
		'AI2Bot-DeepResearchEval' => 'ai2',
		'Ai2Bot-Dolma'       => 'ai2',

		// Cursor
		'Cursor'             => 'cursor',
		'opencode'           => 'cursor',
		'Operator'           => 'cursor',

		// Firecrawl
		'FirecrawlAgent'     => 'firecrawl',

		// Tavily
		'TavilyBot'          => 'tavily',

		// Exa
		'ExaBot'             => 'exa',

		// Scrapy
		'Scrapy'             => 'scrapy',

		// Brave
		'Bravebot'           => 'brave',

		// Yandex bots
		'YandexAdditional'   => 'yandex',
		'YandexAdditionalBot' => 'yandex',

		// PetalBot (Huawei)
		'PetalBot'           => 'petal',

		// Other AI bots
		'AddSearchBot'       => 'other',
		'AgentTimes'         => 'other',
		'aiHitBot'           => 'other',
		'AIWebIndex'         => 'other',
		'Andibot'            => 'other',
		'Anomura'            => 'other',
		'ApifyBot'           => 'other',
		'ApifyWebsiteContentCrawler' => 'other',
		'Aranet-SearchBot'   => 'other',
		'atlassian-bot'      => 'other',
		'Awario'             => 'other',
		'bigsur.ai'          => 'other',
		'Brightbot'          => 'other',
		'BuddyBot'           => 'other',
		'Channel3Bot'        => 'other',
		'ChatGLM-Spider'     => 'other',
		'Cloudflare-AutoRAG' => 'other',
		'Code'               => 'other',
		'Cotoyogi'           => 'other',
		'CragCrawler'        => 'other',
		'Crawl4AI'           => 'other',
		'Crawlspace'         => 'other',
		'Datenbank Crawler'  => 'other',
		'Devin'              => 'other',
		'Diffbot'            => 'other',
		'Echobot Bot'        => 'other',
		'EchoboxBot'         => 'other',
		'Factset_spyderbot'  => 'other',
		'FriendlyCrawler'    => 'other',
		'GeistHaus-PageFetcher' => 'other',
		'Google-Agent'       => 'other',
		'HenkBot'            => 'other',
		'iAskBot'            => 'other',
		'iaskspider'         => 'other',
		'IbouBot'            => 'other',
		'ICC-Crawler'        => 'other',
		'ImagesiftBot'       => 'other',
		'imageSpider'        => 'other',
		'img2dataset'        => 'other',
		'ISSCyberRiskCrawler' => 'other',
		'kagi-fetcher'       => 'other',
		'Kangaroo Bot'       => 'other',
		'Kimi-User'          => 'other',
		'KlaviyoAIBot'       => 'other',
		'KunatoCrawler'      => 'other',
		'laion-huggingface-processor' => 'other',
		'LAIONDownloader'    => 'other',
		'LCC'                => 'other',
		'LinerBot'           => 'other',
		'Linguee Bot'        => 'other',
		'LinkupBot'          => 'other',
		'Manus-User'         => 'other',
		'Mozilla-Tabstack'   => 'other',
		'MyCentralAIScraperBot' => 'other',
		'NagetBot'           => 'other',
		'netEstate Imprint Crawler' => 'other',
		'newsai'             => 'other',
		'NovaAct'            => 'other',
		'omgili'             => 'other',
		'omgilibot'          => 'other',
		'PanguBot'           => 'other',
		'Panscient'          => 'other',
		'panscient.com'      => 'other',
		'PhindBot'           => 'other',
		'Poggio-Citations'   => 'other',
		'Poseidon Research Crawler' => 'other',
		'QualifiedBot'       => 'other',
		'Querit-SearchBot'   => 'other',
		'QueritBot'          => 'other',
		'QuillBot'           => 'other',
		'quillbot.com'       => 'other',
		'SBIntuitionsBot'    => 'other',
		'SemrushBot-OCOB'    => 'other',
		'SemrushBot-SWA'     => 'other',
		'Shap-User'          => 'other',
		'ShapBot'            => 'other',
		'Sidetrade indexer bot' => 'other',
		'Spider'             => 'other',
		'Terra Cotta'        => 'other',
		'TerraCotta'         => 'other',
		'Thinkbot'           => 'other',
		'Timpibot'           => 'other',
		'TongyiBot'          => 'other',
		'Trae'               => 'other',
		'TwinAgent'          => 'other',
		'UseAI'              => 'other',
		'VelenPublicWebCrawler' => 'other',
		'WARDBot'            => 'other',
		'Webzio-Extended'    => 'other',
		'webzio-extended'    => 'other',
		'wpbot'              => 'other',
		'WRTNBot'            => 'other',
		'YaK'                => 'other',
		'YiyanBot'           => 'other',
		'YouBot'             => 'other',
		'ZanistaBot'         => 'other',
	);

	/**
	 * Detect bot family from User-Agent string.
	 *
	 * @param string $user_agent User-Agent header value.
	 * @return string Bot family identifier or 'unknown'.
	 */
	public static function detect( string $user_agent ): string {
		if ( empty( $user_agent ) ) {
			return 'unknown';
		}

		foreach ( self::$bots as $pattern => $family ) {
			if ( stripos( $user_agent, $pattern ) !== false ) {
				return $family;
			}
		}

		return 'unknown';
	}

	/**
	 * Get human-readable label for bot family.
	 *
	 * @param string $family Bot family identifier.
	 * @return string Human-readable label.
	 */
	public static function label( string $family ): string {
		$labels = array(
			'openai'        => 'OpenAI',
			'anthropic'     => 'Anthropic (Claude)',
			'google'        => 'Google',
			'perplexity'    => 'Perplexity',
			'meta'          => 'Meta (Facebook)',
			'bing'          => 'Microsoft (Bing)',
			'amazon'        => 'Amazon',
			'bytedance'     => 'ByteDance',
			'commoncrawl'   => 'Common Crawl',
			'apple'         => 'Apple',
			'duckduckgo'    => 'DuckDuckGo',
			'mistral'       => 'Mistral AI',
			'deepseek'      => 'DeepSeek',
			'cohere'        => 'Cohere',
			'ai2'           => 'AI2',
			'cursor'        => 'Cursor',
			'firecrawl'     => 'Firecrawl',
			'tavily'        => 'Tavily',
			'exa'           => 'Exa',
			'scrapy'        => 'Scrapy',
			'brave'         => 'Brave',
			'yandex'        => 'Yandex',
			'petal'         => 'PetalBot (Huawei)',
			'other'         => 'Other AI Bot',
			'unknown'       => 'Unknown',
		);

		return $labels[ $family ] ?? 'Unknown';
	}

	/**
	 * Get all known bot families.
	 *
	 * @return array<string, string> Map of family => label.
	 */
	public static function get_all_families(): array {
		$families = array();
		foreach ( array_unique( self::$bots ) as $family ) {
			$families[ $family ] = self::label( $family );
		}
		ksort( $families );
		return $families;
	}
}
