<?php
/**
 * Contract every fix action must satisfy.
 *
 * The Fixer engine enumerates these at boot, queries their current status,
 * and dispatches apply/rollback/verify calls. A fix is a pure unit of work:
 * given a site, it either applies or rolls back a single, reversible change.
 *
 * Implementations live in `Fixer/Actions/`. To add a new fix:
 *   1. Create a class implementing this interface.
 *   2. Register it via `Fixer::register()` in GeoForge::register_hooks().
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface FixInterface {

	/**
	 * Stable identifier used in REST paths and DB rows.
	 * Lowercase snake_case. e.g. 'llms_txt', 'security_txt'.
	 */
	public function get_id(): string;

	/** Human label (translatable). */
	public function get_label(): string;

	/** Short description shown in the Fix Center UI (translatable). */
	public function get_description(): string;

	/**
	 * Risk tier. Drives which auto-fix policies will touch this action.
	 *   'none'   — no observable side-effect (regenerating cache)
	 *   'low'    — adds content, changes nothing existing
	 *   'medium' — modifies HTTP behavior or public-facing files
	 *   'high'   — rewrites existing content or server config
	 */
	public function get_risk_level(): string;

	/** Priority band shown in UI. Lower = more important. */
	public function get_priority(): int;

	/**
	 * GEO KAMI check IDs this fix is intended to improve.
	 * Used by `verify()` to tell the API which checks to re-run.
	 *
	 * @return string[]
	 */
	public function get_check_ids(): array;

	/**
	 * Current status: 'pending', 'applied', 'failed', 'rolled_back'.
	 * Implementations decide — usually by inspecting an option or DB row.
	 */
	public function get_status(): string;

	/**
	 * Apply the fix.
	 *
	 * @return array{success:bool, message:string, score_change?:int}
	 */
	public function apply(): array;

	/**
	 * Undo the fix. Should be a no-op if not applied.
	 *
	 * @return array{success:bool, message:string}
	 */
	public function rollback(): array;

	/**
	 * Optional verification — typically calls GEO KAMI /verify.
	 * Return empty array if verification isn't implemented.
	 *
	 * @return array{verified?:bool, new_score?:int, message?:string}
	 */
	public function verify(): array;
}
