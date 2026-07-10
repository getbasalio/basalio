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
 * @var WP_Block $block      Block instance (unused).
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
$basalio_style     = ( isset( $attributes['revealStyle'] ) && 'opacity' === $attributes['revealStyle'] ) ? 'opacity' : 'crossfade';
$basalio_image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$basalio_image_url = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$basalio_image_alt = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';

// Nothing to render without an image.
if ( ! $basalio_image_id && '' === $basalio_image_url ) {
	return '';
}

// Build the image markup: prefer the attachment (responsive srcset + alt),
// fall back to a bare URL if only that was stored.
$basalio_img_html = '';
if ( $basalio_image_id ) {
	$basalio_img_html = wp_get_attachment_image(
		$basalio_image_id,
		'large',
		false,
		array(
			'class'    => 'basalio-grid-reveal__image',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);

	// If the editor supplied explicit alt text, honour it over the attachment's.
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
	$basalio_img_html = sprintf(
		'<img class="basalio-grid-reveal__image" src="%s" alt="%s" loading="lazy" decoding="async" />',
		esc_url( $basalio_image_url ),
		esc_attr( $basalio_image_alt )
	);
}

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

$basalio_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'basalio-grid-reveal is-style-' . $basalio_style )
);
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

		<button
			type="button"
			class="basalio-grid-reveal__toggle"
			aria-expanded="false"
			<?php if ( $basalio_has_caption ) : ?>aria-controls="<?php echo esc_attr( $basalio_caption_id ); ?>"<?php endif; ?>
		>
			<span class="basalio-grid-reveal__sr"><?php echo esc_html( $basalio_button_label ); ?></span>
		</button>
	</div>
</figure>
