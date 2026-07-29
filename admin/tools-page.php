<?php
/**
 * Admin Tools Page Template
 *
 * @package Gutenberg_Blocks_Presets
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if (isset($_POST['gbp_action']) && isset($_POST['gbp_nonce']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['gbp_nonce'])), 'gbp_tools_action')) {
    switch ($_POST['gbp_action']) {
        case 'migrate_old_blocks':
            $migrated = gbp_migrate_old_blocks();
            if ($migrated !== false) {
                /* translators: %d: Number of migrated block presets */
                echo '<div class="notice notice-success"><p>' . esc_html(sprintf(__('Successfully migrated %d block presets from old format.', 'gutenberg-blocks-presets'), $migrated)) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html(__('Migration failed. Please check error logs.', 'gutenberg-blocks-presets')) . '</p></div>';
            }
            break;
            
        case 'reset_usage_stats':
            if (gbp_reset_usage_stats()) {
                echo '<div class="notice notice-success"><p>' . esc_html(__('Usage statistics have been reset.', 'gutenberg-blocks-presets')) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html(__('Failed to reset usage statistics.', 'gutenberg-blocks-presets')) . '</p></div>';
            }
            break;
            
        case 'export_presets':
            $export_data = gbp_export_presets();
            if ($export_data) {
                $filename = 'gbp-block-presets-' . gmdate('Y-m-d-H-i-s') . '.json';
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo json_encode($export_data, JSON_PRETTY_PRINT);
                exit;
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html(__('Export failed. No presets found.', 'gutenberg-blocks-presets')) . '</p></div>';
            }
            break;
    }
}

// Check for old block posts
$old_blocks = get_posts(array(
    'post_type' => 'block',
    'posts_per_page' => 1,
    'post_status' => 'any'
));

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="gbp-tools-container">
        
        <!-- Migration Tool -->
        <div class="gbp-tool-section">
            <h2><?php esc_html_e('Migration Tools', 'gutenberg-blocks-presets'); ?></h2>
            
            <?php if (!empty($old_blocks)): ?>
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('Migrate Old Block Presets', 'gutenberg-blocks-presets'); ?></h3>
                <p><?php esc_html_e('Found block presets using the old "block" post type. Click below to migrate them to the new format.', 'gutenberg-blocks-presets'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('gbp_tools_action', 'gbp_nonce'); ?>
                    <input type="hidden" name="gbp_action" value="migrate_old_blocks">
                    <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to migrate old block presets? This action cannot be undone.', 'gutenberg-blocks-presets'); ?>')">
                        <?php esc_html_e('Migrate Old Blocks', 'gutenberg-blocks-presets'); ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('Migration Status', 'gutenberg-blocks-presets'); ?></h3>
                <p class="gbp-success"><?php esc_html_e('✓ No old block presets found. Migration is complete or not needed.', 'gutenberg-blocks-presets'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Data Management Tools -->
        <div class="gbp-tool-section">
            <h2><?php esc_html_e('Data Management', 'gutenberg-blocks-presets'); ?></h2>
            
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('Export Block Presets', 'gutenberg-blocks-presets'); ?></h3>
                <p><?php esc_html_e('Export all block presets and their settings to a JSON file for backup or migration purposes.', 'gutenberg-blocks-presets'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('gbp_tools_action', 'gbp_nonce'); ?>
                    <input type="hidden" name="gbp_action" value="export_presets">
                    <button type="submit" class="button">
                        <?php esc_html_e('Export Block Presets', 'gutenberg-blocks-presets'); ?>
                    </button>
                </form>
            </div>
            
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('Reset Usage Statistics', 'gutenberg-blocks-presets'); ?></h3>
                <p><?php esc_html_e('Clear all usage statistics data. This will reset the usage counters for all block presets.', 'gutenberg-blocks-presets'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('gbp_tools_action', 'gbp_nonce'); ?>
                    <input type="hidden" name="gbp_action" value="reset_usage_stats">
                    <button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all usage statistics? This action cannot be undone.', 'gutenberg-blocks-presets'); ?>')">
                        <?php esc_html_e('Reset Usage Statistics', 'gutenberg-blocks-presets'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="gbp-tool-section">
            <h2><?php esc_html_e('System Information', 'gutenberg-blocks-presets'); ?></h2>
            
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('Plugin Status', 'gutenberg-blocks-presets'); ?></h3>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('Plugin Version:', 'gutenberg-blocks-presets'); ?></strong></td>
                            <td><?php echo esc_html(GBP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('WordPress Version:', 'gutenberg-blocks-presets'); ?></strong></td>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('ACF Plugin:', 'gutenberg-blocks-presets'); ?></strong></td>
                            <td><?php echo wp_kses(function_exists('acf_register_block') ? '<span class="gbp-success">✓ Active</span>' : '<span class="gbp-error">✗ Not found</span>', array('span' => array('class' => array()))); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Block Presets Count:', 'gutenberg-blocks-presets'); ?></strong></td>
                            <td><?php echo esc_html(wp_count_posts('gbp_block_preset')->publish); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Old Blocks Count:', 'gutenberg-blocks-presets'); ?></strong></td>
                            <td><?php echo esc_html(wp_count_posts('block') ? wp_count_posts('block')->publish : 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Theme Integration -->
        <div class="gbp-tool-section">
            <h2><?php esc_html_e('Theme Integration', 'gutenberg-blocks-presets'); ?></h2>
            
            <div class="gbp-tool-card">
                <h3><?php esc_html_e('ACF Block Folders Status', 'gutenberg-blocks-presets'); ?></h3>
                <?php
                $settings = get_option('gbp_settings', array());
                $block_folders = isset($settings['block_folders']) ? $settings['block_folders'] : array();
                
                if (empty($block_folders)) {
                    echo '<p class="gbp-warning">' . esc_html(__('No block folders configured.', 'gutenberg-blocks-presets')) . '</p>';
                } else {
                    echo '<ul>';
                    foreach ($block_folders as $folder) {
                        $full_path = get_theme_file_path('/' . ltrim($folder, '/') . '/');
                        $exists = file_exists($full_path) && is_dir($full_path);
                        $status = $exists ? '<span class="gbp-success">✓</span>' : '<span class="gbp-error">✗</span>';
                        echo '<li>' . wp_kses($status, array('span' => array('class' => array()))) . ' ' . esc_html($folder) . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        </div>
        
    </div>
</div>

<?php
// Helper functions for tools

function gbp_migrate_old_blocks() {
    $old_blocks = get_posts(array(
        'post_type' => 'block',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
    
    $migrated = 0;
    
    foreach ($old_blocks as $old_block) {
        $new_post_data = array(
            'post_title' => $old_block->post_title,
            'post_content' => $old_block->post_content,
            'post_excerpt' => $old_block->post_excerpt,
            'post_status' => $old_block->post_status,
            'post_type' => 'gbp_block_preset',
            'post_author' => $old_block->post_author,
            'post_date' => $old_block->post_date,
        );
        
        $new_post_id = wp_insert_post($new_post_data);
        
        if ($new_post_id && !is_wp_error($new_post_id)) {
            // Copy meta data
            $meta_data = get_post_meta($old_block->ID);
            foreach ($meta_data as $meta_key => $meta_values) {
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
            
            // Copy taxonomies
            $taxonomies = get_object_taxonomies('block');
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($old_block->ID, $taxonomy);
                if (!is_wp_error($terms) && !empty($terms)) {
                    $term_ids = wp_list_pluck($terms, 'term_id');
                    wp_set_post_terms($new_post_id, $term_ids, $taxonomy);
                }
            }
            
            // Set migration flag
            update_post_meta($new_post_id, '_gbp_migrated_from', $old_block->ID);
            update_post_meta($new_post_id, '_gbp_block_type', 'migrated');
            
            $migrated++;
        }
    }
    
    return $migrated;
}

function gbp_reset_usage_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gbp_block_usage';
    return $wpdb->query($wpdb->prepare('TRUNCATE TABLE %1s', $table_name)) !== false;
}

function gbp_export_presets() {
    $presets = get_posts(array(
        'post_type' => 'gbp_block_preset',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
    
    if (empty($presets)) {
        return false;
    }
    
    $export_data = array(
        'version' => esc_html(GBP_VERSION),
        'export_date' => current_time('mysql'),
        'presets' => array()
    );
    
    foreach ($presets as $preset) {
        $preset_data = array(
            'title' => $preset->post_title,
            'content' => $preset->post_content,
            'excerpt' => $preset->post_excerpt,
            'status' => $preset->post_status,
            'meta' => get_post_meta($preset->ID),
            'categories' => wp_get_post_terms($preset->ID, 'gbp_block_category'),
            'tags' => wp_get_post_terms($preset->ID, 'gbp_block_tag')
        );
        
        $export_data['presets'][] = $preset_data;
    }
    
    return $export_data;
}
?>
