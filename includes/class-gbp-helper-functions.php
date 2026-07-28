<?php
/**
 * Helper Functions for Gutenberg Blocks Presets
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper Functions Class
 */
class GBP_Helper_Functions {

	/**
	 * Instance of this class
	 *
	 * @var GBP_Helper_Functions
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
		// Make global functions available
		$this->define_global_functions();

		// Register shortcodes directly rather than via add_action( 'init', ... ): this class is
		// only ever instantiated from Gutenberg_Blocks_Presets::init(), which is itself already
		// running as an 'init' callback. Re-hooking to the currently-executing 'init' pass is
		// not reliably picked up, which previously left the shortcodes unregistered.
		$this->register_shortcodes();

		// Add REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );
	}

	/**
	 * Check if plugin debug logging is enabled
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {
		$settings = get_option( 'gbp_settings', array() );
		return ! empty( $settings['enable_debug_logging'] );
	}

	/**
	 * Write a debug message if Test Log Mode is enabled
	 *
	 * @param mixed $message Message to log; arrays/objects are dumped with print_r().
	 * @return void
	 */
	public static function log( $message ) {
		if ( ! self::is_debug_enabled() ) {
			return;
		}
		// Debug logging is opt-in via the 'enable_debug_logging' setting checked above.
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( 'GBP: ' . print_r( $message, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		} else {
			error_log( 'GBP: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Define global helper functions
	 */
	private function define_global_functions() {
		if ( ! function_exists( 'gbp_render_block_preset' ) ) {
			/**
			 * Render a block preset by ID
			 *
			 * @param int   $block_id Block preset ID.
			 * @param array $args Additional arguments
			 * @return void
			 */
			function gbp_render_block_preset( $block_id, $args = array() ) {
				// Fully qualified on purpose: this is a global function nested inside a method,
				// so "self::" would resolve to no class scope and fatal at call time.
				GBP_Helper_Functions::render_block_preset( $block_id, $args ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed
			}
		}

		if ( ! function_exists( 'do_cpt_block' ) ) {
			/**
			 * Legacy function to maintain backward compatibility
			 * Renders a block preset - wrapper for gbp_render_block_preset
			 *
			 * Deliberately unprefixed: this name is kept as-is for backward compatibility with sites
			 * migrating from the legacy block system that already call do_cpt_block().
			 *
			 * @param int $block_id Block preset ID
			 * @return void
			 */
			function do_cpt_block( $block_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
				gbp_render_block_preset( $block_id );
			}
		}

		if ( ! function_exists( 'gbp_get_block_presets' ) ) {
			/**
			 * Get block presets
			 *
			 * @param array $args Query arguments
			 * @return WP_Query Block presets query
			 */
			function gbp_get_block_presets( $args = array() ) {
				// Fully qualified on purpose: nested global function, "self::" is not valid here.
				return GBP_Helper_Functions::get_block_presets( $args ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed
			}
		}

		if ( ! function_exists( 'gbp_get_block_preset_content' ) ) {
			/**
			 * Get block preset content without rendering
			 *
			 * @param int $block_id Block preset ID
			 * @return string Block content
			 */
			function gbp_get_block_preset_content( $block_id ) {
				// Fully qualified on purpose: nested global function, "self::" is not valid here.
				return GBP_Helper_Functions::get_block_preset_content( $block_id ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed
			}
		}
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'gbp_block', array( $this, 'block_preset_shortcode' ) );
		add_shortcode( 'block_preset', array( $this, 'block_preset_shortcode' ) ); // Legacy support.
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_rest_endpoints() {
		register_rest_route(
			'gbp/v1',
			'/block-presets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_block_presets' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'gbp/v1',
			'/block-presets/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_block_preset' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'gbp/v1',
			'/block-presets/(?P<id>\d+)/render',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_render_block_preset' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}

	/**
	 * Render a block preset
	 *
	 * @param int   $block_id Block preset ID.
	 * @param array $args Additional arguments
	 */
	public static function render_block_preset( $block_id, $args = array() ) {
		if ( empty( $block_id ) ) {
			return;
		}

		// Try new post type first, then fallback to old 'block' post type
		$block = get_post( $block_id );

		if ( ! $block ) {
			return;
		}

		// Check if it's a valid block preset post type
		if ( ! in_array( $block->post_type, array( 'gbp_block_preset', 'block' ), true ) ) {
			return;
		}

		// Check if block is published
		if ( 'publish' !== $block->post_status ) {
			return;
		}

		// Track usage
		self::track_block_usage( $block_id );

		// Default arguments
		$defaults = array(
			'before'        => '',
			'after'         => '',
			'wrapper_class' => 'gbp-block-preset-wrapper',
			'apply_filters' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get content
		$content = $block->post_content;

		// Apply the_content filters if enabled
		if ( $args['apply_filters'] ) {
			// Re-running the core 'the_content' filter (not a plugin-defined hook) so shortcodes,
			// embeds, and wpautop are processed the same way as normal post content.
			$content = apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		// Add wrapper if specified
		if ( ! empty( $args['wrapper_class'] ) || ! empty( $args['before'] ) || ! empty( $args['after'] ) ) {
			$wrapper_class = esc_attr( $args['wrapper_class'] );
			$before        = $args['before'];
			$after         = $args['after'];

			if ( ! empty( $wrapper_class ) ) {
				$before = '<div class="' . $wrapper_class . '">' . $before;
				$after  = $after . '</div>';
			}

			$content = $before . $content . $after;
		}

		// Allow filtering of final output
		$content = apply_filters( 'gbp_block_preset_content', $content, $block_id, $block, $args );

		echo wp_kses_post( $content );
	}

	/**
	 * Get block presets
	 *
	 * @param array $args Query arguments
	 * @return WP_Query
	 */
	public static function get_block_presets( $args = array() ) {
		$defaults = array(
			'post_type'      => array( 'gbp_block_preset', 'block' ), // Support both new and old post types.
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		return new WP_Query( $args );
	}

	/**
	 * Get block preset content without rendering
	 *
	 * @param int $block_id Block preset ID
	 * @return string
	 */
	public static function get_block_preset_content( $block_id ) {
		if ( empty( $block_id ) ) {
			return '';
		}

		$block = get_post( $block_id );

		if ( ! $block || ! in_array( $block->post_type, array( 'gbp_block_preset', 'block' ), true ) ) {
			return '';
		}

		return $block->post_content;
	}

	/**
	 * Track block usage
	 *
	 * @param int $block_id Block preset ID
	 */
	private static function track_block_usage( $block_id ) {
		global $wpdb;

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		// Table name is built from $wpdb->prefix (not user input), so it is safe to interpolate;
		// $wpdb->prepare() does not support table/column identifiers as placeholders.
		$table_name = $wpdb->prefix . 'gbp_block_usage';

		// Check if record exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, usage_count FROM $table_name WHERE block_id = %d AND post_id = %d",
				$block_id,
				$post_id
			)
		);

		if ( $existing ) {
			// Update existing record
			$wpdb->update(
				$table_name,
				array(
					'usage_count' => $existing->usage_count + 1,
					'last_used'   => current_time( 'mysql' ),
				),
				array(
					'id' => $existing->id,
				),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new record
			$wpdb->insert(
				$table_name,
				array(
					'block_id'    => $block_id,
					'post_id'     => $post_id,
					'usage_count' => 1,
					'last_used'   => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%s' )
			);
		}
	}

	/**
	 * Block preset shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function block_preset_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => 0,
				'class'   => '',
				'wrapper' => 'div',
			),
			$atts,
			'gbp_block'
		);

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		ob_start();

		$args = array();
		if ( ! empty( $atts['class'] ) ) {
			$args['wrapper_class'] = 'gbp-block-preset-wrapper ' . esc_attr( $atts['class'] );
		}

		self::render_block_preset( $atts['id'], $args );

		return ob_get_clean();
	}

	/**
	 * REST API permission check
	 *
	 * @return bool
	 */
	public function rest_permission_check() {
		return true; // Public read access is intentional; endpoints only ever expose published presets.
	}

	/**
	 * REST API: Get block presets
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_block_presets( $request ) {
		$args = array();

		if ( $request->get_param( 'category' ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'gbp_block_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $request->get_param( 'category' ) ),
				),
			);
		}

		if ( $request->get_param( 'tag' ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'gbp_block_tag',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $request->get_param( 'tag' ) ),
				),
			);
		}

		$query   = self::get_block_presets( $args );
		$presets = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$presets[] = array(
				'id'             => get_the_ID(),
				'title'          => get_the_title(),
				'content'        => get_the_content(),
				'excerpt'        => get_the_excerpt(),
				'featured_image' => get_the_post_thumbnail_url( get_the_ID(), 'medium' ),
				'categories'     => wp_get_post_terms( get_the_ID(), 'gbp_block_category' ),
				'tags'           => wp_get_post_terms( get_the_ID(), 'gbp_block_tag' ),
			);
		}

		wp_reset_postdata();

		return new WP_REST_Response( $presets, 200 );
	}

	/**
	 * REST API: Get single block preset
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_block_preset( $request ) {
		$id    = (int) $request['id'];
		$block = get_post( $id );

		// Also verify publish status: this is a public, unauthenticated endpoint, so
		// draft/private/trashed presets must never be returned.
		if ( ! $block || ! in_array( $block->post_type, array( 'gbp_block_preset', 'block' ), true ) || 'publish' !== $block->post_status ) {
			return new WP_REST_Response( array( 'error' => __( 'Block preset not found.', 'gutenberg-blocks-presets' ) ), 404 );
		}

		$preset = array(
			'id'             => $block->ID,
			'title'          => $block->post_title,
			// Re-running the core 'the_content' filter (not a plugin-defined hook), same as render_block_preset().
			'content'        => apply_filters( 'the_content', $block->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'raw_content'    => $block->post_content,
			'excerpt'        => $block->post_excerpt,
			'featured_image' => get_the_post_thumbnail_url( $block->ID, 'large' ),
			'categories'     => wp_get_post_terms( $block->ID, 'gbp_block_category' ),
			'tags'           => wp_get_post_terms( $block->ID, 'gbp_block_tag' ),
		);

		return new WP_REST_Response( $preset, 200 );
	}

	/**
	 * REST API: Render block preset
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function rest_render_block_preset( $request ) {
		$id = (int) $request['id'];

		ob_start();
		self::render_block_preset( $id );
		$rendered_content = ob_get_clean();

		if ( empty( $rendered_content ) ) {
			return new WP_REST_Response( array( 'error' => __( 'Block preset not found or empty.', 'gutenberg-blocks-presets' ) ), 404 );
		}

		return new WP_REST_Response(
			array(
				'id'               => $id,
				'rendered_content' => $rendered_content,
			),
			200
		);
	}
}
