<?php
/**
 * Server-side render for the Basalio: Case Study Transition block.
 *
 * Cross-document (MPA) view transitions were chosen over same-document
 * document.startViewTransition(). Reasons:
 *   - The transition is real page navigation, so the browser back button and
 *     history "just work", there is no trapped history state to manage.
 *   - The transition itself is CSS-driven (@view-transition + view-transition-
 *     name). JS is only needed for focus management, not for the animation.
 *   - Unsupported browsers ignore the unknown @view-transition at-rule and the
 *     matching names, and simply follow the <a href>, a silent fallback.
 *
 * A block has two roles:
 *   - "source": a linked grid tile (the transition's start).
 *   - "hero":   the case-study hero image (the transition's end).
 * Source and hero share a view-transition-name so the browser morphs one into
 * the other. The name is derived from a Pair ID; when that is blank it is
 * auto-derived from the linked (source) or current (hero) post ID, so the two
 * ends match with zero manual configuration in the common case.
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

// Conditional, load-once shared core (registers Module 2's behavior too).
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_role      = ( isset( $attributes['role'] ) && 'hero' === $attributes['role'] ) ? 'hero' : 'source';
$basalio_image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$basalio_image_url = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$basalio_image_alt = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';
$basalio_link_url  = isset( $attributes['linkUrl'] ) ? $attributes['linkUrl'] : '';

// Nothing to render without an image.
if ( ! $basalio_image_id && '' === $basalio_image_url ) {
	return '';
}

// Resolve the shared transition name.
// A valid CSS custom-ident must start with a letter and contain only letters,
// digits, underscores, and hyphens. Invalid or empty values fall back to the
// auto-derived ID so the transition still works.
$basalio_pair = '';
if ( isset( $attributes['pairId'] ) && is_string( $attributes['pairId'] ) ) {
	$basalio_pair_candidate = trim( $attributes['pairId'] );
	if ( '' !== $basalio_pair_candidate && preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $basalio_pair_candidate ) ) {
		$basalio_pair = $basalio_pair_candidate;
	}
}
if ( '' === $basalio_pair ) {
	if ( 'hero' === $basalio_role ) {
		$basalio_auto_id = get_the_ID();
	} else {
		$basalio_auto_id = ( '' !== $basalio_link_url ) ? url_to_postid( $basalio_link_url ) : 0;
	}
	if ( $basalio_auto_id ) {
		$basalio_pair = 'p' . $basalio_auto_id;
	}
}
$basalio_vt_name = ( '' !== $basalio_pair ) ? 'basalio-cst-' . $basalio_pair : '';

// Build image attributes. The hero is typically the LCP element, so it loads
// eagerly; grid sources stay lazy.
$basalio_img_attr = array(
	'class'    => 'basalio-cst__image',
	'decoding' => 'async',
);
if ( 'hero' === $basalio_role ) {
	$basalio_img_attr['loading']       = 'eager';
	$basalio_img_attr['fetchpriority'] = 'high';
} else {
	$basalio_img_attr['loading'] = 'lazy';
}
if ( '' !== $basalio_vt_name ) {
	$basalio_img_attr['style'] = 'view-transition-name:' . $basalio_vt_name . ';';
}

$basalio_img_html = basalio_get_image_html(
	$basalio_image_id,
	$basalio_image_url,
	$basalio_image_alt,
	$basalio_img_attr
);

// Corner radius, lightbox, and morph-duration options.
// Duration: WCAG sets no threshold for interaction-triggered motion (2.3.3
// requires it be disableable, which prefers-reduced-motion handles). The
// 150-600ms clamp follows motion-design practice for medium-to-large
// transitions, and reflects that a shared-element morph animates a large area
// with a scale change, which is the riskier end of vestibular triggers. It is
// also a page navigation: past ~600ms the visitor is watching an image travel
// instead of reading the page they asked for.
$basalio_radius   = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 0;
$basalio_lightbox = ! empty( $attributes['lightbox'] );
$basalio_full_url = $basalio_image_id ? wp_get_attachment_image_url( $basalio_image_id, 'full' ) : $basalio_image_url;
$basalio_dur      = isset( $attributes['transitionDuration'] ) ? max( 150, min( 600, absint( $attributes['transitionDuration'] ) ) ) : 300;

// Motion parameters.
$basalio_raw_attrs = ( $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) )
	? $block->parsed_block['attrs']
	: $attributes;
$basalio_motion_params = isset( $basalio_raw_attrs['motionParams'] ) && is_array( $basalio_raw_attrs['motionParams'] )
	? $basalio_raw_attrs['motionParams']
	: array();

$basalio_focus_target   = isset( $basalio_motion_params['focusTarget'] ) ? $basalio_motion_params['focusTarget'] : 'next panel';
$basalio_announce       = isset( $basalio_motion_params['announceChange'] ) ? $basalio_motion_params['announceChange'] : 'polite';

$basalio_motion_css = class_exists( 'Basalio', false )
	? Basalio::resolve_motion_params_to_css( 'basalio/case-study-transition', $basalio_motion_params )
	: array( 'style' => '', 'classes' => array() );

$basalio_motion_data = sprintf(
	'data-basalio-cst-focus-target="%s" data-basalio-cst-announce="%s"',
	esc_attr( $basalio_focus_target ),
	esc_attr( $basalio_announce )
);

$basalio_extra = array();
if ( $basalio_radius ) {
	$basalio_extra['style'] = 'border-radius:' . $basalio_radius . 'px;overflow:hidden';
}

// Scoped duration for the cross-page morph (only when it differs from the default).
// The CSS is registered and enqueued here, inside render(), so it is never
// attached to a stylesheet that may have already printed during wp_head().
// WordPress prints this late-enqueued handle after the content is known; on
// WordPress 6.9+ it is hoisted back to the <head>, and on older versions it
// falls back to the footer, but in either case it is part of the enqueued
// output and not a literal <style> element inside the block markup.
if ( '' !== $basalio_vt_name && 300 !== $basalio_dur ) {
	$basalio_dur_css = '::view-transition-group(' . $basalio_vt_name . '),::view-transition-image-pair(' . $basalio_vt_name . '){animation-duration:' . $basalio_dur . 'ms}';
	wp_register_style( 'basalio-cst-duration', false, array(), BASALIO_VERSION );
	wp_enqueue_style( 'basalio-cst-duration' );
	wp_add_inline_style( 'basalio-cst-duration', $basalio_dur_css );
}

if ( 'hero' === $basalio_role ) {
	$basalio_hero_class = 'basalio-cst basalio-cst--hero';
	if ( ! empty( $basalio_motion_css['classes'] ) ) {
		$basalio_hero_class .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
	}
	$basalio_hero_attrs = array_merge(
		array(
			'class' => $basalio_hero_class,
			'style' => $basalio_motion_css['style'],
		),
		$basalio_extra
	);
	$basalio_wrapper = get_block_wrapper_attributes( $basalio_hero_attrs );
	?>
	<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-cst-hero <?php echo $basalio_motion_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_attr above. ?>>
		<?php echo $basalio_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via wp_get_attachment_image / esc_* above. ?>
	</figure>
	<?php
	return;
}

// Source role.
$basalio_source_class = 'basalio-cst basalio-cst--source';
if ( ! empty( $basalio_motion_css['classes'] ) ) {
	$basalio_source_class .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
}
$basalio_source_attrs = array_merge(
	array(
		'class' => $basalio_source_class,
		'style' => $basalio_motion_css['style'],
	),
	$basalio_extra
);
$basalio_wrapper = get_block_wrapper_attributes( $basalio_source_attrs );

// Lightbox takes precedence over the transition link.
if ( $basalio_lightbox && $basalio_full_url ) {
	?>
	<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
		<?php echo basalio_media_wrap( $basalio_img_html, '', true, $basalio_full_url, $basalio_image_alt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in basalio_media_wrap(). ?>
	</figure>
	<?php
	return;
}

$basalio_link_label = '';
if ( '' !== $basalio_link_url ) {
	if ( isset( $attributes['linkLabel'] ) && '' !== trim( $attributes['linkLabel'] ) ) {
		$basalio_link_label = trim( $attributes['linkLabel'] );
	} else {
		$basalio_linked_post_id = url_to_postid( $basalio_link_url );
		if ( $basalio_linked_post_id ) {
			$basalio_link_label = get_the_title( $basalio_linked_post_id );
		}
	}
	if ( '' === $basalio_link_label ) {
		$basalio_link_label = __( 'View case study', 'basalio' );
	}
}

if ( '' !== $basalio_link_url ) {
	?>
	<a <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> href="<?php echo esc_url( $basalio_link_url ); ?>" data-basalio-cst-source <?php echo $basalio_motion_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_attr above. ?> aria-label="<?php echo esc_attr( $basalio_link_label ); ?>">
		<?php echo $basalio_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via wp_get_attachment_image / esc_* above. ?>
	</a>
	<?php
	return;
}

// Source with no link yet: render the image alone (no transition, no error).
?>
<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php echo $basalio_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via wp_get_attachment_image / esc_* above. ?>
</figure>
