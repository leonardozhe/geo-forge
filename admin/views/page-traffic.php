<?php
/**
 * Traffic monitoring view.
 *
 * Shows:
 *   - 24h summary stat tiles (total hits, top bot family)
 *   - 14-day trend as simple CSS bars (no external chart library)
 *   - Recent traffic table with filters
 *
 * @package GEO_Forge
 * @var \GEO_Forge\Admin\Admin $admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GEO_Forge\Traffic\BotFamily;
use GEO_Forge\Traffic\Store as TrafficStore;

$filter_family = isset( $_GET['family'] ) ? BotFamily::tryFrom( sanitize_text_field( wp_unslash( $_GET['family'] ) ) ) : null;
$filter_source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : null;

$valid_sources = array( 'bot_ua', 'well_known', 'markdown' );
if ( null !== $filter_source && ! in_array( $filter_source, $valid_sources, true ) ) {
	$filter_source = null;
}

$rows      = TrafficStore::recent( 100, $filter_family, $filter_source );
$summary   = TrafficStore::summary_24h();
$chart     = TrafficStore::chart_data( 14 );

$top_family     = null;
$top_family_max = 0;
foreach ( $summary['by_family'] as $entry ) {
	if ( (int) $entry['n'] > $top_family_max ) {
		$top_family_max = (int) $entry['n'];
		$top_family     = BotFamily::tryFrom( $entry['bot_family'] );
	}
}

$max_chart = 1;
foreach ( $chart['series'] as $series ) {
	foreach ( $series as $n ) {
		if ( $n > $max_chart ) {
			$max_chart = $n;
		}
	}
}

?>
<div class="wrap geo-forge-wrap">
	<h1><?php esc_html_e( 'GEO Forge — Traffic', 'geo-forge' ); ?></h1>

	<p class="geo-forge-muted">
		<?php esc_html_e( 'Records when AI agents crawl your store: which bot, what URL, what response. IP addresses are stored as SHA-256 hashes only — never in plaintext.', 'geo-forge' ); ?>
	</p>

	<div class="geo-forge-grid">
		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Last 24h Hits', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat"><?php echo esc_html( (string) $summary['total_24h'] ); ?></p>
			<p class="geo-forge-muted"><?php esc_html_e( 'AI agent requests recorded', 'geo-forge' ); ?></p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Top Bot', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat" style="font-size: 22px;">
				<?php echo esc_html( $top_family ? $top_family->label() : '—' ); ?>
			</p>
			<p class="geo-forge-muted">
				<?php
				printf(
					/* translators: %d: request count */
					esc_html__( '%d hits in 24h', 'geo-forge' ),
					$top_family_max
				);
				?>
			</p>
		</div>

		<div class="geo-forge-card">
			<h3><?php esc_html_e( 'Sample Rate', 'geo-forge' ); ?></h3>
			<p class="geo-forge-stat">1/<?php echo esc_html( (string) (int) get_option( 'geo_forge_traffic_sample_rate', 10 ) ); ?></p>
			<p class="geo-forge-muted"><?php esc_html_e( 'for regular bot traffic (well-known + markdown = 100%)', 'geo-forge' ); ?></p>
		</div>
	</div>

	<div class="geo-forge-card">
		<h3><?php esc_html_e( 'Trend (last 14 days)', 'geo-forge' ); ?></h3>
		<?php if ( empty( array_filter( $chart['series'] ) ) ) : ?>
			<p class="geo-forge-muted"><?php esc_html_e( 'No traffic recorded yet.', 'geo-forge' ); ?></p>
		<?php else : ?>
			<table class="geo-forge-chart">
				<?php foreach ( $chart['series'] as $family_key => $series ) :
					$family = BotFamily::tryFrom( $family_key );
					$label  = $family ? $family->label() : $family_key;
				?>
					<tr>
						<td class="geo-forge-chart-label"><?php echo esc_html( $label ); ?></td>
						<td class="geo-forge-chart-bars">
							<?php foreach ( $series as $n ) :
								$pct = $max_chart > 0 ? (int) round( ( $n / $max_chart ) * 100 ) : 0;
							?>
								<span class="geo-forge-chart-bar" style="height: <?php echo max( 2, $pct ); ?>%;" title="<?php echo esc_attr( $n . ' hits' ); ?>"></span>
							<?php endforeach; ?>
						</td>
						<td class="geo-forge-chart-total"><?php echo esc_html( (string) array_sum( $series ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr class="geo-forge-chart-axis">
					<td></td>
					<td>
						<?php
						$axis = $chart['labels'];
						$show = array( $axis[0], $axis[ (int) ( count( $axis ) / 2 ) ], $axis[ count( $axis ) - 1 ] );
						?>
						<div class="geo-forge-chart-axis-labels">
							<?php foreach ( $show as $i => $label ) : ?>
								<span style="flex: <?php echo $i === 0 ? 1 : 2; ?>;"><?php echo esc_html( substr( $label, 5 ) ); ?></span>
							<?php endforeach; ?>
						</div>
					</td>
					<td></td>
				</tr>
			</table>
		<?php endif; ?>
	</div>

	<form method="get" class="geo-forge-log-filter">
		<input type="hidden" name="page" value="geo-forge-traffic" />
		<label for="geo-forge-family"><?php esc_html_e( 'Bot family:', 'geo-forge' ); ?></label>
		<select id="geo-forge-family" name="family" onchange="this.form.submit()">
			<option value=""><?php esc_html_e( 'All', 'geo-forge' ); ?></option>
			<?php foreach ( BotFamily::cases() as $fam ) : ?>
				<option value="<?php echo esc_attr( $fam->value ); ?>" <?php selected( $filter_family?->value, $fam->value ); ?>>
					<?php echo esc_html( $fam->label() ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="geo-forge-source"><?php esc_html_e( 'Source:', 'geo-forge' ); ?></label>
		<select id="geo-forge-source" name="source" onchange="this.form.submit()">
			<option value=""><?php esc_html_e( 'All', 'geo-forge' ); ?></option>
			<option value="bot_ua" <?php selected( $filter_source, 'bot_ua' ); ?>><?php esc_html_e( 'Bot User-Agent', 'geo-forge' ); ?></option>
			<option value="well_known" <?php selected( $filter_source, 'well_known' ); ?>><?php esc_html_e( 'Well-known route', 'geo-forge' ); ?></option>
			<option value="markdown" <?php selected( $filter_source, 'markdown' ); ?>><?php esc_html_e( 'Markdown negotiation', 'geo-forge' ); ?></option>
		</select>
		<noscript><button type="submit" class="button"><?php esc_html_e( 'Filter', 'geo-forge' ); ?></button></noscript>
	</form>

	<?php if ( empty( $rows ) ) : ?>
		<div class="geo-forge-card">
			<p class="geo-forge-muted"><?php esc_html_e( 'No traffic matches this filter.', 'geo-forge' ); ?></p>
		</div>
	<?php else : ?>
		<table class="widefat striped geo-forge-traffic-table">
			<thead>
				<tr>
					<th style="width:140px;"><?php esc_html_e( 'Time (UTC)', 'geo-forge' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Bot Family', 'geo-forge' ); ?></th>
					<th style="width:90px;"><?php esc_html_e( 'Source', 'geo-forge' ); ?></th>
					<th style="width:60px;"><?php esc_html_e( 'Status', 'geo-forge' ); ?></th>
					<th><?php esc_html_e( 'URL', 'geo-forge' ); ?></th>
					<th style="width:90px;" title="<?php esc_attr_e( 'SHA-256 of IP, first 10 chars', 'geo-forge' ); ?>"><?php esc_html_e( 'IP hash', 'geo-forge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) :
					$family = BotFamily::tryFrom( (string) $row['bot_family'] );
				?>
					<tr>
						<td><code><?php echo esc_html( $row['recorded_at'] ); ?></code></td>
						<td><?php echo esc_html( $family ? $family->label() : $row['bot_family'] ); ?></td>
						<td><code><?php echo esc_html( $row['source'] ); ?></code></td>
						<td><code><?php echo esc_html( (string) $row['response_status'] ); ?></code></td>
						<td class="geo-forge-url-cell">
							<a href="<?php echo esc_url( $row['request_url'] ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( wp_parse_url( $row['request_url'], PHP_URL_PATH ) ?: '/' ); ?>
							</a>
						</td>
						<td><code><?php echo esc_html( substr( (string) $row['remote_ip_hash'], 0, 10 ) ); ?>…</code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="geo-forge-muted">
			<?php
			printf(
				/* translators: %d: number of entries */
				esc_html__( 'Showing the most recent %d entries.', 'geo-forge' ),
				count( $rows )
			);
			?>
		</p>
	<?php endif; ?>
</div>
