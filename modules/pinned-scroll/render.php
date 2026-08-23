<?php
/**
 * Server-side render for the Basalio: Pinned Scroll block.
 *
 * The pinning is pure CSS (position: sticky), so there is no scroll hijacking:
 * focus is never trapped, the back button behaves normally, and everything stays
 * in the DOM. Under reduced motion or on narrow screens the sticky is dropped and
 * the layout becomes a normal stack.
 *
 * The pinning itself ships no JavaScript. The shared core is still enqueued
 * because this block's image can be configured as a lightbox trigger, and that
 * trigger is inert without it.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (the story), rendered.
 * @var WP_Block $block      Block instance, used to read raw saved attributes for motionParams.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This block's own pinning is pure CSS, but its image can be a lightbox
// trigger, which needs the shared core script and stylesheet. Enqueue is
// idempotent and load-once.
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_id  = isset( $attributes['pinnedImageId'] ) ? absint( $attributes['pinnedImageId'] ) : 0;
$basalio_url = isset( $attributes['pinnedImageUrl'] ) ? $attributes['pinnedImageUrl'] : '';
$basalio_alt = isset( $attributes['pinnedImageAlt'] ) ? $attributes['pinnedImageAlt'] : '';
$basalio_pos = ( isset( $attributes['mediaPosition'] ) && 'right' === $attributes['mediaPosition'] ) ? 'right' : 'left';

// Needs a pinned image to be meaningful.
if ( ! $basalio_id && '' === $basalio_url ) {
	return '';
}

// Corner radius, media/story split, and click behavior.
$basalio_radius = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 4;
$basalio_mw     = isset( $attributes['mediaWidth'] ) ? max( 25, min( 75, absint( $attributes['mediaWidth'] ) ) ) : 50;
$basalio_link   = isset( $attributes['linkUrl'] ) ? $attributes['linkUrl'] : '';
$basalio_lb     = ! empty( $attributes['lightbox'] );
$basalio_full   = $basalio_id ? wp_get_attachment_image_url( $basalio_id, 'full' ) : $basalio_url;

$basalio_img = basalio_get_image_html(
	$basalio_id,
	$basalio_url,
	$basalio_alt,
	array(
		'class'    => 'basalio-pinned-scroll__image',
		'decoding' => 'async',
		'loading'  => 'eager',
	)
);

$basalio_img = basalio_media_wrap( $basalio_img, $basalio_link, $basalio_lb, $basalio_full, $basalio_alt );

$basalio_style   = '--basalio-ps-cols:' . $basalio_mw . 'fr ' . ( 100 - $basalio_mw ) . 'fr;--basalio-ps-radius:' . $basalio_radius . 'px';

// Motion parameters.
$basalio_raw_attrs = ( $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) )
	? $block->parsed_block['attrs']
	: $attributes;
$basalio_motion_params = isset( $basalio_raw_attrs['motionParams'] ) && is_array( $basalio_raw_attrs['motionParams'] )
	? $basalio_raw_attrs['motionParams']
	: array();

$basalio_motion_css = class_exists( 'Basalio', false )
	? Basalio::resolve_motion_params_to_css( 'basalio/pinned-scroll', $basalio_motion_params )
	: array( 'style' => '', 'classes' => array() );

$basalio_class = 'basalio-pinned-scroll media-' . $basalio_pos;
if ( ! empty( $basalio_motion_css['classes'] ) ) {
	$basalio_class .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
}
if ( ! empty( $basalio_motion_css['style'] ) ) {
	$basalio_style .= ';' . $basalio_motion_css['style'];
}

$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $basalio_class,
		'style' => $basalio_style,
	)
);

// Match DOM order to visual order so Tab and screen-reader reading order follow
// the layout (WCAG 1.3.2 / 2.4.3). On the right variant the story comes first
// visually, so it is emitted first in the DOM.
$basalio_media_html = '<div class="basalio-pinned-scroll__media"><div class="basalio-pinned-scroll__media-inner">' . $basalio_img . '</div></div>';
$basalio_story_html = '<div class="basalio-pinned-scroll__story">' . $content . '</div>';
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( 'right' === $basalio_pos ) : ?>
		<?php echo $basalio_story_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from core-rendered inner blocks / escaped image parts. ?>
		<?php echo $basalio_media_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from escaped image parts. ?>
	<?php else : ?>
		<?php echo $basalio_media_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from escaped image parts. ?>
		<?php echo $basalio_story_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from core-rendered inner blocks. ?>
	<?php endif; ?>
</div>
