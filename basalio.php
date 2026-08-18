<?php
/**
 * Plugin Name:       Basalio
 * Plugin URI:        https://basalio.com
 * Description:        A narrow, opinionated interaction toolkit for portfolio and case-study sites. Framer-quality, accessibility-first Gutenberg blocks. Not a page builder, not a general animation engine.
 * Version:           0.5.1
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            ChrowmDesigns
 * Author URI:        https://chrowmdesigns.com
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
define( 'BASALIO_VERSION', '0.5.1' );

/** Absolute path to the plugin root, with trailing slash. */
define( 'BASALIO_PATH', plugin_dir_path( __FILE__ ) );

/** Public URL to the plugin root, with trailing slash. */
define( 'BASALIO_URL', plugin_dir_url( __FILE__ ) );

/** Main plugin file, for hooks that need it. */
define( 'BASALIO_FILE', __FILE__ );

require_once BASALIO_PATH . 'includes/class-basalio.php';
require_once BASALIO_PATH . 'includes/helpers.php';

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
	wp_enqueue_style( 'basalio-core' );
}

/**
 * Wrap an image's HTML in the click behavior an image block was configured with.
 *
 * Lightbox wins over a link when both are set. Returns the image untouched when
 * neither is configured. Shared by every image block so the behavior is
 * identical and escaping lives in one place.
 *
 * @param string $img_html  Fully-built (already escaped) <img> markup.
 * @param string $link_url  Optional link target.
 * @param bool   $lightbox  Whether to open the full image in the lightbox.
 * @param string $full_url  Full-size image URL for the lightbox.
 * @param string $alt       Alt/caption text for the lightbox.
 * @return string
 */
function basalio_media_wrap( $img_html, $link_url, $lightbox, $full_url, $alt = '' ) {
	if ( $lightbox && $full_url ) {
		// A lightbox trigger is inert without the core script that binds it, and
		// unstyled without the core stylesheet. Enqueue here rather than trusting
		// every calling module to remember: a focusable button that announces
		// "Expand image" and then does nothing is a 4.1.2 failure, not a cosmetic
		// one. Enqueuing is idempotent, so modules that already call
		// basalio_enqueue_core() themselves are unaffected.
		basalio_enqueue_core();

		return '<button type="button" class="basalio-media-expand" data-basalio-lightbox="'
			. esc_url( $full_url ) . '" data-basalio-lightbox-alt="' . esc_attr( $alt )
			. '" aria-label="' . esc_attr__( 'Expand image', 'basalio' ) . '">' . $img_html . '</button>';
	}
	$link_url = esc_url( (string) $link_url );
	if ( '' !== $link_url ) {
		return '<a class="basalio-media-link" href="' . $link_url . '">' . $img_html . '</a>';
	}
	return $img_html;
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
