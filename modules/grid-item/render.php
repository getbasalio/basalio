<?php
/**
 * Server-side render for a Basalio: Grid Item (child of Filterable Grid).
 *
 * Emits its category as a slug in `data-basalio-item-category` so the parent's
 * filter buttons can match it. No core enqueue here, the parent already loads
 * it, and this block only ever renders inside the parent.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance (unused).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$basalio_title    = isset( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$basalio_category = isset( $attributes['category'] ) ? trim( $attributes['category'] ) : '';
// Uncategorized items share a real slug so the parent grid can expose a filter
// for them. Without this they disappear under every category filter.
$basalio_slug     = ( '' !== $basalio_category ) ? sanitize_title( $basalio_category ) : 'uncategorized';
$basalio_image_id = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$basalio_image_url = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$basalio_image_alt = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';
$basalio_url      = isset( $attributes['url'] ) ? $attributes['url'] : '';

// Nothing to render without an image.
if ( ! $basalio_image_id && '' === $basalio_image_url ) {
	return '';
}

$basalio_radius   = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 0;
$basalio_lightbox = ! empty( $attributes['lightbox'] );
$basalio_full     = $basalio_image_id ? wp_get_attachment_image_url( $basalio_image_id, 'full' ) : $basalio_image_url;

// Linked or lightboxed Grid Items use the M1/M2 pattern: the image is
// decorative because the wrapper carries the accessible name. A plain item
// must fall back to attachment / title / category alt.
$basalio_is_decorative = ( $basalio_lightbox && $basalio_full ) || '' !== $basalio_url;
$basalio_image_alt_final = $basalio_is_decorative
	? $basalio_image_alt
	: basalio_resolve_image_name( $basalio_image_id, $basalio_image_alt, $basalio_title, $basalio_category );

$basalio_img_html = basalio_get_image_html(
	$basalio_image_id,
	$basalio_image_url,
	$basalio_image_alt_final,
	array(
		'class'    => 'basalio-grid-item__image',
		'loading'  => 'lazy',
		'decoding' => 'async',
	),
	$basalio_is_decorative
);

$basalio_frame_style = $basalio_radius ? ' style="border-radius:' . $basalio_radius . 'px;overflow:hidden"' : '';

// The image frame is a lightbox trigger, a link, or a plain container.
if ( $basalio_lightbox && $basalio_full ) {
	$basalio_frame_open  = '<button type="button" class="basalio-grid-item__frame basalio-media-expand" data-basalio-lightbox="' . esc_url( $basalio_full ) . '" data-basalio-lightbox-alt="' . esc_attr( $basalio_image_alt_final ) . '" aria-label="' . esc_attr__( 'Expand image', 'basalio' ) . '"' . $basalio_frame_style . '>';
	$basalio_frame_close = '</button>';
} elseif ( '' !== $basalio_url ) {
	// The <figcaption> deliberately sits OUTSIDE the link, so the link's only
	// possible accessible name is the image's alt. When the author left the alt
	// empty, fall back to attachment alt, then title, then category.
	$basalio_aria = '';
	if ( '' === $basalio_image_alt ) {
		$basalio_fallback = basalio_resolve_image_name( $basalio_image_id, $basalio_image_alt, $basalio_title, $basalio_category );
		if ( '' !== $basalio_fallback ) {
			$basalio_aria = ' aria-label="' . esc_attr( $basalio_fallback ) . '"';
		}
	}
	$basalio_frame_open  = '<a class="basalio-grid-item__frame" href="' . esc_url( $basalio_url ) . '"' . $basalio_aria . $basalio_frame_style . '>';
	$basalio_frame_close = '</a>';
} else {
	$basalio_frame_open  = '<div class="basalio-grid-item__frame"' . $basalio_frame_style . '>';
	$basalio_frame_close = '</div>';
}

$basalio_wrapper = get_block_wrapper_attributes( array( 'class' => 'basalio-grid-item' ) );
?>
<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-item-category="<?php echo esc_attr( $basalio_slug ); ?>">
	<?php
	echo wp_kses( $basalio_frame_open . $basalio_img_html . $basalio_frame_close, basalio_allowed_html() );
	?>
	<?php if ( '' !== $basalio_title || '' !== $basalio_category ) : ?>
		<figcaption class="basalio-grid-item__caption">
			<?php if ( '' !== $basalio_title ) : ?>
				<span class="basalio-grid-item__title"><?php echo esc_html( $basalio_title ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $basalio_category ) : ?>
				<span class="basalio-grid-item__category"><?php echo esc_html( $basalio_category ); ?></span>
			<?php endif; ?>
		</figcaption>
	<?php endif; ?>
</figure>
