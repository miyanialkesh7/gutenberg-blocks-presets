<?php
/**
 * Handle ACF Blocks Registration and Management
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF Blocks Class
 */
class GBP_ACF_Blocks {

	/**
	 * Instance of this class
	 *
	 * @var GBP_ACF_Blocks
	 */
	private static $instance = null;

	/**
	 * Block folders to scan for ACF blocks
	 *
	 * @var array
	 */
	private $block_folders = array();

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
		// Only initialize ACF features if enabled in settings
		$settings    = get_option( 'gbp_settings', array() );
		$acf_enabled = isset( $settings['enable_acf_blocks'] ) ? $settings['enable_acf_blocks'] : false;

		if ( $acf_enabled ) {
			add_action( 'acf/init', array( $this, 'acf_init' ) );
			$this->set_block_folders();
		} else {
			// Add admin notice that ACF features are disabled
			add_action( 'admin_notices', array( $this, 'acf_disabled_notice' ) );
		}
	}

	/**
	 * Set block folders from settings
	 */
	private function set_block_folders() {
		$settings = get_option( 'gbp_settings', array() );

		$default_folders = array(
			'general/acf-blocks',
			'public/acf-blocks',
			'login-register/acf-blocks',
			'members/acf-blocks',
		);

		$this->block_folders = isset( $settings['block_folders'] ) ? $settings['block_folders'] : $default_folders;

		// Ensure folders have leading slash
		$this->block_folders = array_map(
			function ( $folder ) {
				return '/' . ltrim( $folder, '/' ) . '/';
			},
			$this->block_folders
		);
	}

	/**
	 * Initialize ACF blocks
	 */
	public function acf_init() {
		// Check if ACF is active and ACF blocks are enabled in settings
		$settings    = get_option( 'gbp_settings', array() );
		$acf_enabled = isset( $settings['enable_acf_blocks'] ) ? $settings['enable_acf_blocks'] : true;

		if ( ! function_exists( 'acf_register_block' ) || ! $acf_enabled ) {
			// Add admin notice if ACF is not available but ACF blocks are enabled
			if ( $acf_enabled && ! function_exists( 'acf_register_block' ) ) {
				add_action( 'admin_notices', array( $this, 'acf_missing_notice' ) );
			}
			return;
		}

		// Register custom block category
		$this->register_block_category();

		// Register ACF blocks from theme folders
		$this->register_acf_blocks();
	}

	/**
	 * Register custom block category
	 */
	private function register_block_category() {
		add_filter(
			'block_categories_all',
			function ( $categories, $block_editor_context ) {
				return array_merge(
					$categories,
					array(
						array(
							'slug'  => 'gbp-custom-blocks',
							'title' => __( 'Custom ACF Blocks', 'gutenberg-blocks-presets' ),
							'icon'  => 'block-default',
						),
					)
				);
			},
			99,
			2
		);
	}

	/**
	 * Register ACF blocks from theme folders
	 */
	private function register_acf_blocks() {
		foreach ( $this->block_folders as $folder ) {
			$this->scan_and_register_blocks( $folder );
		}
	}

	/**
	 * Scan folder and register blocks
	 *
	 * @param string $folder Folder path relative to theme
	 */
	private function scan_and_register_blocks( $folder ) {
		$full_path = get_theme_file_path( $folder );

		if ( ! file_exists( $full_path ) || ! is_dir( $full_path ) ) {
			return;
		}

		$files = scandir( $full_path );
		if ( ! $files ) {
			return;
		}

		$php_files = array_filter(
			$files,
			function ( $file ) {
				return 'php' === pathinfo( $file, PATHINFO_EXTENSION ) && '.' !== $file && '..' !== $file;
			}
		);

		foreach ( $php_files as $file ) {
			$this->register_single_block( $folder, $file );
		}
	}

	/**
	 * Register a single ACF block
	 *
	 * @param string $folder Folder path
	 * @param string $file File name
	 */
	private function register_single_block( $folder, $file ) {
		$name = str_replace( '.php', '', $file );
		$path = get_theme_file_path( $folder . $file );

		// Get block data from file headers
		$block_data = get_file_data(
			$path,
			array(
				'title'       => 'Title',
				'description' => 'Description',
				'keywords'    => 'Keywords',
				'category'    => 'Category',
				'icon'        => 'Icon',
				'color'       => 'color',
				'supports'    => 'Supports',
				'mode'        => 'Mode',
				'align'       => 'Align',
			)
		);

		// Set defaults
		$block_data['title']       = $block_data['title'] ? $block_data['title'] : ucwords( str_replace( '-', ' ', $name ) );
		$block_data['description'] = $block_data['description'] ? $block_data['description'] : $block_data['title'];
		$block_data['category']    = $block_data['category'] ? $block_data['category'] : 'gbp-custom-blocks';
		$block_data['icon']        = $block_data['icon'] ? $block_data['icon'] : 'block-default';
		$block_data['mode']        = $block_data['mode'] ? $block_data['mode'] : 'auto';

		// Parse keywords
		if ( $block_data['keywords'] ) {
			$block_data['keywords'] = array_map( 'trim', explode( ',', $block_data['keywords'] ) );
		} else {
			$block_data['keywords'] = array( $block_data['title'], $block_data['description'] );
		}

		// Parse supports
		$supports = array(
			'align'  => $this->parse_bool_setting( $block_data['align'], false ),
			'mode'   => true,
			'anchor' => true,
			'color'  => $this->parse_bool_setting( $block_data['color'], false ),
		);

		if ( $block_data['supports'] ) {
			$custom_supports = array_map( 'trim', explode( ',', $block_data['supports'] ) );
			foreach ( $custom_supports as $support ) {
				$supports[ $support ] = true;
			}
		}

		// Register the block
		$args = array(
			'name'            => $name,
			'title'           => $block_data['title'],
			'description'     => $block_data['description'],
			'category'        => $block_data['category'],
			'icon'            => $block_data['icon'],
			'keywords'        => $block_data['keywords'],
			'mode'            => $block_data['mode'],
			'supports'        => $supports,
			'render_callback' => array( $this, 'render_block' ),
			'enqueue_assets'  => array( $this, 'enqueue_block_assets' ),
		);

		acf_register_block( $args );
	}

	/**
	 * Parse boolean setting from string
	 *
	 * @param string $value Setting value
	 * @param bool   $default_value Default value.
	 * @return bool
	 */
	private function parse_bool_setting( $value, $default_value = false ) {
		if ( empty( $value ) ) {
			return $default_value;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Render ACF block
	 *
	 * @param array  $block Block array.
	 * @param string $content Block content
	 * @param bool   $is_preview Is preview mode.
	 * @param int    $post_id Post ID.
	 */
	public function render_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$block_slug = str_replace( 'acf/', '', $block['name'] );

		// Find the template file
		$template_file = $this->find_block_template( $block_slug );

		if ( $template_file && file_exists( $template_file ) ) {
			// Set up block variables for template
			$block_id   = 'gbp-block-' . $block_slug . '-' . uniqid();
			$class_name = 'gbp-block gbp-block-' . $block_slug;

			// className/align come from the block editor (user-editable post content), so
			// sanitize them as HTML class tokens before templates echo $class_name directly.
			if ( ! empty( $block['className'] ) ) {
				$extra_classes = array_map( 'sanitize_html_class', explode( ' ', $block['className'] ) );
				$class_name   .= ' ' . implode( ' ', array_filter( $extra_classes ) );
			}

			if ( ! empty( $block['align'] ) ) {
				$class_name .= ' align' . sanitize_html_class( $block['align'] );
			}

			// Include the template
			include $template_file;
		} else {
			echo '<div class="gbp-block-error">';
			echo esc_html(
				sprintf(
					/* translators: %s: Block slug. */
					__( 'Block template not found: %s', 'gutenberg-blocks-presets' ),
					$block_slug
				)
			);
			echo '</div>';
		}
	}

	/**
	 * Find block template file
	 *
	 * @param string $block_slug Block slug
	 * @return string|false Template file path or false
	 */
	private function find_block_template( $block_slug ) {
		foreach ( $this->block_folders as $folder ) {
			$template_path = get_theme_file_path( $folder . $block_slug . '.php' );
			if ( file_exists( $template_path ) ) {
				return $template_path;
			}
		}
		return false;
	}

	/**
	 * Enqueue block assets
	 *
	 * @param array $block Block array
	 */
	public function enqueue_block_assets( $block ) {
		$block_slug    = str_replace( 'acf/', '', $block['name'] );
		$template_file = $this->find_block_template( $block_slug );

		if ( ! $template_file ) {
			return;
		}

		// Get asset data from template file
		$asset_data = get_file_data(
			$template_file,
			array(
				'enqueue_style'  => 'enqueue_style',
				'enqueue_script' => 'enqueue_script',
				'enqueue_libs'   => 'enqueue_libs',
			)
		);

		$handle = 'gbp-block-' . $block_slug;

		// Enqueue styles
		if ( $this->parse_bool_setting( $asset_data['enqueue_style'], true ) ) {
			$this->enqueue_block_styles( $block_slug, $handle );
		}

		// Enqueue scripts
		if ( $this->parse_bool_setting( $asset_data['enqueue_script'], false ) ) {
			$this->enqueue_block_scripts( $block_slug, $handle );
		}

		// Enqueue libraries
		if ( $asset_data['enqueue_libs'] ) {
			$libs = array_map( 'trim', explode( ',', $asset_data['enqueue_libs'] ) );
			foreach ( $libs as $lib ) {
				wp_enqueue_script( $lib );
			}
		}
	}

	/**
	 * Enqueue block styles
	 *
	 * @param string $block_slug Block slug
	 * @param string $handle Script handle
	 */
	private function enqueue_block_styles( $block_slug, $handle ) {
		foreach ( $this->block_folders as $folder ) {
			$css_path = get_theme_file_path( $folder . 'css/' . $block_slug . '.css' );
			$css_url  = get_theme_file_uri( $folder . 'css/' . $block_slug . '.css' );

			if ( file_exists( $css_path ) ) {
				wp_enqueue_style( $handle, $css_url, array(), filemtime( $css_path ) );
				break;
			}
		}
	}

	/**
	 * Enqueue block scripts
	 *
	 * @param string $block_slug Block slug
	 * @param string $handle Script handle
	 */
	private function enqueue_block_scripts( $block_slug, $handle ) {
		foreach ( $this->block_folders as $folder ) {
			$js_path = get_theme_file_path( $folder . 'js/' . $block_slug . '.js' );
			$js_url  = get_theme_file_uri( $folder . 'js/' . $block_slug . '.js' );

			if ( file_exists( $js_path ) ) {
				wp_enqueue_script( $handle, $js_url, array( 'jquery' ), filemtime( $js_path ), true );
				break;
			}
		}
	}

	/**
	 * Display admin notice when ACF is missing
	 */
	public function acf_missing_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Gutenberg Blocks Presets:', 'gutenberg-blocks-presets' ); ?></strong>
				<?php esc_html_e( 'Advanced Custom Fields Pro is required for automatic ACF blocks registration. You can still use Block Presets without ACF.', 'gutenberg-blocks-presets' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gbp_block_preset&page=gbp-settings' ) ); ?>"><?php esc_html_e( 'Configure settings', 'gutenberg-blocks-presets' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Display admin notice when ACF features are disabled
	 */
	public function acf_disabled_notice() {
		// Only show on plugin pages
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'gbp' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'ACF Features Disabled:', 'gutenberg-blocks-presets' ); ?></strong>
				<?php esc_html_e( 'Automatic ACF blocks registration is currently disabled. The plugin works with native WordPress functionality.', 'gutenberg-blocks-presets' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gbp_block_preset&page=gbp-settings' ) ); ?>"><?php esc_html_e( 'Enable ACF features', 'gutenberg-blocks-presets' ); ?></a>
			</p>
		</div>
		<?php
	}
}
