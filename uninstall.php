<?php
/**
 * Uninstall script for Gutenberg Blocks Presets
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It cleans up all plugin data including posts, meta, options, and database tables.
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data
 */
function gbp_uninstall_cleanup() {
	global $wpdb;

	// Delete all block preset posts
	$posts = get_posts(
		array(
			'post_type'   => 'gbp_block_preset',
			'numberposts' => -1,
			'post_status' => 'any',
		)
	);

	foreach ( $posts as $post ) {
		// Force-deleting a post also removes its post meta, so no separate meta cleanup is needed.
		wp_delete_post( $post->ID, true );
	}

	// Delete custom taxonomy terms.
	$taxonomies = array( 'gbp_block_category', 'gbp_block_tag' );
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}
	}

	// Delete plugin options
	delete_option( 'gbp_settings' );
	delete_option( 'gbp_version' );
	delete_option( 'gbp_activation_date' );

	// Delete transients
	delete_transient( 'gbp_block_cache' );
	delete_transient( 'gbp_usage_stats' );

	// Delete the custom database table.
	// Table name is built from $wpdb->prefix (not user input), so it is safe to interpolate;
	// $wpdb->prepare() does not support table/column identifiers as placeholders.
	$table_name = $wpdb->prefix . 'gbp_block_usage';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	// Clean up any remaining meta keys
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like( '_gbp_' ) . '%' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like( 'gbp_' ) . '%' ) );

	// Clean up user meta (if any)
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", $wpdb->esc_like( '_gbp_' ) . '%' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", $wpdb->esc_like( 'gbp_' ) . '%' ) );

	// Flush rewrite rules
	flush_rewrite_rules();

	// Clear any cached data
	wp_cache_flush();
}

// Execute cleanup
gbp_uninstall_cleanup();

/**
 * Log uninstall event (optional - for debugging)
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Gutenberg Blocks Presets plugin has been uninstalled and all data removed.' );
}
