<?php
/**
 * Scheduled jobs.
 *
 * Two events, both configurable via Settings → Automation:
 *   - geo_forge_regenerate_llms — (default on) regenerates llms.txt,
 *     llms-full.txt, security.txt and the AI-bot robots.txt rules from
 *     current store data. Pure local work — no GEO KAMI points consumed,
 *     so it runs even without an API key.
 *   - geo_forge_daily_scan      — (default on, needs API key) starts a
 *     GEO KAMI scan. Bounded so a slow scan cannot hang wp-cron forever.
 *
 * Manual edits win: any of the three text files that the user has edited
 * by hand is skipped on the next scheduled run.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Cron;

use GEO_Forge\Api\Client;
use GEO_Forge\Compat\SeoDetector;
use GEO_Forge\Install\Installer;
use GEO_Forge\Log\Logger;
use GEO_Forge\Scanner\Scanner;
use GEO_Forge\WellKnown\LlmsTxt;
use GEO_Forge\WellKnown\RobotsTxt;
use GEO_Forge\WellKnown\SecurityTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Scheduler {

	public const EVENT_REGENERATE = 'geo_forge_regenerate_llms';
	public const EVENT_SCAN       = 'geo_forge_daily_scan';
	public const EVENT_FINALIZE   = 'geo_forge_finalize_scan';

	/** Transient prefix for the per-event re-entrancy lock. */
	private const LOCK_TRANSIENT = 'geo_forge_cron_lock_';

	/**
	 * Wire events + schedules. Called from GeoForge::register_hooks().
	 */
	public static function register(): void {
		add_filter( 'cron_schedules', array( self::class, 'add_schedules' ) );
		add_action( self::EVENT_REGENERATE, array( self::class, 'run_regenerate' ) );
		add_action( self::EVENT_SCAN, array( self::class, 'run_scan' ) );
		add_action( self::EVENT_FINALIZE, array( self::class, 'run_finalize_scan' ) );

		// Self-heal after upgrades: schedule once per plugin version, on any
		// request (Installer::activate() only runs for admins).
		add_action( 'init', array( self::class, 'maybe_schedule' ) );
	}

	/**
	 * Schedule the events once per plugin version. Cheap version check, so
	 * running on every request is fine — it becomes a no-op after the first.
	 */
	public static function maybe_schedule(): void {
		$scheduled_version = (string) get_option( 'geo_forge_cron_version', '0' );
		if ( $scheduled_version === GEO_FORGE_VERSION ) {
			return;
		}

		self::schedule();
		update_option( 'geo_forge_cron_version', GEO_FORGE_VERSION, false );
	}

	/**
	 * Register the weekly interval (daily/twicedaily are core).
	 *
	 * @param array $schedules Existing WP-Cron intervals.
	 * @return array
	 */
	public static function add_schedules( array $schedules ): array {
		$schedules['geo_forge_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once weekly', 'geo-forge' ),
		);
		return $schedules;
	}

	/**
	 * (Re)schedule all events from the current settings.
	 * Call on activation and after the Settings form is saved.
	 */
	public static function schedule(): void {
		$regen_enabled = 'yes' === Installer::get_setting( 'auto_regen_llms', 'yes' );
		self::toggle_event( self::EVENT_REGENERATE, $regen_enabled, 'daily' );

		$scan_enabled = 'yes' === Installer::get_setting( 'auto_scan_enabled', 'yes' )
			&& '' !== (string) Installer::get_setting( 'api_key', '' );
		self::toggle_event( self::EVENT_SCAN, $scan_enabled, self::frequency() );
	}

	/**
	 * Remove every scheduled event. Called on deactivation and uninstall.
	 */
	public static function clear(): void {
		wp_clear_scheduled_hook( self::EVENT_REGENERATE );
		wp_clear_scheduled_hook( self::EVENT_SCAN );
		wp_clear_scheduled_hook( self::EVENT_FINALIZE );
		delete_option( 'geo_forge_pending_scan' );
	}

	/**
	 * The configured scan interval, validated against known values.
	 */
	private static function frequency(): string {
		$freq = (string) Installer::get_setting( 'scan_frequency', 'daily' );
		return in_array( $freq, array( 'daily', 'twicedaily', 'weekly' ), true ) ? $freq : 'daily';
	}

	/**
	 * Schedule or unschedule a single event based on a toggle.
	 */
	private static function toggle_event( string $event, bool $enabled, string $interval ): void {
		$next = wp_next_scheduled( $event );

		if ( $enabled && false === $next ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, $event );
			Logger::info(
				'Scheduled cron event.',
				array( 'event' => $event, 'interval' => $interval )
			);
		} elseif ( ! $enabled && false !== $next ) {
			wp_clear_scheduled_hook( $event );
			Logger::info( 'Unscheduled cron event.', array( 'event' => $event ) );
		}
	}

	/**
	 * Regenerate the well-known text files. Skips anything the user edited.
	 */
	public static function run_regenerate(): void {
		if ( ! self::acquire_lock( self::EVENT_REGENERATE ) ) {
			return;
		}

		try {
			$generated = array();

			// Audit mode: skip surfaces another plugin / physical file owns
			// (unless the user explicitly chose to override via Cover).
			$llms_owned = 'geo-forge' === SeoDetector::llms_txt_owner()
				|| 'yes' === get_option( 'geo_forge_override_llms_txt', '' );
			if ( $llms_owned ) {
				if ( ! LlmsTxt::is_manual() ) {
					LlmsTxt::regenerate_all();
					$generated[] = 'llms.txt';
				}
			} else {
				Logger::info(
					'Scheduled llms.txt regeneration skipped — managed by ' . SeoDetector::owner_label( SeoDetector::llms_txt_owner() ) . '.'
				);
			}

			if ( ! SecurityTxt::is_manual() ) {
				SecurityTxt::regenerate();
				$generated[] = 'security.txt';
			}

			$robots_owned = 'geo-forge' === SeoDetector::robots_txt_owner()
				|| 'yes' === get_option( 'geo_forge_override_robots_txt', '' );
			if ( $robots_owned ) {
				if ( ! RobotsTxt::is_manual() ) {
					RobotsTxt::regenerate();
					$generated[] = 'robots.txt';
				}
			} else {
				Logger::info(
					'Scheduled robots.txt regeneration skipped — managed by ' . SeoDetector::owner_label( SeoDetector::robots_txt_owner() ) . '.'
				);
			}

			Logger::info(
				'Scheduled regeneration complete.',
				array( 'generated' => implode( ', ', $generated ) )
			);
		} catch ( \Throwable $e ) {
			Logger::error(
				'Scheduled regeneration failed: ' . $e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		} finally {
			self::release_lock( self::EVENT_REGENERATE );
		}
	}

	/**
	 * Start a scheduled GEO KAMI scan (bounded, never hangs wp-cron).
	 */
	public static function run_scan(): void {
		if ( 'yes' !== Installer::get_setting( 'auto_scan_enabled', 'yes' ) ) {
			return;
		}

		$api = new Client();
		if ( ! $api->has_api_key() ) {
			Logger::info( 'Scheduled scan skipped — no API key configured.' );
			return;
		}

		if ( ! self::acquire_lock( self::EVENT_SCAN ) ) {
			return;
		}

		try {
			// Initiate async — no long-running polling inside wp-cron.
			$scanner = new Scanner( $api );
			$started = $scanner->start_scan();

			if ( empty( $started['scan_id'] ) ) {
				Logger::warning( 'Scheduled scan did not return a scan id.', array( 'started' => $started ) );
				return;
			}

			// Collect the result a few minutes later via a single follow-up event.
			wp_schedule_single_event( time() + 4 * MINUTE_IN_SECONDS, self::EVENT_FINALIZE );
			Logger::info( 'Scheduled scan initiated.', array( 'scan_id' => $started['scan_id'] ) );
		} catch ( \Throwable $e ) {
			Logger::warning(
				'Scheduled scan failed to start: ' . $e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		} finally {
			self::release_lock( self::EVENT_SCAN );
		}
	}

	/**
	 * Persist the result of a scheduled scan started earlier.
	 */
	public static function run_finalize_scan(): void {
		try {
			$scanner = new Scanner();
			$status  = $scanner->check_scan_status();

			Logger::info(
				'Scheduled scan finalized.',
				array( 'status' => $status['status'] ?? 'unknown' )
			);
		} catch ( \Throwable $e ) {
			Logger::warning(
				'Scheduled scan finalize failed: ' . $e->getMessage(),
				array( 'exception' => get_class( $e ) )
			);
		}
	}

	/**
	 * Prevent overlapping runs of the same event.
	 */
	private static function acquire_lock( string $event ): bool {
		if ( get_transient( self::LOCK_TRANSIENT . $event ) ) {
			return false;
		}
		set_transient( self::LOCK_TRANSIENT . $event, 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Release the re-entrancy lock.
	 */
	private static function release_lock( string $event ): void {
		delete_transient( self::LOCK_TRANSIENT . $event );
	}
}
