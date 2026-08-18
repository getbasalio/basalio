<?php
/**
 * Server-side render for the Basalio: Custom Cursor block.
 *
 * Wraps inner content and shows a decorative accent that trails the pointer. The
 * native cursor is never hidden and the accent is pointer-events:none, so it can
 * never obscure or block a click target. basalio-core.js runs it only on fine
 * pointers with hover, never on touch, and never under reduced motion.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (rendered).
 * @var WP_Block $block      Block instance (unused).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_size = isset( $attributes['size'] ) ? (int) $attributes['size'] : 30;
$basalio_size = max( 10, min( 80, $basalio_size ) );

$basalio_color = isset( $attributes['color'] ) ? sanitize_hex_color( $attributes['color'] ) : '';
if ( '' === (string) $basalio_color ) {
	$basalio_color = '#DFFF00';
}

$basalio_type = ( isset( $attributes['cursorStyle'] ) && 'dot' === $attributes['cursorStyle'] ) ? 'dot' : 'ring';

// Optional uploaded cursor image/SVG. When set, it replaces the ring/dot.
$basalio_cur_img = '';
if ( ! empty( $attributes['cursorImageId'] ) ) {
	$basalio_cur_img = wp_get_attachment_image_url( absint( $attributes['cursorImageId'] ), 'full' );
}
if ( ! $basalio_cur_img && ! empty( $attributes['cursorImageUrl'] ) ) {
	$basalio_cur_img = $attributes['cursorImageUrl'];
}

$basalio_class = 'basalio-custom-cursor is-cursor-' . ( $basalio_cur_img ? 'image' : $basalio_type );
$basalio_style = '--basalio-cursor-size:' . $basalio_size . 'px;--basalio-cursor-color:' . $basalio_color . ';';
if ( $basalio_cur_img ) {
	$basalio_style .= "--basalio-cursor-img:url('" . esc_url( $basalio_cur_img ) . "');";
}

$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $basalio_class,
		'style' => $basalio_style,
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-custom-cursor>
	<span class="basalio-custom-cursor__accent" aria-hidden="true"></span>
	<div class="basalio-custom-cursor__content"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks rendered by core. ?></div>
</div>
