<?php
/**
 * Server-side render for the Basalio: Scroll Sequence block.
 *
 * A tall wrapper gives scroll room; an inner stage sticks to the viewport and
 * shows one frame at a time. basalio-core.js maps scroll progress to the active
 * frame. The Previous and Next buttons are the manual, non-scroll way to step
 * through frames (WCAG 2.5.1), and under reduced motion they are the only driver.
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

if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_frames = ( isset( $attributes['frames'] ) && is_array( $attributes['frames'] ) ) ? $attributes['frames'] : array();
if ( count( $basalio_frames ) === 0 ) {
	return '';
}

$basalio_screens = isset( $attributes['scrollScreens'] ) ? (int) $attributes['scrollScreens'] : 3;
$basalio_screens = max( 2, min( 8, $basalio_screens ) );

$basalio_total    = count( $basalio_frames );
$basalio_controls = ! isset( $attributes['showControls'] ) || ! empty( $attributes['showControls'] );
$basalio_format   = ( isset( $attributes['paginationFormat'] ) && '' !== trim( $attributes['paginationFormat'] ) )
	? $attributes['paginationFormat']
	/* translators: {current} and {total} are placeholders replaced with frame numbers. Keep both. */
	: __( 'Frame {current} of {total}', 'basalio' );

$basalio_frames_html = '';
foreach ( $basalio_frames as $basalio_i => $basalio_frame ) {
	$basalio_id  = isset( $basalio_frame['id'] ) ? absint( $basalio_frame['id'] ) : 0;
	$basalio_url = isset( $basalio_frame['url'] ) ? $basalio_frame['url'] : '';
	$basalio_alt = isset( $basalio_frame['alt'] ) ? $basalio_frame['alt'] : '';
	$basalio_cls = 'basalio-scroll-sequence__frame' . ( 0 === $basalio_i ? ' is-active' : '' );

	$basalio_img = '';
	if ( $basalio_id ) {
		$basalio_img = wp_get_attachment_image(
			$basalio_id,
			'large',
			false,
			array(
				'class'    => $basalio_cls,
				'decoding' => 'async',
				'loading'  => ( 0 === $basalio_i ? 'eager' : 'lazy' ),
			)
		);
		// Honor the author's per-frame alt override, matching Grid Reveal,
		// Case Study Transition, Before/After and Pinned Scroll. Without this
		// the alt entered in the editor is silently discarded in favour of the
		// attachment's own alt, and behaviour differs from the URL branch below.
		if ( '' !== $basalio_alt && $basalio_img ) {
			$basalio_img = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $basalio_alt ) . '"', $basalio_img, 1 );
		}
	}
	if ( '' === $basalio_img && '' !== $basalio_url ) {
		$basalio_img = sprintf(
			'<img class="%s" src="%s" alt="%s" decoding="async" loading="%s" />',
			esc_attr( $basalio_cls ),
			esc_url( $basalio_url ),
			esc_attr( $basalio_alt ),
			( 0 === $basalio_i ? 'eager' : 'lazy' )
		);
	}
	$basalio_frames_html .= $basalio_img;
}

$basalio_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'basalio-scroll-sequence',
		'style' => '--basalio-seq-screens:' . $basalio_screens . ';',
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-scroll-sequence>
	<div class="basalio-scroll-sequence__stage">
		<?php echo $basalio_frames_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built via esc_* / wp_get_attachment_image above. ?>
		<?php if ( $basalio_controls ) : ?>
		<div class="basalio-scroll-sequence__controls">
			<button type="button" class="basalio-scroll-sequence__btn" data-basalio-seq-prev aria-label="<?php esc_attr_e( 'Previous frame', 'basalio' ); ?>">&larr;</button>
			<p class="basalio-scroll-sequence__status" data-basalio-seq-total="<?php echo esc_attr( $basalio_total ); ?>" data-basalio-seq-format="<?php echo esc_attr( $basalio_format ); ?>">
				<?php
				echo esc_html(
					str_replace(
						array( '{current}', '{total}' ),
						array( '1', (string) $basalio_total ),
						$basalio_format
					)
				);
				?>
			</p>
			<button type="button" class="basalio-scroll-sequence__btn" data-basalio-seq-next aria-label="<?php esc_attr_e( 'Next frame', 'basalio' ); ?>">&rarr;</button>
		</div>
		<?php // The live region. Deliberately starts empty so the first button press is announced, and is written to only by the step buttons, never by the scroll driver. ?>
		<span class="basalio-scroll-sequence__announcer" role="status" data-basalio-seq-announcer></span>
		<?php endif; ?>
	</div>
</div>
