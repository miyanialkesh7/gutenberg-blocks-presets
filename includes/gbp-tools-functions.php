<?php
/**
 * Helper functions for the Tools admin screen (migration, usage reset, export)
 *
 * Loaded unconditionally alongside the plugin's other dependencies (not only when the
 * Tools page itself renders) so gbp_export_presets() is available to GBP_Admin's
 * admin_init handler, which must run - and may need to send file-download headers -
 * before the Tools page template ever outputs any HTML.
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrate legacy "block" post type entries to the gbp_block_preset post type
 *
 * @return int Number of block presets migrated.
 */
function gbp_migrate_old_blocks() {
	$old_blocks = get_posts(
		array(
			'post_type'      => 'block',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	$migrated = 0;

	foreach ( $old_blocks as $old_block ) {
		$new_post_data = array(
			'post_title'   => $old_block->post_title,
			'post_content' => $old_block->post_content,
			'post_excerpt' => $old_block->post_excerpt,
			'post_status'  => $old_block->post_status,
			'post_type'    => 'gbp_block_preset',
			'post_author'  => $old_block->post_author,
			'post_date'    => $old_block->post_date,
		);

		$new_post_id = wp_insert_post( $new_post_data );

		if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
			// Copy meta data.
			$meta_data = get_post_meta( $old_block->ID );
			foreach ( $meta_data as $meta_key => $meta_values ) {
				foreach ( $meta_values as $meta_value ) {
					add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
				}
			}

			// Copy taxonomies.
			$taxonomies = get_object_taxonomies( 'block' );
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $old_block->ID, $taxonomy );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$term_ids = wp_list_pluck( $terms, 'term_id' );
					wp_set_post_terms( $new_post_id, $term_ids, $taxonomy );
				}
			}

			// Set migration flag.
			update_post_meta( $new_post_id, '_gbp_migrated_from', $old_block->ID );
			update_post_meta( $new_post_id, '_gbp_block_type', 'migrated' );

			++$migrated;
		}
	}

	return $migrated;
}

/**
 * Truncate the block usage statistics table
 *
 * @return bool True on success, false on failure.
 */
function gbp_reset_usage_stats() {
	global $wpdb;
	// Table name is built from $wpdb->prefix (not user input), so it is safe to interpolate;
	// $wpdb->prepare() does not support table/column identifiers as placeholders.
	$table_name = $wpdb->prefix . 'gbp_block_usage';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	return false !== $wpdb->query( "TRUNCATE TABLE $table_name" );
}

/**
 * Build an exportable array of all block presets and their settings
 *
 * @return array|false Export data, or false if there are no presets to export.
 */
function gbp_export_presets() {
	$presets = get_posts(
		array(
			'post_type'      => 'gbp_block_preset',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	if ( empty( $presets ) ) {
		return false;
	}

	$export_data = array(
		'version'     => GBP_VERSION,
		'export_date' => current_time( 'mysql' ),
		'presets'     => array(),
	);

	foreach ( $presets as $preset ) {
		$preset_data = array(
			'title'      => $preset->post_title,
			'content'    => $preset->post_content,
			'excerpt'    => $preset->post_excerpt,
			'status'     => $preset->post_status,
			'meta'       => get_post_meta( $preset->ID ),
			'categories' => wp_get_post_terms( $preset->ID, 'gbp_block_category' ),
			'tags'       => wp_get_post_terms( $preset->ID, 'gbp_block_tag' ),
		);

		$export_data['presets'][] = $preset_data;
	}

	return $export_data;
}
