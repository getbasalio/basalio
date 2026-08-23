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
 * @var WP_Block $block      Block instance, used to read raw saved attributes for motionParams.
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

// Motion parameters (strength is also a legacy top-level attribute).
$basalio_raw_attrs = ( $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) )
	? $block->parsed_block['attrs']
	: $attributes;
$basalio_motion_params = isset( $basalio_raw_attrs['motionParams'] ) && is_array( $basalio_raw_attrs['motionParams'] )
	? $basalio_raw_attrs['motionParams']
	: array();

$basalio_strength = isset( $basalio_motion_params['strength'] )
	? (int) $basalio_motion_params['strength']
	: ( isset( $attributes['strength'] ) ? (int) $attributes['strength'] : 18 );
$basalio_strength = max( 4, min( 40, $basalio_strength ) ); // clamp for sanity/vestibular comfort
$basalio_new_tab  = ! empty( $attributes['opensInNewTab'] );

// Merge legacy top-level values so resolved CSS matches the rendered values.
$basalio_resolve_params = $basalio_motion_params;
if ( ! isset( $basalio_resolve_params['strength'] ) ) {
	$basalio_resolve_params['strength'] = $basalio_strength;
}

// Link when a URL is set, otherwise a non-navigating element.
$basalio_tag        = ( '' !== $basalio_url ) ? 'a' : 'span';
$basalio_link_attrs = '';
if ( '' !== $basalio_url ) {
	$basalio_link_attrs = ' href="' . esc_url( $basalio_url ) . '"';
	if ( $basalio_new_tab ) {
		$basalio_link_attrs .= ' target="_blank" rel="noopener noreferrer"';
	}
}

$basalio_new_tab_text = $basalio_new_tab
	? ' <span class="basalio-screen-reader-text">' . esc_html__( '(opens in a new tab)', 'basalio' ) . '</span>'
	: '';

$basalio_align = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'left';
$basalio_align = in_array( $basalio_align, array( 'left', 'center', 'right' ), true ) ? $basalio_align : 'left';

// The block wrapper is a block-level container so the button sits exactly where
// the block is placed and can be aligned; the button itself stays inline.
$basalio_motion_css = class_exists( 'Basalio', false )
	? Basalio::resolve_motion_params_to_css( 'basalio/magnetic-button', $basalio_resolve_params )
	: array( 'style' => '', 'classes' => array() );

$basalio_wrapper_class = 'basalio-magnetic-button-wrap';
$basalio_wrapper_style = 'text-align:' . $basalio_align;
if ( ! empty( $basalio_motion_css['classes'] ) ) {
	$basalio_wrapper_class .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
}
if ( ! empty( $basalio_motion_css['style'] ) ) {
	$basalio_wrapper_style .= ';' . $basalio_motion_css['style'];
}

$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $basalio_wrapper_class,
		'style' => $basalio_wrapper_style,
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<<?php echo esc_html( $basalio_tag ); ?> class="basalio-magnetic-button" data-basalio-magnetic="<?php echo esc_attr( $basalio_strength ); ?>"<?php echo $basalio_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_url above. ?>>
		<span class="basalio-magnetic-button__label"><?php echo esc_html( $basalio_text ); ?></span><?php echo $basalio_new_tab_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_html__() above. ?>
	</<?php echo esc_html( $basalio_tag ); ?>>
</div>
