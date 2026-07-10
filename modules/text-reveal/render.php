<?php
/**
 * Server-side render for the Basalio: Text Reveal block.
 *
 * This module has NO front-end JavaScript. The staged / word-by-word entrance
 * is pure CSS, and the word wrapping is done here in PHP. Consequently:
 *
 *   - The real text is emitted into the initial HTML response, so it is in the
 *     DOM and exposed to assistive technology immediately, the reveal is never
 *     "JS-only" (1.3.1, 4.1.2). See style.css for the invariant that the hidden
 *     state exists ONLY as a keyframe start-frame, never as a standalone rule.
 *   - Because there is no JS, render.php does NOT enqueue basalio-core.js.
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

$basalio_headline = isset( $attributes['headline'] ) ? trim( $attributes['headline'] ) : '';
$basalio_subhead  = isset( $attributes['subhead'] ) ? trim( $attributes['subhead'] ) : '';
$basalio_cta_text = isset( $attributes['ctaText'] ) ? trim( $attributes['ctaText'] ) : '';
$basalio_cta_url  = isset( $attributes['ctaUrl'] ) ? $attributes['ctaUrl'] : '';

// Nothing to render if there's no text at all.
if ( '' === $basalio_headline && '' === $basalio_subhead && '' === $basalio_cta_text ) {
	return '';
}

$basalio_level = isset( $attributes['headingLevel'] ) ? $attributes['headingLevel'] : 'h2';
if ( ! in_array( $basalio_level, array( 'h1', 'h2', 'h3', 'h4' ), true ) ) {
	$basalio_level = 'h2';
}

$basalio_word_by_word = ! empty( $attributes['wordByWord'] );

// Timing (ms). Delays are computed here so the CSS stays trivial. A single
// monotonic fade per element/word, nothing loops, so there is no flashing
// (2.3.1).
$basalio_word_step  = 55;
$basalio_stage_step = 140;
$basalio_cursor     = 0; // running delay in ms

/**
 * Render one animated item.
 *
 * Guarded because render.php is included on every block render; without the
 * guard a second Text Reveal block on the page would fatally redeclare it.
 *
 * @param string $tag     HTML tag.
 * @param string $inner   Already-escaped inner HTML.
 * @param string $classes Space-separated class list.
 * @param int    $delay   animation-delay in ms.
 * @param string $extra   Extra attributes (already escaped), e.g. href.
 * @return string
 */
if ( ! function_exists( 'basalio_tr_item' ) ) {
	function basalio_tr_item( $tag, $inner, $classes, $delay, $extra = '' ) {
		return sprintf(
			'<%1$s class="%2$s" style="--basalio-tr-delay:%3$dms"%4$s>%5$s</%1$s>',
			$tag,
			esc_attr( $classes ),
			(int) $delay,
			$extra,
			$inner
		);
	}
}

$basalio_parts = '';

// --- Headline -----------------------------------------------------------
if ( '' !== $basalio_headline ) {
	if ( $basalio_word_by_word ) {
		$basalio_words = preg_split( '/\s+/u', $basalio_headline, -1, PREG_SPLIT_NO_EMPTY );
		$basalio_spans = array();
		foreach ( $basalio_words as $i => $basalio_word ) {
			$basalio_delay   = $basalio_cursor + ( $i * $basalio_word_step );
			$basalio_spans[] = basalio_tr_item(
				'span',
				esc_html( $basalio_word ),
				'basalio-text-reveal__word basalio-text-reveal__item',
				$basalio_delay
			);
		}
		// Real spaces between word spans keep the text natural for screen readers.
		$basalio_headline_inner = implode( ' ', $basalio_spans );
		$basalio_cursor        += ( count( $basalio_words ) - 1 ) * $basalio_word_step;

		$basalio_parts .= sprintf(
			'<%1$s class="basalio-text-reveal__headline">%2$s</%1$s>',
			$basalio_level,
			$basalio_headline_inner
		);
	} else {
		$basalio_parts .= basalio_tr_item(
			$basalio_level,
			esc_html( $basalio_headline ),
			'basalio-text-reveal__headline basalio-text-reveal__item',
			$basalio_cursor
		);
	}
	$basalio_cursor += $basalio_stage_step;
}

// --- Subhead ------------------------------------------------------------
if ( '' !== $basalio_subhead ) {
	$basalio_parts .= basalio_tr_item(
		'p',
		esc_html( $basalio_subhead ),
		'basalio-text-reveal__subhead basalio-text-reveal__item',
		$basalio_cursor
	);
	$basalio_cursor += $basalio_stage_step;
}

// --- CTA ----------------------------------------------------------------
if ( '' !== $basalio_cta_text ) {
	if ( '' !== $basalio_cta_url ) {
		$basalio_parts .= basalio_tr_item(
			'a',
			esc_html( $basalio_cta_text ),
			'basalio-text-reveal__cta basalio-text-reveal__item',
			$basalio_cursor,
			' href="' . esc_url( $basalio_cta_url ) . '"'
		);
	} else {
		$basalio_parts .= basalio_tr_item(
			'span',
			esc_html( $basalio_cta_text ),
			'basalio-text-reveal__cta basalio-text-reveal__item',
			$basalio_cursor
		);
	}
}

$basalio_wrapper = get_block_wrapper_attributes( array( 'class' => 'basalio-text-reveal' ) );
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-text-reveal>
	<?php echo $basalio_parts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_html / esc_url / esc_attr above. ?>
</div>
