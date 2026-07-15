	/**
	 * Fetch the latest scan row from the DB, if any.
	 */
	public function get_last_scan(): ?array {
		$cached = TransientCache::get( 'last_scan' );

		// Re-fetch if cache is missing
		if ( ! is_array( $cached ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$cached = $wpdb->get_row(
				"SELECT * FROM {$wpdb->prefix}geo_forge_scans ORDER BY created_at DESC LIMIT 1",
				ARRAY_A
			);
			if ( ! $cached ) {
				return null;
			}
			TransientCache::set( 'last_scan', $cached );
		}

		// Decode JSON fields so callers get arrays
		foreach ( array( 'category_scores', 'checks_result', 'suggestions' ) as $field ) {
			if ( isset( $cached[ $field ] ) && is_string( $cached[ $field ] ) ) {
				$decoded = json_decode( $cached[ $field ], true );
				$cached[ $field ] = is_array( $decoded ) ? $decoded : array();
			}
		}

		return $cached;
	}
}