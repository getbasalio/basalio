<?php
/**
 * Server-side render for the Basalio: Pinned Scroll block.
 *
 * The pinning is pure CSS (position: sticky), so there is no scroll hijacking:
 * focus is never trapped, the back button behaves normally, and everything stays
 * in the DOM. Under reduced motion or on narrow screens the sticky is dropped and
 * the layout becomes a normal stack. This block ships no JavaScript.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (the story), rendered.
 * @var WP_Block $block      Block instance (unused).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

$basalio_img = '';
if ( $basalio_id ) {
	$basalio_img = wp_get_attachment_image(
		$basalio_id,
		'large',
		false,
		array(
			'class'    => 'basalio-pinned-scroll__image',
			'decoding' => 'async',
			'loading'  => 'lazy',
		)
	);
	if ( '' !== $basalio_alt && $basalio_img ) {
		$basalio_img = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $basalio_alt ) . '"', $basalio_img, 1 );
	}
}
if ( '' === $basalio_img && '' !== $basalio_url ) {
	$basalio_img = sprintf(
		'<img class="basalio-pinned-scroll__image" src="%s" alt="%s" decoding="async" loading="lazy" />',
		esc_url( $basalio_url ),
		esc_attr( $basalio_alt )
	);
}

$basalio_img = basalio_media_wrap( $basalio_img, $basalio_link, $basalio_lb, $basalio_full, $basalio_alt );

$basalio_style   = '--basalio-ps-cols:' . $basalio_mw . 'fr ' . ( 100 - $basalio_mw ) . 'fr;--basalio-ps-radius:' . $basalio_radius . 'px';
$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'basalio-pinned-scroll media-' . $basalio_pos,
		'style' => $basalio_style,
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="basalio-pinned-scroll__media">
		<div class="basalio-pinned-scroll__media-inner">
			<?php echo $basalio_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_* / wp_get_attachment_image / basalio_media_wrap above. ?>
		</div>
	</div>
	<div class="basalio-pinned-scroll__story"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks rendered by core. ?></div>
</div>
