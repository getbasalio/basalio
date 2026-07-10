<?php
/**
 * Server-side render for the Basalio: Before / After block.
 *
 * The reveal handle is a real ARIA slider (role="slider", tabindex, aria-value*)
 *, not a bare draggable div, so it is fully keyboard-operable (arrows / Home /
 * End / PageUp / PageDown). basalio-core.js wires pointer dragging + the keys;
 * with JS off the two images still both render, split at the start position.
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

// Conditional, load-once shared core.
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

/**
 * Build an <img> for one side (attachment preferred, URL fallback). Guarded
 * because render.php is included on every block render.
 */
if ( ! function_exists( 'basalio_ba_image' ) ) {
	function basalio_ba_image( $id, $url, $alt, $class ) {
		$html = '';
		if ( $id ) {
			$html = wp_get_attachment_image(
				$id,
				'large',
				false,
				array(
					'class'    => $class,
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			if ( '' !== $alt && $html ) {
				$html = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $alt ) . '"', $html, 1 );
			}
		}
		if ( '' === $html && '' !== $url ) {
			$html = sprintf(
				'<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_attr( $class ),
				esc_url( $url ),
				esc_attr( $alt )
			);
		}
		return $html;
	}
}

$basalio_before = basalio_ba_image(
	isset( $attributes['beforeImageId'] ) ? absint( $attributes['beforeImageId'] ) : 0,
	isset( $attributes['beforeImageUrl'] ) ? $attributes['beforeImageUrl'] : '',
	isset( $attributes['beforeImageAlt'] ) ? $attributes['beforeImageAlt'] : '',
	'basalio-before-after__img basalio-before-after__img--before'
);
$basalio_after = basalio_ba_image(
	isset( $attributes['afterImageId'] ) ? absint( $attributes['afterImageId'] ) : 0,
	isset( $attributes['afterImageUrl'] ) ? $attributes['afterImageUrl'] : '',
	isset( $attributes['afterImageAlt'] ) ? $attributes['afterImageAlt'] : '',
	'basalio-before-after__img basalio-before-after__img--after'
);

// A comparison needs both images.
if ( '' === $basalio_before || '' === $basalio_after ) {
	return '';
}

$basalio_before_label = ( isset( $attributes['beforeLabel'] ) && '' !== $attributes['beforeLabel'] ) ? $attributes['beforeLabel'] : __( 'Before', 'basalio' );
$basalio_after_label  = ( isset( $attributes['afterLabel'] ) && '' !== $attributes['afterLabel'] ) ? $attributes['afterLabel'] : __( 'After', 'basalio' );

$basalio_start = isset( $attributes['startPosition'] ) ? (int) $attributes['startPosition'] : 50;
$basalio_start = max( 0, min( 100, $basalio_start ) );

$basalio_slider_label = sprintf(
	/* translators: 1: before label, 2: after label. */
	__( 'Reveal %1$s over %2$s', 'basalio' ),
	$basalio_before_label,
	$basalio_after_label
);

$basalio_wrapper = get_block_wrapper_attributes( array( 'class' => 'basalio-before-after' ) );
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-before-after style="--basalio-ba-pos:<?php echo (int) $basalio_start; ?>%">
	<div class="basalio-before-after__frame">
		<?php echo $basalio_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_* / wp_get_attachment_image above. ?>
		<?php echo $basalio_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_* / wp_get_attachment_image above. ?>

		<span class="basalio-before-after__label basalio-before-after__label--before"><?php echo esc_html( $basalio_before_label ); ?></span>
		<span class="basalio-before-after__label basalio-before-after__label--after"><?php echo esc_html( $basalio_after_label ); ?></span>

		<span class="basalio-before-after__divider" aria-hidden="true"></span>

		<div
			class="basalio-before-after__handle"
			role="slider"
			tabindex="0"
			aria-label="<?php echo esc_attr( $basalio_slider_label ); ?>"
			aria-orientation="horizontal"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo (int) $basalio_start; ?>"
			aria-valuetext="<?php echo esc_attr( $basalio_start . '%' ); ?>"
			data-basalio-step="2"
		>
			<span class="basalio-before-after__handle-icon" aria-hidden="true"></span>
		</div>
	</div>
</div>
