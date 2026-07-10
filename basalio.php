<?php
/**
 * Plugin Name:       Basalio
 * Plugin URI:        https://example.com/basalio
 * Description:        A narrow, opinionated interaction toolkit for portfolio and case-study sites. Framer-quality, accessibility-first Gutenberg blocks. Not a page builder, not a general animation engine.
 * Version:           0.2.1
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Basalio
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       basalio
 * Domain Path:       /languages
 *
 * @package Basalio
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version constant. Also used as the asset cache-busting version for every
 * enqueued script/style so bumping this here busts caches everywhere.
 */
define( 'BASALIO_VERSION', '0.2.1' );

/** Absolute path to the plugin root, with trailing slash. */
define( 'BASALIO_PATH', plugin_dir_path( __FILE__ ) );

/** Public URL to the plugin root, with trailing slash. */
define( 'BASALIO_URL', plugin_dir_url( __FILE__ ) );

/** Main plugin file, for hooks that need it. */
define( 'BASALIO_FILE', __FILE__ );

require_once BASALIO_PATH . 'includes/class-basalio.php';

/**
 * Enqueue the one shared, versioned Basalio JS core.
 *
 * This is intentionally a plain global function so every module's render.php
 * can call it without knowing anything about the loader internals. Because a
 * block's render callback only fires when that block is actually on the page,
 * calling this from render.php is what guarantees the core loads *only* on
 * pages that contain at least one Basalio block, never site-wide.
 *
 * The handle is registered once (see Basalio::register_assets); enqueuing an
 * already-registered handle multiple times is deduplicated by WordPress, so
 * many Basalio blocks on one page still load a single copy of the core.
 *
 * @return void
 */
function basalio_enqueue_core() {
	wp_enqueue_script( 'basalio-core' );
}

/**
 * Runs on activation. Kept deliberately minimal: no schema, no rewrite rules.
 * We store the installed version so future upgrade routines have something to
 * compare against, and we guard the environment.
 *
 * @return void
 */
function basalio_activate() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( BASALIO_FILE ) );
		wp_die(
			esc_html__( 'Basalio requires PHP 7.4 or higher. Please upgrade PHP and try again.', 'basalio' ),
			esc_html__( 'Plugin activation error', 'basalio' ),
			array( 'back_link' => true )
		);
	}

	update_option( 'basalio_version', BASALIO_VERSION );
}
register_activation_hook( __FILE__, 'basalio_activate' );

// Boot the plugin.
Basalio::instance();
