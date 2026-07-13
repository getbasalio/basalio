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
$basalio_slug     = ( '' !== $basalio_category ) ? sanitize_title( $basalio_category ) : '';
$basalio_image_id = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$basalio_image_url = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$basalio_image_alt = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';
$basalio_url      = isset( $attributes['url'] ) ? $attributes['url'] : '';

// Nothing to render without an image.
if ( ! $basalio_image_id && '' === $basalio_image_url ) {
	return '';
}

$basalio_img_html = '';
if ( $basalio_image_id ) {
	$basalio_img_html = wp_get_attachment_image(
		$basalio_image_id,
		'large',
		false,
		array(
			'class'    => 'basalio-grid-item__image',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
	if ( '' !== $basalio_image_alt && $basalio_img_html ) {
		$basalio_img_html = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $basalio_image_alt ) . '"', $basalio_img_html, 1 );
	}
}
if ( '' === $basalio_img_html && '' !== $basalio_image_url ) {
	$basalio_img_html = sprintf(
		'<img class="basalio-grid-item__image" src="%s" alt="%s" loading="lazy" decoding="async" />',
		esc_url( $basalio_image_url ),
		esc_attr( $basalio_image_alt )
	);
}

$basalio_radius   = isset( $attributes['borderRadius'] ) ? max( 0, absint( $attributes['borderRadius'] ) ) : 0;
$basalio_lightbox = ! empty( $attributes['lightbox'] );
$basalio_full     = $basalio_image_id ? wp_get_attachment_image_url( $basalio_image_id, 'full' ) : $basalio_image_url;
$basalio_frame_style = $basalio_radius ? ' style="border-radius:' . $basalio_radius . 'px;overflow:hidden"' : '';

// The image frame is a lightbox trigger, a link, or a plain container.
if ( $basalio_lightbox && $basalio_full ) {
	$basalio_frame_open  = '<button type="button" class="basalio-grid-item__frame basalio-media-expand" data-basalio-lightbox="' . esc_url( $basalio_full ) . '" data-basalio-lightbox-alt="' . esc_attr( $basalio_image_alt ) . '" aria-label="' . esc_attr__( 'Expand image', 'basalio' ) . '"' . $basalio_frame_style . '>';
	$basalio_frame_close = '</button>';
} elseif ( '' !== $basalio_url ) {
	$basalio_frame_open  = '<a class="basalio-grid-item__frame" href="' . esc_url( $basalio_url ) . '"' . $basalio_frame_style . '>';
	$basalio_frame_close = '</a>';
} else {
	$basalio_frame_open  = '<div class="basalio-grid-item__frame"' . $basalio_frame_style . '>';
	$basalio_frame_close = '</div>';
}

$basalio_wrapper = get_block_wrapper_attributes( array( 'class' => 'basalio-grid-item' ) );
?>
<figure <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-item-category="<?php echo esc_attr( $basalio_slug ); ?>">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_url / wp_get_attachment_image above.
	echo $basalio_frame_open . $basalio_img_html . $basalio_frame_close;
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
