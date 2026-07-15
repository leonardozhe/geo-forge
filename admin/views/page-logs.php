<?php
/**
 * Logs view.
 *
 * Shows recent log rows from `wp_geo_forge_logs`, newest first.
 * Filterable by level via GET param. "Clear logs" button POSTs to REST.
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GEO_Forge\Log\Level;
use GEO_Forge\Log\Logger;

$filter_level = isset( $_GET['level'] ) ? Level::tryFrom( sanitize_text_field( wp_unslash( $_GET['level'] ) ) ) : null;
$rows         = Logger::recent( 200, $filter_level );
$row_count    = count( $rows );

?>
<div class="wrap geo-forge-wrap">
	<h1><?php esc_html_e( 'GEO Forge — Logs', 'geo-forge' ); ?></h1>

	<p class="geo-forge-muted">
		<?php
		printf(
			/* translators: %s: minimum level */
			esc_html__( 'Showing entries at or above "%s". Configure the threshold in the filter below. Auto-pruned after %d days.', 'geo-forge' ),
			esc_html( ( get_option( 'geo_forge_log_min_level', 'warning' ) ) ),
			(int) get_option( 'geo_forge_log_retention_days', 30 )
		);
		?>
	</p>

	<form method="get" class="geo-forge-log-filter">
		<input type="hidden" name="page" value="geo-forge-logs" />
		<label for="geo-forge-level">
			<?php esc_html_e( 'Minimum level:', 'geo-forge' ); ?>
		</label>
		<select id="geo-forge-level" name="level" onchange="this.form.submit()">
			<option value=""><?php esc_html_e( 'All', 'geo-forge' ); ?></option>
			<?php foreach ( Level::cases() as $lvl ) : ?>
				<option value="<?php echo esc_attr( $lvl->value ); ?>" <?php selected( $filter_level?->value, $lvl->value ); ?>>
					<?php echo esc_html( $lvl->label() ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit" class="button"><?php esc_html_e( 'Filter', 'geo-forge' ); ?></button></noscript>
	</form>

	<div class="geo-forge-log-actions">
		<button type="button" id="geo-forge-clear-logs" class="button button-secondary">
			<?php esc_html_e( 'Clear all logs', 'geo-forge' ); ?>
		</button>
		<span id="geo-forge-clear-status" class="geo-forge-muted" aria-live="polite"></span>
	</div>

	<?php if ( 0 === $row_count ) : ?>
		<div class="geo-forge-card">
			<p class="geo-forge-muted">
				<?php esc_html_e( 'No log entries match this filter.', 'geo-forge' ); ?>
			</p>
		</div>
	<?php else : ?>
		<table class="widefat striped geo-forge-log-table">
			<thead>
				<tr>
					<th style="width:140px;"><?php esc_html_e( 'Time (UTC)', 'geo-forge' ); ?></th>
					<th style="width:90px;"><?php esc_html_e( 'Level', 'geo-forge' ); ?></th>
					<th style="width:180px;"><?php esc_html_e( 'Source', 'geo-forge' ); ?></th>
					<th><?php esc_html_e( 'Message', 'geo-forge' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Request', 'geo-forge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) :
					$level      = Level::tryFrom( (string) $row['level'] ) ?? Level::Debug;
					$context    = is_array( $row['context'] ) ? $row['context'] : array();
					$ctx_json   = wp_json_encode( $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
				?>
					<tr class="geo-forge-log-row <?php echo esc_attr( $level->css_class() ); ?>">
						<td><code><?php echo esc_html( $row['created_at'] ); ?></code></td>
						<td>
							<span class="geo-forge-log-badge <?php echo esc_attr( $level->css_class() ); ?>">
								<?php echo esc_html( $level->label() ); ?>
							</span>
						</td>
						<td><code><?php echo esc_html( $row['source'] ); ?></code></td>
						<td>
							<div class="geo-forge-log-message"><?php echo esc_html( $row['message'] ); ?></div>
							<?php if ( ! empty( $context ) ) : ?>
								<details class="geo-forge-log-context">
									<summary><?php esc_html_e( 'Context', 'geo-forge' ); ?></summary>
									<pre><?php echo esc_html( (string) $ctx_json ); ?></pre>
								</details>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( substr( (string) $row['request_id'], 0, 12 ) ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="geo-forge-muted">
			<?php
			printf(
				/* translators: %d: number of entries */
				esc_html__( 'Showing the most recent %d entries.', 'geo-forge' ),
				$row_count
			);
			?>
		</p>
	<?php endif; ?>
</div>
