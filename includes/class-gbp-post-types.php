<?php
/**
 * Handle Custom Post Types for Gutenberg Blocks Presets
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Types Class
 */
class GBP_Post_Types {

	/**
	 * Instance of this class
	 *
	 * @var GBP_Post_Types
	 */
	private static $instance = null;

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 10 );
		add_action( 'admin_menu', array( $this, 'adjust_menu_labels' ), 999 );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types() {
		$settings = get_option( 'gbp_settings', array() );

		// Always register the new post type
		$this->register_block_preset_post_type();

		// Register legacy post type for backward compatibility if enabled
		$enable_legacy = isset( $settings['enable_legacy_post_type'] ) ? $settings['enable_legacy_post_type'] : true;
		if ( $enable_legacy ) {
			$this->register_legacy_block_post_type();
		}

		GBP_Helper_Functions::log( 'Post types registered. gbp_block_preset exists: ' . ( post_type_exists( 'gbp_block_preset' ) ? 'YES' : 'NO' ) );
	}

	/**
	 * Register Block Preset custom post type (Native WordPress)
	 */
	public function register_block_preset_post_type() {
		$labels = array(
			'name'               => __( 'Block Presets', 'gutenberg-blocks-presets' ),
			'singular_name'      => __( 'Block Preset', 'gutenberg-blocks-presets' ),
			'menu_name'          => __( 'Block Presets', 'gutenberg-blocks-presets' ),
			'name_admin_bar'     => __( 'Block Preset', 'gutenberg-blocks-presets' ),
			'add_new'            => __( 'Add New', 'gutenberg-blocks-presets' ),
			'add_new_item'       => __( 'Add New Block Preset', 'gutenberg-blocks-presets' ),
			'new_item'           => __( 'New Block Preset', 'gutenberg-blocks-presets' ),
			'edit_item'          => __( 'Edit Block Preset', 'gutenberg-blocks-presets' ),
			'view_item'          => __( 'View Block Preset', 'gutenberg-blocks-presets' ),
			'view_items'         => __( 'View Block Presets', 'gutenberg-blocks-presets' ),
			'all_items'          => __( 'All Block Presets', 'gutenberg-blocks-presets' ),
			'search_items'       => __( 'Search Block Presets', 'gutenberg-blocks-presets' ),
			'not_found'          => __( 'No block presets found.', 'gutenberg-blocks-presets' ),
			'not_found_in_trash' => __( 'No block presets found in Trash.', 'gutenberg-blocks-presets' ),
		);

		$args = array(
			'labels'       => $labels,
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-block-default',
			'supports'     => array( 'title', 'editor' ),
		);

		// Register the canonical post type slug used throughout the plugin
		$post_type_name = 'gbp_block_preset';
		$result         = register_post_type( $post_type_name, $args );

		if ( is_wp_error( $result ) ) {
			GBP_Helper_Functions::log( 'Post type registration failed: ' . $result->get_error_message() );
		}

		// Register taxonomies after post type
		$this->register_block_preset_taxonomies();
	}

	/**
	 * Register taxonomies for block presets
	 */
	public function register_block_preset_taxonomies() {
		// Block Preset Categories
		$category_labels = array(
			'name'                       => _x( 'Block Categories', 'Taxonomy General Name', 'gutenberg-blocks-presets' ),
			'singular_name'              => _x( 'Block Category', 'Taxonomy Singular Name', 'gutenberg-blocks-presets' ),
			'menu_name'                  => __( 'Categories', 'gutenberg-blocks-presets' ),
			'all_items'                  => __( 'All Categories', 'gutenberg-blocks-presets' ),
			'parent_item'                => __( 'Parent Category', 'gutenberg-blocks-presets' ),
			'parent_item_colon'          => __( 'Parent Category:', 'gutenberg-blocks-presets' ),
			'new_item_name'              => __( 'New Category Name', 'gutenberg-blocks-presets' ),
			'add_new_item'               => __( 'Add New Category', 'gutenberg-blocks-presets' ),
			'edit_item'                  => __( 'Edit Category', 'gutenberg-blocks-presets' ),
			'update_item'                => __( 'Update Category', 'gutenberg-blocks-presets' ),
			'view_item'                  => __( 'View Category', 'gutenberg-blocks-presets' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'gutenberg-blocks-presets' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'gutenberg-blocks-presets' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'gutenberg-blocks-presets' ),
			'popular_items'              => __( 'Popular Categories', 'gutenberg-blocks-presets' ),
			'search_items'               => __( 'Search Categories', 'gutenberg-blocks-presets' ),
			'not_found'                  => __( 'Not Found', 'gutenberg-blocks-presets' ),
			'no_terms'                   => __( 'No categories', 'gutenberg-blocks-presets' ),
			'items_list'                 => __( 'Categories list', 'gutenberg-blocks-presets' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'gutenberg-blocks-presets' ),
		);

		$category_args = array(
			'labels'            => $category_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'gbp_block_category', array( 'gbp_block_preset' ), $category_args );

		// Block Preset Tags
		$tag_labels = array(
			'name'                       => _x( 'Block Tags', 'Taxonomy General Name', 'gutenberg-blocks-presets' ),
			'singular_name'              => _x( 'Block Tag', 'Taxonomy Singular Name', 'gutenberg-blocks-presets' ),
			'menu_name'                  => __( 'Tags', 'gutenberg-blocks-presets' ),
			'all_items'                  => __( 'All Tags', 'gutenberg-blocks-presets' ),
			'new_item_name'              => __( 'New Tag Name', 'gutenberg-blocks-presets' ),
			'add_new_item'               => __( 'Add New Tag', 'gutenberg-blocks-presets' ),
			'edit_item'                  => __( 'Edit Tag', 'gutenberg-blocks-presets' ),
			'update_item'                => __( 'Update Tag', 'gutenberg-blocks-presets' ),
			'view_item'                  => __( 'View Tag', 'gutenberg-blocks-presets' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'gutenberg-blocks-presets' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'gutenberg-blocks-presets' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'gutenberg-blocks-presets' ),
			'popular_items'              => __( 'Popular Tags', 'gutenberg-blocks-presets' ),
			'search_items'               => __( 'Search Tags', 'gutenberg-blocks-presets' ),
			'not_found'                  => __( 'Not Found', 'gutenberg-blocks-presets' ),
			'no_terms'                   => __( 'No tags', 'gutenberg-blocks-presets' ),
			'items_list'                 => __( 'Tags list', 'gutenberg-blocks-presets' ),
			'items_list_navigation'      => __( 'Tags list navigation', 'gutenberg-blocks-presets' ),
		);

		$tag_args = array(
			'labels'            => $tag_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
		);

		register_taxonomy( 'gbp_block_tag', array( 'gbp_block_preset' ), $tag_args );
	}

	/**
	 * Register legacy 'block' post type for backward compatibility
	 * This matches the original theme implementation
	 */
	public function register_legacy_block_post_type() {
		$labels = array(
			'name'               => __( 'Block Presets (Legacy)', 'gutenberg-blocks-presets' ),
			'singular_name'      => __( 'Block Preset', 'gutenberg-blocks-presets' ),
			'menu_name'          => __( 'Legacy Blocks', 'gutenberg-blocks-presets' ),
			'all_items'          => __( 'All Legacy Blocks', 'gutenberg-blocks-presets' ),
			'edit_item'          => __( 'Edit Block Preset', 'gutenberg-blocks-presets' ),
			'view_item'          => __( 'View Block Preset', 'gutenberg-blocks-presets' ),
			'add_new_item'       => __( 'Add New Block', 'gutenberg-blocks-presets' ),
			'add_new'            => __( 'Add New', 'gutenberg-blocks-presets' ),
			'new_item'           => __( 'New Block Preset', 'gutenberg-blocks-presets' ),
			'search_items'       => __( 'Search Block Presets', 'gutenberg-blocks-presets' ),
			'not_found'          => __( 'No block presets found.', 'gutenberg-blocks-presets' ),
			'not_found_in_trash' => __( 'No block presets found in Trash.', 'gutenberg-blocks-presets' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Legacy block presets (for backward compatibility only).', 'gutenberg-blocks-presets' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // Hide from main menu since we have the new post type.
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title',
				'editor',
				'thumbnail',
				'custom-fields',
			),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'delete_with_user'    => false,
			'can_export'          => true,
		);

		register_post_type( 'block', $args );
	}

	/**
	 * Adjust menu labels and ensure proper menu visibility
	 */
	public function adjust_menu_labels() {
		// Relabeling the admin menu after registration requires modifying WordPress's own
		// $menu/$submenu globals directly; there is no public API for renaming menu entries.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		global $menu, $submenu;

		// Make sure the Block Presets menu is visible and properly labeled
		if ( post_type_exists( 'gbp_block_preset' ) ) {
			// Find and adjust the menu item
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[2] ) && 'edit.php?post_type=gbp_block_preset' === $item[2] ) {
					$menu[ $key ][0] = __( 'Block Presets', 'gutenberg-blocks-presets' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					break;
				}
			}

			// Ensure submenu items are properly labeled
			if ( isset( $submenu['edit.php?post_type=gbp_block_preset'] ) ) {
				foreach ( $submenu['edit.php?post_type=gbp_block_preset'] as $key => $item ) {
					if ( 'edit.php?post_type=gbp_block_preset' === $item[2] ) {
						$submenu['edit.php?post_type=gbp_block_preset'][ $key ][0] = __( 'All Block Presets', 'gutenberg-blocks-presets' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					} elseif ( 'post-new.php?post_type=gbp_block_preset' === $item[2] ) {
						$submenu['edit.php?post_type=gbp_block_preset'][ $key ][0] = __( 'Add New Preset', 'gutenberg-blocks-presets' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}
				}
			}
		}
	}
}
