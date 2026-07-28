<?php
/**
 * Admin Settings Page Template
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php settings_errors(); ?>
	
	<div class="gbp-admin-container">
		<div class="gbp-admin-main">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'gbp_settings_group' );
				do_settings_sections( 'gutenberg-blocks-presets' );
				submit_button();
				?>
			</form>
		</div>
		
		<div class="gbp-admin-sidebar">
			<div class="gbp-widget">
				<h3><?php esc_html_e( 'Plugin Information', 'gutenberg-blocks-presets' ); ?></h3>
				<ul>
					<li><strong><?php esc_html_e( 'Version:', 'gutenberg-blocks-presets' ); ?></strong> <?php echo esc_html( GBP_VERSION ); ?></li>
					<li><strong><?php esc_html_e( 'Post Type:', 'gutenberg-blocks-presets' ); ?></strong> gbp_block_preset</li>
					<li><strong><?php esc_html_e( 'Shortcode:', 'gutenberg-blocks-presets' ); ?></strong> [gbp_block id="123"]</li>
				</ul>
			</div>
			
			<div class="gbp-widget">
				<h3><?php esc_html_e( 'Quick Actions', 'gutenberg-blocks-presets' ); ?></h3>
				<p>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gbp_block_preset' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Create New Block Preset', 'gutenberg-blocks-presets' ); ?>
					</a>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gbp_block_preset' ) ); ?>" class="button">
						<?php esc_html_e( 'Manage Block Presets', 'gutenberg-blocks-presets' ); ?>
					</a>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gbp_block_preset&page=gbp-statistics' ) ); ?>" class="button">
						<?php esc_html_e( 'View Statistics', 'gutenberg-blocks-presets' ); ?>
					</a>
				</p>
			</div>
			
			<div class="gbp-widget">
				<h3><?php esc_html_e( 'Documentation', 'gutenberg-blocks-presets' ); ?></h3>
				<p><?php esc_html_e( 'Use these functions in your theme:', 'gutenberg-blocks-presets' ); ?></p>
				<code>gbp_render_block_preset(123);</code><br>
				<code>do_cpt_block(123); // Legacy</code><br><br>
				
				<p><?php esc_html_e( 'Use shortcodes in content:', 'gutenberg-blocks-presets' ); ?></p>
				<code>[gbp_block id="123"]</code><br>
				<code>[block_preset id="123"]</code>
			</div>
		</div>
	</div>
</div>
