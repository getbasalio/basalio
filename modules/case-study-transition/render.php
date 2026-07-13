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
 * @var WP_Block $block      Block instance (unused).
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
$basalio_pair = isset( $attributes['pairId'] ) ? preg_replace( '/[^a-z0-9\-]/', '', strtolower( $attributes['pairId'] ) ) : '';
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
// Prefix guarantees the value is a valid CSS custom-ident (starts with a letter).
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

$basalio_img_html = '';
if ( $basalio_image_id ) {
	$basalio_img_html = wp_get_attachment_image( $basalio_image_id, 'large', false, $basalio_img_attr );

	if ( '' !== $basalio_image_alt && $basalio_img_html ) {
		$basalio_img_html = preg_replace(
			'/alt="[^"]*"/',
			'alt="' . esc_attr( $basalio_image_alt ) . '"',
			$basalio_img_html,
			1
		);
	}
}

if ( '' === $basalio_img_html && '' !== $basalio_image_url ) {
	$basalio_loading    = ( 'hero' === $basalio_role ) ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
	$basalio_style_attr = ( '' !== $basalio_vt_name ) ? ' style="' . esc_attr( 'view-transition-name:' . $basalio_vt_name . ';' ) . '"' : '';
	$basalio_img_html   = sprintf(
		'<img class="basalio-cst__image" src="%s" alt="%s"%s decoding="async"%s />',
		esc_url( $basalio_image_url ),
		esc_attr( $basalio_image_alt ),
		$basalio_loading,
		$basalio_style_attr
	);
}

// Corner radius, lightbox, and morph-duration options.
$basalio_radius   = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 0;
$basalio_lightbox = ! empty( $attributes['lightbox'] );
$basalio_full_url = $basalio_image_id ? wp_get_attachment_image_url( $basalio_image_id, 'full' ) : $basalio_image_url;
$basalio_dur      = isset( $attributes['transitionDuration'] ) ? max( 100, min( 3000, absint( $attributes['transitionDuration'] ) ) ) : 500;

$basalio_extra = array();
if ( $basalio_radius ) {
	$basalio_extra['style'] = 'border-radius:' . $basalio_radius . 'px;overflow:hidden';
}

// Scoped duration for the cross-page morph (only when it differs from the default).
$basalio_dur_style = '';
if ( '' !== $basalio_vt_name && 500 !== $basalio_dur ) {
	$basalio_dur_style = '<style>::view-transition-group(' . $basalio_vt_name . '),::view-transition-image-pair(' . $basalio_vt_name . '){animation-duration:' . $basalio_dur . 'ms}</style>';
}

if ( 'hero' === $basalio_role ) {
	$basalio_wrapper = get_block_wrapper_attributes( array_merge( array( 'class' => 'basalio-cst basalio-cst--hero' ), $basalio_extra ) );
	echo $basalio_dur_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup with sanitized custom-ident + int.
	?>
	<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-cst-hero>
		<?php echo $basalio_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via wp_get_attachment_image / esc_* above. ?>
	</figure>
	<?php
	return;
}

// Source role.
$basalio_wrapper = get_block_wrapper_attributes( array_merge( array( 'class' => 'basalio-cst basalio-cst--source' ), $basalio_extra ) );

// Lightbox takes precedence over the transition link.
if ( $basalio_lightbox && $basalio_full_url ) {
	?>
	<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
		<?php echo basalio_media_wrap( $basalio_img_html, '', true, $basalio_full_url, $basalio_image_alt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in basalio_media_wrap(). ?>
	</figure>
	<?php
	return;
}

if ( '' !== $basalio_link_url ) {
	echo $basalio_dur_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup with sanitized custom-ident + int.
	?>
	<a <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> href="<?php echo esc_url( $basalio_link_url ); ?>" data-basalio-cst-source>
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
