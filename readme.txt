=== Gutenberg Blocks Presets ===
Contributors: davet86
Tags: gutenberg, blocks, presets, reusable, native
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
Text Domain: gutenberg-blocks-presets
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful native WordPress plugin for creating and managing reusable Gutenberg block presets. No dependencies required!

== Description ==

Gutenberg Blocks Presets is a comprehensive solution for managing reusable block content in WordPress. This plugin allows you to create, organize, and deploy block presets across your website with ease.

= Key Features =

* **Native WordPress**: Works with pure WordPress - no external dependencies required
* **Custom Post Type**: Dedicated "Block Presets" post type for managing reusable content
* **Gutenberg Ready**: Full integration with the native WordPress block editor
* **Usage Tracking**: Monitor which presets are used where and how often
* **REST API**: Full REST API support for headless implementations
* **Shortcode Support**: Easy insertion via shortcodes `[gbp_block id="123"]`
* **Legacy Compatibility**: Maintains compatibility with existing `do_cpt_block()` functions
* **Migration Tools**: Seamlessly migrate from old block systems
* **Statistics Dashboard**: Comprehensive usage analytics and reporting
* **Taxonomies**: Organize presets with categories and tags
* **ACF Integration (Optional)**: Automatic registration of ACF blocks from theme folders if ACF Pro is installed
* **Developer Friendly**: Extensive hooks and filters for customization

= Perfect For =

* Agencies managing multiple client sites
* Developers building custom themes with reusable components
* Content managers who need consistent design elements
* Sites with complex layouts that need to be replicated
* Teams collaborating on content creation

= Usage Examples =

In PHP templates: `gbp_render_block_preset(123);` and `do_cpt_block(123)` (legacy support)

In content: `[gbp_block id="123"]` and `[block_preset id="123"]` (legacy)

REST API: `GET /wp-json/gbp/v1/block-presets`, `GET /wp-json/gbp/v1/block-presets/123`, `GET /wp-json/gbp/v1/block-presets/123/render`

= ACF Integration =

The plugin automatically scans specified theme folders for ACF blocks and registers them in the Gutenberg editor. Default folders include:

* `general/acf-blocks/`
* `public/acf-blocks/`
* `login-register/acf-blocks/`
* `members/acf-blocks/`

You can customize these folders in the plugin settings.

= Migration Support =

Seamlessly migrate from existing block systems with built-in migration tools that handle:

* Old post type conversion
* Meta data preservation
* Taxonomy migration
* Usage statistics transfer

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/gutenberg-blocks-presets/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Settings > Blocks Presets to configure the plugin
4. Start creating block presets under the new "Block Presets" menu

== Frequently Asked Questions ==

= Does this plugin require ACF? =

No! This plugin works with pure WordPress and has no external dependencies. ACF integration is completely optional and can be enabled in settings if you have Advanced Custom Fields Pro installed.

= Can I migrate from an existing block system? =

Yes! The plugin includes migration tools accessible via Block Presets > Tools that can migrate from the old "block" post type format.

= Is this compatible with my theme? =

The plugin is designed to work with any WordPress theme. It doesn't modify your theme files but provides hooks and filters for integration.

= Can I use this with page builders? =

Yes, block presets work within the Gutenberg editor and are compatible with most page builders that support WordPress blocks.

= How do I customize the block folders? =

Go to Settings > Blocks Presets and modify the "ACF Block Folders" setting to specify which theme folders should be scanned for ACF blocks.

== Screenshots ==
1. Block Presets admin interface
2. Settings page with configuration options
3. Usage statistics dashboard
4. Migration tools interface
5. Block preset editor with meta boxes
6. ACF blocks integration

== Changelog ==

= 1.0.1 =
* Security: REST API endpoint for a single block preset no longer exposes draft/private/trashed presets to unauthenticated requests
* Security: removed a duplicate, conflicting registration of the plugin settings that had silently dropped path-safety validation on the ACF block folders setting
* Fix: resolved a fatal error that occurred whenever `gbp_render_block_preset()`, `do_cpt_block()`, or related helper functions were called from a theme
* Fix: resolved a fatal error on `plugins_loaded` caused by a missing text domain loader
* Hardened nonce verification, redirects, and JSON output across the admin screens
* Updated "Tested up to" to WordPress 7.0
* Full WordPress Coding Standards (WPCS) compliance pass
* Removed unused legacy admin code superseded by the current settings screen

= 1.0.0 =
* Initial release
* Custom post type for block presets
* ACF blocks auto-registration
* Usage tracking and statistics
* REST API endpoints
* Migration tools
* Shortcode support
* Legacy function compatibility
* Admin interface with tools and statistics
* Comprehensive uninstall cleanup

== Upgrade Notice ==

= 1.0.1 =
Security and stability update: fixes two fatal-error bugs and closes a settings-validation gap. Upgrade is recommended for all users.

= 1.0.0 =
Initial release of Gutenberg Blocks Presets. If you're migrating from a custom implementation, use the migration tools in Block Presets > Tools.

== Developer Information ==

= Hooks and Filters =

The plugin provides numerous hooks for customization:

Filters:
- `gbp_block_preset_content` - Filter block preset output

= REST API Endpoints =

- `GET /wp-json/gbp/v1/block-presets` - List all presets
- `GET /wp-json/gbp/v1/block-presets/{id}` - Get specific preset
- `GET /wp-json/gbp/v1/block-presets/{id}/render` - Get rendered preset

= File Structure =

    gutenberg-blocks-presets/
    ├── gutenberg-blocks-presets.php
    ├── uninstall.php
    ├── readme.txt
    ├── LICENSE
    ├── languages/
    │   └── index.php
    ├── assets/
    │   └── js/
    │       └── block-preset.js
    ├── includes/
    │   ├── class-gbp-post-types.php
    │   ├── class-gbp-acf-blocks.php
    │   ├── class-gbp-gutenberg-blocks.php
    │   ├── class-gbp-helper-functions.php
    │   └── class-gbp-admin.php
    └── admin/
        ├── settings-page.php
        ├── tools-page.php
        ├── statistics-page.php
        ├── css/
        │   └── admin.css
        └── js/
            └── admin.js

For more information and documentation, visit: https://neodavet.github.io/davetportfolio/
