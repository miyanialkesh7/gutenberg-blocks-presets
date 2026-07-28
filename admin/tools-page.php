<?php
/**
 * Admin Tools Page Template
 *
 * This template is always `include`d from GBP_Admin::tools_page(), so its
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
// but verify again since these actions are destructive (data reset, migration).
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'gutenberg-blocks-presets' ) );
}

// Handle form submissions
if (
	isset( $_POST['gbp_action'], $_POST['gbp_nonce'] )
	&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gbp_nonce'] ) ), 'gbp_tools_action' )
) {
	$gbp_action = sanitize_text_field( wp_unslash( $_POST['gbp_action'] ) );

	switch ( $gbp_action ) {
		case 'migrate_old_blocks':
			$migrated = gbp_migrate_old_blocks();
			if ( false !== $migrated ) {
				echo '<div class="notice notice-success"><p>' . esc_html(
					sprintf(
						/* translators: %d: Number of migrated block presets. */
						_n( 'Successfully migrated %d block preset from old format.', 'Successfully migrated %d block presets from old format.', $migrated, 'gutenberg-blocks-presets' ),
						$migrated
					)
				) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( __( 'Migration failed. Please check error logs.', 'gutenberg-blocks-presets' ) ) . '</p></div>';
			}
			break;

		case 'reset_usage_stats':
			if ( gbp_reset_usage_stats() ) {
				echo '<div class="notice notice-success"><p>' . esc_html( __( 'Usage statistics have been reset.', 'gutenberg-blocks-presets' ) ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( __( 'Failed to reset usage statistics.', 'gutenberg-blocks-presets' ) ) . '</p></div>';
			}
			break;

		// Note: 'export_presets' is intentionally not handled here. It sends file-download
		// headers, which must happen before any admin page HTML is output; see
		// GBP_Admin::maybe_export_presets(), hooked to 'admin_init'.
	}
}

// Check for old block posts
$old_blocks = get_posts(
	array(
		'post_type'      => 'block',
		'posts_per_page' => 1,
		'post_status'    => 'any',
	)
);

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="gbp-tools-container">
		
		<!-- Migration Tool -->
		<div class="gbp-tool-section">
			<h2><?php esc_html_e( 'Migration Tools', 'gutenberg-blocks-presets' ); ?></h2>
			
			<?php if ( ! empty( $old_blocks ) ) : ?>
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'Migrate Old Block Presets', 'gutenberg-blocks-presets' ); ?></h3>
				<p><?php esc_html_e( 'Found block presets using the old "block" post type. Click below to migrate them to the new format.', 'gutenberg-blocks-presets' ); ?></p>
				<form method="post" action="">
					<?php wp_nonce_field( 'gbp_tools_action', 'gbp_nonce' ); ?>
					<input type="hidden" name="gbp_action" value="migrate_old_blocks">
					<button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to migrate old block presets? This action cannot be undone.', 'gutenberg-blocks-presets' ); ?>')">
						<?php esc_html_e( 'Migrate Old Blocks', 'gutenberg-blocks-presets' ); ?>
					</button>
				</form>
			</div>
			<?php else : ?>
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'Migration Status', 'gutenberg-blocks-presets' ); ?></h3>
				<p class="gbp-success"><?php esc_html_e( '✓ No old block presets found. Migration is complete or not needed.', 'gutenberg-blocks-presets' ); ?></p>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- Data Management Tools -->
		<div class="gbp-tool-section">
			<h2><?php esc_html_e( 'Data Management', 'gutenberg-blocks-presets' ); ?></h2>
			
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'Export Block Presets', 'gutenberg-blocks-presets' ); ?></h3>
				<p><?php esc_html_e( 'Export all block presets and their settings to a JSON file for backup or migration purposes.', 'gutenberg-blocks-presets' ); ?></p>
				<form method="post" action="">
					<?php wp_nonce_field( 'gbp_tools_action', 'gbp_nonce' ); ?>
					<input type="hidden" name="gbp_action" value="export_presets">
					<button type="submit" class="button">
						<?php esc_html_e( 'Export Block Presets', 'gutenberg-blocks-presets' ); ?>
					</button>
				</form>
			</div>
			
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'Reset Usage Statistics', 'gutenberg-blocks-presets' ); ?></h3>
				<p><?php esc_html_e( 'Clear all usage statistics data. This will reset the usage counters for all block presets.', 'gutenberg-blocks-presets' ); ?></p>
				<form method="post" action="">
					<?php wp_nonce_field( 'gbp_tools_action', 'gbp_nonce' ); ?>
					<input type="hidden" name="gbp_action" value="reset_usage_stats">
					<button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset all usage statistics? This action cannot be undone.', 'gutenberg-blocks-presets' ); ?>')">
						<?php esc_html_e( 'Reset Usage Statistics', 'gutenberg-blocks-presets' ); ?>
					</button>
				</form>
			</div>
		</div>
		
		<!-- System Information -->
		<div class="gbp-tool-section">
			<h2><?php esc_html_e( 'System Information', 'gutenberg-blocks-presets' ); ?></h2>
			
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'Plugin Status', 'gutenberg-blocks-presets' ); ?></h3>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Plugin Version:', 'gutenberg-blocks-presets' ); ?></strong></td>
							<td><?php echo esc_html( GBP_VERSION ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WordPress Version:', 'gutenberg-blocks-presets' ); ?></strong></td>
							<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'ACF Plugin:', 'gutenberg-blocks-presets' ); ?></strong></td>
							<td><?php echo wp_kses( function_exists( 'acf_register_block' ) ? '<span class="gbp-success">✓ Active</span>' : '<span class="gbp-error">✗ Not found</span>', array( 'span' => array( 'class' => array() ) ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Block Presets Count:', 'gutenberg-blocks-presets' ); ?></strong></td>
							<td><?php echo esc_html( wp_count_posts( 'gbp_block_preset' )->publish ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Old Blocks Count:', 'gutenberg-blocks-presets' ); ?></strong></td>
							<td><?php echo esc_html( wp_count_posts( 'block' ) ? wp_count_posts( 'block' )->publish : 0 ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		
		<!-- Theme Integration -->
		<div class="gbp-tool-section">
			<h2><?php esc_html_e( 'Theme Integration', 'gutenberg-blocks-presets' ); ?></h2>
			
			<div class="gbp-tool-card">
				<h3><?php esc_html_e( 'ACF Block Folders Status', 'gutenberg-blocks-presets' ); ?></h3>
				<?php
				$settings      = get_option( 'gbp_settings', array() );
				$block_folders = isset( $settings['block_folders'] ) ? $settings['block_folders'] : array();

				if ( empty( $block_folders ) ) {
					echo '<p class="gbp-warning">' . esc_html( __( 'No block folders configured.', 'gutenberg-blocks-presets' ) ) . '</p>';
				} else {
					echo '<ul>';
					foreach ( $block_folders as $folder ) {
						$full_path     = get_theme_file_path( '/' . ltrim( $folder, '/' ) . '/' );
						$exists        = file_exists( $full_path ) && is_dir( $full_path );
						$folder_status = $exists ? '<span class="gbp-success">✓</span>' : '<span class="gbp-error">✗</span>';
						echo '<li>' . wp_kses( $folder_status, array( 'span' => array( 'class' => array() ) ) ) . ' ' . esc_html( $folder ) . '</li>';
					}
					echo '</ul>';
				}
				?>
			</div>
		</div>
		
	</div>
</div>

