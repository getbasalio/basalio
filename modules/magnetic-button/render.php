<?php
/**
 * Server-side render for the Basalio: Magnetic Button block.
 *
 * The magnetism is a pointer-only decorative enhancement, the button is a real,
 * keyboard-operable link at all times. JS only runs on fine pointers with hover
 * (never touch) and never under reduced motion; with JS off the button is simply
 * a normal, fully-functional button.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused).
 * @var WP_Block $block      Block instance (unused).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Conditional, load-once shared core.
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_text = isset( $attributes['text'] ) ? trim( $attributes['text'] ) : '';

// A button needs a label.
if ( '' === $basalio_text ) {
	return '';
}

$basalio_url      = isset( $attributes['url'] ) ? $attributes['url'] : '';
$basalio_strength = isset( $attributes['strength'] ) ? (int) $attributes['strength'] : 18;
$basalio_strength = max( 4, min( 40, $basalio_strength ) ); // clamp for sanity/vestibular comfort
$basalio_new_tab  = ! empty( $attributes['opensInNewTab'] );

// Link when a URL is set, otherwise a non-navigating element.
$basalio_tag        = ( '' !== $basalio_url ) ? 'a' : 'span';
$basalio_link_attrs = '';
if ( '' !== $basalio_url ) {
	$basalio_link_attrs = ' href="' . esc_url( $basalio_url ) . '"';
	if ( $basalio_new_tab ) {
		$basalio_link_attrs .= ' target="_blank" rel="noopener noreferrer"';
	}
}

$basalio_align = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'left';
$basalio_align = in_array( $basalio_align, array( 'left', 'center', 'right' ), true ) ? $basalio_align : 'left';

// The block wrapper is a block-level container so the button sits exactly where
// the block is placed and can be aligned; the button itself stays inline.
$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'basalio-magnetic-button-wrap',
		'style' => 'text-align:' . $basalio_align,
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<<?php echo esc_html( $basalio_tag ); ?> class="basalio-magnetic-button" data-basalio-magnetic="<?php echo esc_attr( $basalio_strength ); ?>"<?php echo $basalio_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_url above. ?>>
		<span class="basalio-magnetic-button__label"><?php echo esc_html( $basalio_text ); ?></span>
	</<?php echo esc_html( $basalio_tag ); ?>>
</div>
