<?php
/**
 * Plugin Name:       Basalio
 * Plugin URI:        https://basalio.com
 * Description:        A narrow, opinionated interaction toolkit for portfolio and case-study sites. Framer-quality, accessibility-first Gutenberg blocks. Not a page builder, not a general animation engine.
 * Version:           0.6.2
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
define( 'BASALIO_VERSION', '0.6.2' );

/** Absolute path to the plugin root, with trailing slash. */
define( 'BASALIO_PATH', plugin_dir_path( __FILE__ ) );

/** Public URL to the plugin root, with trailing slash. */
define( 'BASALIO_URL', plugin_dir_url( __FILE__ ) );

/** Main plugin file, for hooks that need it. */
define( 'BASALIO_FILE', __FILE__ );

require_once BASALIO_PATH . 'includes/class-basalio.php';
require_once BASALIO_PATH . 'includes/class-contrast.php';
require_once BASALIO_PATH . 'includes/class-tokens.php';
require_once BASALIO_PATH . 'includes/class-admin.php';
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

	// Emit the default design tokens as CSS custom properties whenever the core
	// stylesheet loads. This keeps front-end rendering independent of Pro: even
	// when the Pro add-on is inactive, every block can resolve --basalio-*
	// variables. The CSS is inline so it always follows the core stylesheet and
	// needs no physical file. The static guard prevents duplicate inline CSS on
	// pages that contain more than one Basalio block.
	static $tokens_inlined = false;
	if ( ! $tokens_inlined && class_exists( 'Basalio_Tokens', false ) ) {
		wp_add_inline_style( 'basalio-core', Basalio_Tokens::to_css() );
		$tokens_inlined = true;
	}
}

/**
 * Check whether a string of block markup contains any Basalio block.
 *
 * WordPress' has_block() does not accept a namespace prefix such as "basalio/";
 * it expects an exact block name. We therefore parse the content and look for
 * any block whose name starts with "basalio/".
 *
 * @param string $content Block markup.
 * @return bool
 */
function basalio_content_has_basalio_block( $content ) {
	if ( ! function_exists( 'parse_blocks' ) ) {
		return false;
	}

	$blocks = parse_blocks( (string) $content );
	$stack  = $blocks;

	while ( ! empty( $stack ) ) {
		$block = array_shift( $stack );

		if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'basalio/' ) ) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$stack = array_merge( $stack, $block['innerBlocks'] );
		}
	}

	return false;
}

/**
 * Determine whether the current page contains a Basalio block.
 *
 * Used by the Pro add-on (and any future extension) to decide whether to load
 * Pro assets. The detection looks at the main post content, the block template,
 * and any blocks rendered so far in the current request.
 *
 * @return bool
 */
function basalio_has_block() {
	static $has_block = null;

	if ( null !== $has_block ) {
		return $has_block;
	}

	$has_block = false;

	// Current post content.
	if ( is_singular() ) {
		$post = get_post();
		if ( $post && basalio_content_has_basalio_block( $post->post_content ) ) {
			$has_block = true;
		}
	}

	// FSE block template.
	if ( ! $has_block && function_exists( 'get_block_template' ) ) {
		$template = get_block_template( get_stylesheet() . '//' . basename( get_page_template() ), 'wp_template' );
		if ( $template && basalio_content_has_basalio_block( $template->content ) ) {
			$has_block = true;
		}
	}

	/**
	 * Filter whether the current page contains a Basalio block.
	 *
	 * @param bool $has_block Current detection result.
	 */
	$has_block = (bool) apply_filters( 'basalio_has_block', $has_block );

	return $has_block;
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
