<?php
/**
 * Gutenberg Blocks for Gutenberg Blocks Presets
 *
 * @package Gutenberg_Blocks_Presets
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native Gutenberg Block Class
 */
class GBP_Gutenberg_Blocks {

	/**
	 * Instance of this class
	 *
	 * @var GBP_Gutenberg_Blocks
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
		add_action( 'init', array( $this, 'register_assets_and_block' ) );
	}

	/**
	 * Register the editor script and the native "gbp/block-preset" block type
	 */
	public function register_assets_and_block() {
		// Register editor script
		$handle = 'gbp-block-preset-editor';
		$src    = GBP_PLUGIN_URL . 'assets/js/block-preset.js';
		$deps   = array(
			'wp-blocks',
			'wp-element',
			'wp-i18n',
			'wp-components',
			'wp-block-editor',
			'wp-data',
			'wp-server-side-render',
			'wp-api-fetch',
		);

		wp_register_script( $handle, $src, $deps, GBP_VERSION, true );

		// Register block type (dynamic)
		register_block_type(
			'gbp/block-preset',
			array(
				'editor_script'   => $handle,
				'render_callback' => array( $this, 'render_block_preset' ),
				'attributes'      => array(
					'presetId'    => array(
						'type' => 'integer',
					),
					'presetTitle' => array(
						'type'    => 'string',
						'default' => '',
					),
					'showTitle'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'className'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'align'       => array(
						'type' => 'string',
					),
					// Editor-only filtering helpers
					'categoryId'  => array(
						'type' => 'integer',
					),
					'tagId'       => array(
						'type' => 'integer',
					),
					'search'      => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'supports'        => array(
					'align'           => array( 'wide', 'full' ),
					'customClassName' => true,
					'anchor'          => true,
				),
			)
		);
	}

	/**
	 * Render callback for the "gbp/block-preset" dynamic block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Block inner content (unused; the block has no inner blocks).
	 * @return string Rendered block HTML.
	 */
	public function render_block_preset( $attributes, $content ) {
		$preset_id  = isset( $attributes['presetId'] ) ? intval( $attributes['presetId'] ) : 0;
		$show_title = ! empty( $attributes['showTitle'] );
		$class_name = isset( $attributes['className'] ) ? sanitize_html_class( $attributes['className'] ) : '';
		$align      = isset( $attributes['align'] ) ? sanitize_html_class( $attributes['align'] ) : '';

		if ( ! $preset_id ) {
			return $this->render_notice( __( 'No Block Preset selected.', 'gutenberg-blocks-presets' ) );
		}

		$post        = get_post( $preset_id );
		$can_preview = is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_preview();
		if ( ! $post || 'gbp_block_preset' !== $post->post_type || ( 'publish' !== $post->post_status && ! $can_preview ) ) {
			return $this->render_notice( __( 'This block references a Block Preset that is missing or unavailable. Edits to a Block Preset affect all posts and pages using it.', 'gutenberg-blocks-presets' ) );
		}

		// Build wrapper classes
		$classes = array( 'wp-block-gbp-block-preset' );
		if ( ! empty( $class_name ) ) {
			$classes[] = $class_name;
		}
		if ( ! empty( $align ) ) {
			$classes[] = 'align' . $align;
		}

		// Render content through the core 'the_content' filter (not a plugin-defined hook)
		// to process blocks and shortcodes the same way normal post content is rendered.
		$rendered_content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-gbp-preset-id="' . esc_attr( $preset_id ) . '">';
		if ( $show_title ) {
			$html .= '<div class="gbp-preset-title">' . esc_html( get_the_title( $post ) ) . '</div>';
		}
		$html .= '<div class="gbp-preset-content">' . $rendered_content . '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a lightweight, front-end-safe notice
	 *
	 * @param string $message Notice text.
	 * @return string Notice HTML.
	 */
	private function render_notice( $message ) {
		return '<div class="gbp-preset-notice" role="note">' . esc_html( $message ) . '</div>';
	}
}
