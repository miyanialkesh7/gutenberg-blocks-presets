<?php
/**
 * Admin Statistics Page Template
 *
 * This template is always `include`d from GBP_Admin::statistics_page(), so its
 * top-level variables are local to that method call at runtime, not real globals.
 * phpcs analyzes included template files statically and cannot see that, hence the
 * blanket disable below for the "unprefixed global" sniff.
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defense-in-depth: the menu registration already restricts this page to 'manage_options',
// but verify again in case this template is ever included from elsewhere.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'gutenberg-blocks-presets' ) );
}

global $wpdb;
// Table name is built from $wpdb->prefix (not user input), so it is safe to interpolate;
// $wpdb->prepare() does not support table/column identifiers as placeholders.
$table_name = $wpdb->prefix . 'gbp_block_usage';

// Get overall statistics
$total_presets = wp_count_posts( 'gbp_block_preset' )->publish;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$total_usage = $wpdb->get_var( "SELECT SUM(usage_count) FROM $table_name" );
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$most_used = $wpdb->get_results(
	"
    SELECT p.ID, p.post_title, SUM(u.usage_count) as total_usage
    FROM {$wpdb->posts} p
    LEFT JOIN $table_name u ON p.ID = u.block_id
    WHERE p.post_type = 'gbp_block_preset' AND p.post_status = 'publish'
    GROUP BY p.ID
    ORDER BY total_usage DESC
    LIMIT 10
"
);

$unused_presets = $wpdb->get_results(
	"
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    LEFT JOIN $table_name u ON p.ID = u.block_id
    WHERE p.post_type = 'gbp_block_preset'
    AND p.post_status = 'publish'
    AND u.block_id IS NULL
    ORDER BY p.post_title ASC
"
);

// Get recent usage
$recent_usage = $wpdb->get_results(
	"
    SELECT p.post_title as block_title, p2.post_title as used_on, u.last_used, u.usage_count
    FROM $table_name u
    LEFT JOIN {$wpdb->posts} p ON u.block_id = p.ID
    LEFT JOIN {$wpdb->posts} p2 ON u.post_id = p2.ID
    ORDER BY u.last_used DESC
    LIMIT 20
"
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="gbp-stats-container">
		
		<!-- Overview Cards -->
		<div class="gbp-stats-cards">
			<div class="gbp-stat-card">
				<h3><?php esc_html_e( 'Total Block Presets', 'gutenberg-blocks-presets' ); ?></h3>
				<div class="gbp-stat-number"><?php echo esc_html( $total_presets ); ?></div>
			</div>
			
			<div class="gbp-stat-card">
				<h3><?php esc_html_e( 'Total Usage Count', 'gutenberg-blocks-presets' ); ?></h3>
				<div class="gbp-stat-number"><?php echo esc_html( $total_usage ?? 0 ); ?></div>
			</div>
			
			<div class="gbp-stat-card">
				<h3><?php esc_html_e( 'Unused Presets', 'gutenberg-blocks-presets' ); ?></h3>
				<div class="gbp-stat-number"><?php echo esc_html( count( $unused_presets ) ); ?></div>
			</div>
			
			<div class="gbp-stat-card">
				<h3><?php esc_html_e( 'Average Usage', 'gutenberg-blocks-presets' ); ?></h3>
				<div class="gbp-stat-number">
					<?php echo esc_html( $total_presets > 0 ? round( ( $total_usage ?? 0 ) / $total_presets, 1 ) : 0 ); ?>
				</div>
			</div>
		</div>
		
		<div class="gbp-stats-grid">
			
			<!-- Most Used Presets -->
			<div class="gbp-stats-section">
				<h2><?php esc_html_e( 'Most Used Block Presets', 'gutenberg-blocks-presets' ); ?></h2>
				
				<?php if ( ! empty( $most_used ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Block Preset', 'gutenberg-blocks-presets' ); ?></th>
								<th><?php esc_html_e( 'Usage Count', 'gutenberg-blocks-presets' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'gutenberg-blocks-presets' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $most_used as $preset ) : ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $preset->ID ) ); ?>">
											<?php echo esc_html( $preset->post_title ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( $preset->total_usage ?? 0 ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $preset->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'gutenberg-blocks-presets' ); ?>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No usage data available yet.', 'gutenberg-blocks-presets' ); ?></p>
				<?php endif; ?>
			</div>
			
			<!-- Recent Activity -->
			<div class="gbp-stats-section">
				<h2><?php esc_html_e( 'Recent Usage Activity', 'gutenberg-blocks-presets' ); ?></h2>
				
				<?php if ( ! empty( $recent_usage ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Block Preset', 'gutenberg-blocks-presets' ); ?></th>
								<th><?php esc_html_e( 'Used On', 'gutenberg-blocks-presets' ); ?></th>
								<th><?php esc_html_e( 'Last Used', 'gutenberg-blocks-presets' ); ?></th>
								<th><?php esc_html_e( 'Count', 'gutenberg-blocks-presets' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_usage as $usage ) : ?>
							<tr>
								<td><?php echo esc_html( $usage->block_title ); ?></td>
								<td><?php echo esc_html( $usage->used_on ); ?></td>
								<td>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: Human-readable time difference, e.g. "2 hours". */
											__( '%s ago', 'gutenberg-blocks-presets' ),
											human_time_diff( strtotime( $usage->last_used ), time() )
										)
									);
									?>
								</td>
								<td><?php echo esc_html( $usage->usage_count ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No recent usage activity.', 'gutenberg-blocks-presets' ); ?></p>
				<?php endif; ?>
			</div>
			
		</div>
		
		<!-- Unused Presets -->
		<?php if ( ! empty( $unused_presets ) ) : ?>
		<div class="gbp-stats-section gbp-full-width">
			<h2><?php esc_html_e( 'Unused Block Presets', 'gutenberg-blocks-presets' ); ?></h2>
			<p><?php esc_html_e( 'These block presets have never been used. Consider reviewing them for relevance.', 'gutenberg-blocks-presets' ); ?></p>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Block Preset', 'gutenberg-blocks-presets' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'gutenberg-blocks-presets' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $unused_presets as $preset ) : ?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( get_edit_post_link( $preset->ID ) ); ?>">
									<?php echo esc_html( $preset->post_title ); ?>
								</a>
							</strong>
						</td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $preset->ID ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'gutenberg-blocks-presets' ); ?>
							</a>
							<a href="<?php echo esc_url( get_permalink( $preset->ID ) ); ?>" class="button button-small" target="_blank">
								<?php esc_html_e( 'Preview', 'gutenberg-blocks-presets' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		
		<!-- Usage by Month Chart -->
		<div class="gbp-stats-section gbp-full-width">
			<h2><?php esc_html_e( 'Usage Trends', 'gutenberg-blocks-presets' ); ?></h2>
			
			<?php
			// Get usage by month for the last 12 months
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$monthly_usage = $wpdb->get_results(
				"
                SELECT DATE_FORMAT(last_used, '%Y-%m') as month, SUM(usage_count) as total_usage
                FROM $table_name
                WHERE last_used >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(last_used, '%Y-%m')
                ORDER BY month ASC
            "
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! empty( $monthly_usage ) ) :
				?>
				<div class="gbp-chart-container">
					<canvas id="gbp-usage-chart" width="400" height="200"></canvas>
				</div>
				
				<script>
				// Simple chart implementation (you might want to use Chart.js or similar)
				document.addEventListener('DOMContentLoaded', function() {
					const canvas = document.getElementById('gbp-usage-chart');
					const ctx = canvas.getContext('2d');
					
					// Chart data
					const data = <?php echo wp_json_encode( $monthly_usage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
					
					// Draw simple bar chart
					const padding = 40;
					const chartWidth = canvas.width - (padding * 2);
					const chartHeight = canvas.height - (padding * 2);
					const barWidth = chartWidth / data.length;
					const maxValue = Math.max(...data.map(d => parseInt(d.total_usage)));
					
					// Clear canvas
					ctx.clearRect(0, 0, canvas.width, canvas.height);
					
					// Draw bars
					data.forEach((item, index) => {
						const barHeight = (parseInt(item.total_usage) / maxValue) * chartHeight;
						const x = padding + (index * barWidth);
						const y = canvas.height - padding - barHeight;
						
						ctx.fillStyle = '#0073aa';
						ctx.fillRect(x + 5, y, barWidth - 10, barHeight);
						
						// Draw labels
						ctx.fillStyle = '#333';
						ctx.font = '12px Arial';
						ctx.textAlign = 'center';
						ctx.fillText(item.month, x + (barWidth / 2), canvas.height - 10);
						ctx.fillText(item.total_usage, x + (barWidth / 2), y - 5);
					});
				});
				</script>
			<?php else : ?>
				<p><?php esc_html_e( 'No usage trend data available yet.', 'gutenberg-blocks-presets' ); ?></p>
			<?php endif; ?>
		</div>
		
	</div>
</div>
