<?php
/**
 * Byte-safe backup and restore helpers for Fixer file operations.
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Fixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FileBackup {

	private const SUFFIX = '.geo-forge-backup';

	/**
	 * Build the rollback backup path for a target file.
	 */
	public static function path( string $file ): string {
		return $file . self::SUFFIX;
	}

	/**
	 * Whether a rollback backup currently exists.
	 */
	public static function exists( string $file ): bool {
		return file_exists( self::path( $file ) );
	}

	/**
	 * Backup a file before it is changed or removed.
	 *
	 * The backup is verified by SHA-256 before success is returned. If a
	 * backup already exists, it is reused only when it is byte-identical to
	 * the current file.
	 *
	 * @return array{success:bool,message:string,path?:string}
	 */
	public static function create( string $file ): array {
		if ( ! file_exists( $file ) ) {
			return array(
				'success' => true,
				'message' => __( 'No original file exists; no backup was needed.', 'geo-forge' ),
			);
		}

		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			return array(
				'success' => false,
				'message' => __( 'The original file is not readable; refusing to change it.', 'geo-forge' ),
			);
		}

		$original = file_get_contents( $file );
		if ( false === $original ) {
			return array(
				'success' => false,
				'message' => __( 'Could not read the original file; refusing to change it.', 'geo-forge' ),
			);
		}

		$backup       = self::path( $file );
		$original_hash = hash( 'sha256', $original );

		if ( file_exists( $backup ) ) {
			$existing = file_get_contents( $backup );
			if ( false !== $existing && hash_equals( $original_hash, hash( 'sha256', $existing ) ) ) {
				return array(
					'success' => true,
					'message' => __( 'Existing byte-identical backup reused.', 'geo-forge' ),
					'path'    => $backup,
				);
			}

			return array(
				'success' => false,
				'message' => __( 'A different backup already exists; refusing to overwrite it.', 'geo-forge' ),
				'path'    => $backup,
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $backup, $original, LOCK_EX );
		if ( false === $written || strlen( $original ) !== $written ) {
			return array(
				'success' => false,
				'message' => __( 'Could not write the backup file; refusing to change the original.', 'geo-forge' ),
			);
		}

		$backup_hash = hash_file( 'sha256', $backup );
		if ( false === $backup_hash || ! hash_equals( $original_hash, $backup_hash ) ) {
			unlink( $backup );
			return array(
				'success' => false,
				'message' => __( 'Backup verification failed; refusing to change the original.', 'geo-forge' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Original file backed up.', 'geo-forge' ),
			'path'    => $backup,
		);
	}

	/**
	 * Restore a file from its byte-safe backup.
	 *
	 * Restore is verified by SHA-256 before the backup is removed. This is
	 * the executable rollback path used by Fixer::rollback() and the
	 * `wp geo-forge rollback <fix-id>` command.
	 *
	 * @return array{success:bool,message:string}
	 */
	public static function restore( string $file ): array {
		$backup = self::path( $file );
		if ( ! file_exists( $backup ) || ! is_file( $backup ) || ! is_readable( $backup ) ) {
			return array(
				'success' => false,
				'message' => __( 'No readable backup exists for this file.', 'geo-forge' ),
			);
		}

		$original = file_get_contents( $backup );
		if ( false === $original ) {
			return array(
				'success' => false,
				'message' => __( 'Could not read the backup file.', 'geo-forge' ),
			);
		}

		if ( ! is_writable( dirname( $file ) ) ) {
			return array(
				'success' => false,
				'message' => __( 'The file directory is not writable; rollback failed.', 'geo-forge' ),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $file, $original, LOCK_EX );
		if ( false === $written || strlen( $original ) !== $written ) {
			return array(
				'success' => false,
				'message' => __( 'Could not write the restored file.', 'geo-forge' ),
			);
		}

		$restored_hash = hash_file( 'sha256', $file );
		if ( false === $restored_hash || ! hash_equals( hash( 'sha256', $original ), $restored_hash ) ) {
			return array(
				'success' => false,
				'message' => __( 'Restore verification failed; the backup was kept for retry.', 'geo-forge' ),
			);
		}

		if ( ! unlink( $backup ) ) {
			return array(
				'success' => true,
				'message' => __( 'Original file restored, but the backup file could not be removed.', 'geo-forge' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Original file restored from backup.', 'geo-forge' ),
		);
	}
}
