<?php
/**
 * Server-side render for the Basalio: Grid Reveal block.
 *
 * Dynamic render was chosen over a static save() so that:
 *   - markup and escaping live in one place and can be fixed later WITHOUT
 *     invalidating already-saved blocks (no "this block contains unexpected
 *     content" recovery prompts for users on update);
 *   - images use wp_get_attachment_image() for real responsive srcset + alt;
 *   - the shared core is enqueued here, which is what makes it load only on
 *     pages that actually contain this block.
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

// Conditional, load-once shared core. Only reached because this block is on
// the page, so the script never loads site-wide.
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_title     = isset( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$basalio_tag       = isset( $attributes['tag'] ) ? trim( $attributes['tag'] ) : '';

// Motion parameters (revealStyle is also a legacy top-level attribute).
$basalio_raw_attrs = ( $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) )
	? $block->parsed_block['attrs']
	: $attributes;
$basalio_motion_params = isset( $basalio_raw_attrs['motionParams'] ) && is_array( $basalio_raw_attrs['motionParams'] )
	? $basalio_raw_attrs['motionParams']
	: array();

$basalio_style = ( isset( $basalio_motion_params['revealStyle'] ) && 'opacity' === $basalio_motion_params['revealStyle'] )
	? 'opacity'
	: ( ( isset( $attributes['revealStyle'] ) && 'opacity' === $attributes['revealStyle'] ) ? 'opacity' : 'crossfade' );

// Merge legacy top-level values so resolved CSS matches the rendered values.
$basalio_resolve_params = $basalio_motion_params;
if ( ! isset( $basalio_resolve_params['revealStyle'] ) ) {
	$basalio_resolve_params['revealStyle'] = $basalio_style;
}

$basalio_image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$basalio_image_url = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$basalio_image_alt = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';

// Nothing to render without an image.
if ( ! $basalio_image_id && '' === $basalio_image_url ) {
	return '';
}

// Radius, aspect ratio (tile shape/size), and click behavior.
$basalio_radius   = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 2;
$basalio_link     = isset( $attributes['linkUrl'] ) ? $attributes['linkUrl'] : '';
$basalio_lightbox = ! empty( $attributes['lightbox'] );
$basalio_full_url = $basalio_image_id ? wp_get_attachment_image_url( $basalio_image_id, 'full' ) : $basalio_image_url;

$basalio_ratios    = array(
	'1:1'  => '1 / 1',
	'4:3'  => '4 / 3',
	'3:2'  => '3 / 2',
	'16:9' => '16 / 9',
	'4:5'  => '4 / 5',
	'3:4'  => '3 / 4',
	'2:3'  => '2 / 3',
);
$basalio_ratio_key = isset( $attributes['aspectRatio'] ) ? (string) $attributes['aspectRatio'] : 'auto';
$basalio_ratio_css = isset( $basalio_ratios[ $basalio_ratio_key ] ) ? $basalio_ratios[ $basalio_ratio_key ] : '';

// The reveal toggle is a named sibling control; the image itself is not inside
// it, so an empty author alt must fall back to attachment/title/tag text.
$basalio_image_alt_final = basalio_resolve_image_name(
	$basalio_image_id,
	$basalio_image_alt,
	$basalio_title,
	$basalio_tag
);

// Build the image markup: prefer the attachment (responsive srcset + alt),
// fall back to a bare URL if only that was stored.
$basalio_img_html = basalio_get_image_html(
	$basalio_image_id,
	$basalio_image_url,
	$basalio_image_alt_final,
	array(
		'class'    => 'basalio-grid-reveal__image',
		'loading'  => 'lazy',
		'decoding' => 'async',
	)
);

$basalio_has_caption = ( '' !== $basalio_title || '' !== $basalio_tag );
$basalio_uid         = wp_unique_id( 'basalio-gr-' );
$basalio_caption_id  = $basalio_uid . '-caption';

// Accessible name for the reveal control (mostly meaningful on touch).
$basalio_name_parts = array_filter( array( $basalio_title, $basalio_tag ) );
if ( ! empty( $basalio_name_parts ) ) {
	$basalio_button_label = sprintf(
		/* translators: %s: project title and/or tag, used as the reveal button's accessible name. */
		__( 'Show details: %s', 'basalio' ),
		implode( ', ', $basalio_name_parts )
	);
} else {
	$basalio_button_label = __( 'Show project details', 'basalio' );
}

$basalio_gr_classes = 'basalio-grid-reveal is-style-' . $basalio_style . ( '' !== $basalio_ratio_css ? ' has-ratio' : '' );
$basalio_gr_style   = '--basalio-radius:' . $basalio_radius . 'px';
if ( '' !== $basalio_ratio_css ) {
	$basalio_gr_style .= ';--basalio-gr-ratio:' . $basalio_ratio_css;
}

$basalio_motion_css = class_exists( 'Basalio', false )
	? Basalio::resolve_motion_params_to_css( 'basalio/grid-reveal', $basalio_resolve_params )
	: array( 'style' => '', 'classes' => array() );

if ( ! empty( $basalio_motion_css['classes'] ) ) {
	$basalio_gr_classes .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
}
if ( ! empty( $basalio_motion_css['style'] ) ) {
	$basalio_gr_style .= ';' . $basalio_motion_css['style'];
}

$basalio_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $basalio_gr_classes,
		'style' => $basalio_gr_style,
	)
);

// The tile overlay control is a link, a lightbox trigger, or the plain
// tap-to-reveal button, depending on how the block is configured.
$basalio_ctrl_inner    = '<span class="basalio-grid-reveal__sr">' . esc_html( $basalio_button_label ) . '</span>';
$basalio_aria_controls = $basalio_has_caption ? ' aria-controls="' . esc_attr( $basalio_caption_id ) . '"' : '';
if ( $basalio_lightbox && $basalio_full_url ) {
	$basalio_toggle = '<button type="button" class="basalio-grid-reveal__toggle" data-basalio-lightbox="' . esc_url( $basalio_full_url ) . '" data-basalio-lightbox-alt="' . esc_attr( $basalio_image_alt ) . '" aria-expanded="false"' . $basalio_aria_controls . '>' . $basalio_ctrl_inner . '</button>';
} elseif ( '' !== $basalio_link ) {
	$basalio_toggle = '<a class="basalio-grid-reveal__toggle" href="' . esc_url( $basalio_link ) . '"' . $basalio_aria_controls . '>' . $basalio_ctrl_inner . '</a>';
} else {
	$basalio_toggle = '<button type="button" class="basalio-grid-reveal__toggle" aria-expanded="false"' . $basalio_aria_controls . '>' . $basalio_ctrl_inner . '</button>';
}
?>
<figure <?php echo $basalio_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-grid-reveal>
	<div class="basalio-grid-reveal__frame">
		<?php echo $basalio_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_* / wp_get_attachment_image above. ?>
		<span class="basalio-grid-reveal__scrim" aria-hidden="true"></span>

		<?php if ( $basalio_has_caption ) : ?>
			<figcaption class="basalio-grid-reveal__caption" id="<?php echo esc_attr( $basalio_caption_id ); ?>">
				<?php if ( '' !== $basalio_tag ) : ?>
					<span class="basalio-grid-reveal__tag"><?php echo esc_html( $basalio_tag ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $basalio_title ) : ?>
					<span class="basalio-grid-reveal__title"><?php echo esc_html( $basalio_title ); ?></span>
				<?php endif; ?>
			</figcaption>
		<?php endif; ?>

		<?php echo $basalio_toggle; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_url / esc_attr / esc_html above. ?>
	</div>
</figure>
