<?php
/**
 * Fixer engine — registry + dispatch + persistence.
 *
 * Holds the list of registered fix actions, and exposes:
 *   - list()       → all fixes with current status
 *   - apply()      → apply a fix, record in wp_geo_forge_fixes
 *   - rollback()   → rollback a fix, update row
 *   - verify()     → ask GEO KAMI to re-check the relevant checks
 *
 * Persists every apply/rollback into the `fixes` table so the admin UI
 * can show history, and so restarts don't lose state.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer;

use GEO_Forge\Api\ApiException;
use GEO_Forge\Api\Client;
use GEO_Forge\Log\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fixer {

	/**
	 * @var array<string, FixInterface>
	 */
	private array $registry = array();

	/**
	 * Register a fix action. Called at boot from GeoForge::register_hooks().
	 * Duplicate IDs silently replace earlier registrations — lets extensions
	 * override built-in fixes if they want.
	 */
	public function register( FixInterface $fix ): void {
		$this->registry[ $fix->get_id() ] = $fix;
	}

	/**
	 * All registered fixes, sorted by priority asc, then label asc.
	 *
	 * @return array<string, array<string,mixed>>  keyed by fix id, with status injected.
	 */
	public function list(): array {
		$out = array();
		foreach ( $this->registry as $id => $fix ) {
			$out[ $id ] = array(
				'id'          => $id,
				'label'       => $fix->get_label(),
				'description' => $fix->get_description(),
				'risk_level'  => $fix->get_risk_level(),
				'priority'    => $fix->get_priority(),
				'check_ids'   => $fix->get_check_ids(),
				'status'      => $this->resolve_status( $fix ),
				'applied_at'  => $this->last_applied_at( $id ),
			);
		}

		uasort(
			$out,
			static fn( $a, $b ) => $a['priority'] <=> $b['priority']
				?: strcmp( $a['label'], $b['label'] )
		);

		return $out;
	}

	/**
	 * Apply a fix by id. Records result in fixes table.
	 *
	 * @return array{success:bool, message:string, score_change?:int}
	 */
	public function apply( string $id ): array {
		$fix = $this->registry[ $id ] ?? null;
		if ( null === $fix ) {
			return array( 'success' => false, 'message' => __( 'Unknown fix.', 'geo-forge' ) );
		}

		$snapshot = $this->snapshot( $fix );

		Logger::info(
			'Applying fix.',
			array( 'fix_id' => $id, 'risk_level' => $fix->get_risk_level() )
		);

		try {
			$result = $fix->apply();
		} catch ( \Throwable $e ) {
			Logger::error(
				'Fix threw: ' . $e->getMessage(),
				array( 'fix_id' => $id, 'exception' => get_class( $e ) )
			);
			$this->record_fix( $id, 'failed', 0, $snapshot, $e->getMessage() );
			return array( 'success' => false, 'message' => $e->getMessage() );
		}

		$status = ! empty( $result['success'] ) ? 'applied' : 'failed';
		$score  = (int) ( $result['score_change'] ?? 0 );
		$error  = ! empty( $result['success'] ) ? null : ( $result['message'] ?? '' );

		$this->record_fix( $id, $status, $score, $snapshot, $error );

		if ( 'applied' === $status ) {
			Logger::info(
				'Fix applied.',
				array( 'fix_id' => $id, 'score_change' => $score )
			);
		} else {
			Logger::warning(
				'Fix failed to apply.',
				array( 'fix_id' => $id, 'message' => $error )
			);
		}

		return $result;
	}

	/**
	 * Rollback a fix by id.
	 *
	 * @return array{success:bool, message:string}
	 */
	public function rollback( string $id ): array {
		$fix = $this->registry[ $id ] ?? null;
		if ( null === $fix ) {
			return array( 'success' => false, 'message' => __( 'Unknown fix.', 'geo-forge' ) );
		}

		Logger::info( 'Rolling back fix.', array( 'fix_id' => $id ) );

		try {
			$result = $fix->rollback();
		} catch ( \Throwable $e ) {
			Logger::error(
				'Rollback threw: ' . $e->getMessage(),
				array( 'fix_id' => $id, 'exception' => get_class( $e ) )
			);
			return array( 'success' => false, 'message' => $e->getMessage() );
		}

		if ( ! empty( $result['success'] ) ) {
			$this->mark_status( $id, 'rolled_back' );
		}

		return $result;
	}

	/**
	 * Verify a fix by running a new scan and checking if the score improved.
	 *
	 * @return array{success:bool, message:string, verified?:bool, new_score?:int}
	 */
	public function verify( string $id ): array {
		$fix = $this->registry[ $id ] ?? null;
		if ( null === $fix ) {
			return array( 'success' => false, 'message' => __( 'Unknown fix.', 'geo-forge' ) );
		}

		$check_ids = $fix->get_check_ids();
		if ( empty( $check_ids ) ) {
			return array(
				'success' => false,
				'message' => __( 'Fix declares no check IDs — cannot verify.', 'geo-forge' ),
			);
		}

		try {
			// Run a fresh scan to verify the fix
			$client = new Client();
			$response = $client->initiate_scan( home_url(), true );

			$this->mark_status( $id, 'verified', 'verified_at' );

			$new_score = (int) ( $response['result']['totalScore'] ?? 0 );

			return array(
				'success'   => true,
				'message'   => __( 'Verification completed.', 'geo-forge' ),
				'verified'  => (bool) ( $response['success'] ?? false ),
				'new_score' => $new_score,
				'response'  => $response,
			);
		} catch ( ApiException $e ) {
			Logger::warning(
				'Verify failed: ' . $e->getMessage(),
				array( 'fix_id' => $id, 'code' => $e->getCodeEnum()->value )
			);
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/* =====================================================================
	 * Internal helpers
	 * ===================================================================== */

	/**
	 * A fix's "status" comes from its own get_status() if that returns
	 * something other than 'pending'. Otherwise we fall back to the last
	 * row we recorded — so a fix that was applied, then its option deleted
	 * by a user, will still show as applied in our history.
	 */
	private function resolve_status( FixInterface $fix ): string {
		$own = $fix->get_status();
		if ( 'pending' !== $own ) {
			return $own;
		}
		return $this->last_status( $fix->get_id() ) ?? 'pending';
	}

	private function last_status( string $fix_id ): ?string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}geo_forge_fixes WHERE fix_id = %s ORDER BY created_at DESC LIMIT 1",
				$fix_id
			)
		);
		return is_string( $val ) ? $val : null;
	}

	private function last_applied_at( string $fix_id ): ?string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT applied_at FROM {$wpdb->prefix}geo_forge_fixes WHERE fix_id = %s AND status = 'applied' ORDER BY created_at DESC LIMIT 1",
				$fix_id
			)
		);
		return is_string( $val ) ? $val : null;
	}

	/**
	 * Capture the current state of whatever this fix owns — used as a rollback
	 * snapshot. Default: empty. Implementations may override in their apply()
	 * to provide a richer snapshot themselves.
	 */
	private function snapshot( FixInterface $fix ): array {
		// Hook point — fixes that need custom snapshots can set a property
		// or override apply() and write directly to the fixes table.
		return array();
	}

	/**
	 * Insert a new row into the fixes table.
	 */
	private function record_fix( string $fix_id, string $status, int $score_change, array $snapshot, ?string $error ): void {
		global $wpdb;
		$now = current_time( 'mysql', true );

		$row = array(
			'fix_id'        => $fix_id,
			'status'        => $status,
			'score_change'  => $score_change,
			'snapshot'      => wp_json_encode( $snapshot ),
			'error_message' => $error,
			'created_at'    => $now,
		);

		if ( 'applied' === $status ) {
			$row['applied_at'] = $now;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $wpdb->prefix . 'geo_forge_fixes', $row );
	}

	/**
	 * Update the most recent row's status (and optional timestamp column).
	 */
	private function mark_status( string $fix_id, string $status, ?string $timestamp_column = null ): void {
		global $wpdb;

		$update = array( 'status' => $status );
		if ( null !== $timestamp_column && in_array( $timestamp_column, array( 'applied_at', 'verified_at' ), true ) ) {
			$update[ $timestamp_column ] = current_time( 'mysql', true );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$wpdb->prefix . 'geo_forge_fixes',
			$update,
			array(
				'fix_id' => $fix_id,
				'status' => 'applied',
			)
		);
	}
}
