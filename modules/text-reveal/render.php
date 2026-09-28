<?php
/**
 * Server-side render for the Basalio: Text Reveal block.
 *
 * The real text is emitted into the initial HTML response, so it is in the DOM
 * and exposed to assistive technology immediately; the reveal is never
 * "JS-only" (1.3.1, 4.1.2). The hidden state exists ONLY as a keyframe
 * start-frame plus animation-fill-mode, never as a standalone rule.
 *
 * The scroll-into-view trigger is enhanced by the shared Basalio core JS.
 * Calling basalio_enqueue_core() here guarantees the core loads for any page
 * that contains this block.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused).
 * @var WP_Block $block      Block instance, used to read raw saved attributes for legacy migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The scroll trigger lives in the shared core.
basalio_enqueue_core();

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

// ---------------------------------------------------------------------------
// Motion parameters
// ---------------------------------------------------------------------------

$basalio_motion_defaults = array(
	'splitUnit'              => 'line',
	'duration'               => 'base',
	'easing'                 => 'glide',
	'triggerThreshold'       => 50,
	'reducedMotionFallback'  => 'appear instantly',
);

$basalio_raw_attrs = ( $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) )
	? $block->parsed_block['attrs']
	: $attributes;

$basalio_motion_params = isset( $basalio_raw_attrs['motionParams'] ) && is_array( $basalio_raw_attrs['motionParams'] )
	? $basalio_raw_attrs['motionParams']
	: array();

if ( empty( $basalio_motion_params ) ) {
	// Legacy migration path: the block predates the motionParams object.
	$basalio_motion_params = array(
		'splitUnit'              => ! empty( $basalio_raw_attrs['wordByWord'] ) ? 'word' : 'line',
		'easing'                 => 'glide',
		'triggerThreshold'       => 50,
		'reducedMotionFallback'  => 'appear instantly',
	);

	$basalio_legacy_duration = isset( $basalio_raw_attrs['duration'] ) ? (int) $basalio_raw_attrs['duration'] : 600;
	if ( $basalio_legacy_duration <= 350 ) {
		$basalio_motion_params['duration'] = 'short';
	} elseif ( $basalio_legacy_duration > 900 ) {
		$basalio_motion_params['duration'] = 'long';
	} else {
		$basalio_motion_params['duration'] = 'base';
	}
}

$basalio_motion_params = wp_parse_args( $basalio_motion_params, $basalio_motion_defaults );

// Word-by-word is now driven by splitUnit.
$basalio_word_by_word = ( 'word' === $basalio_motion_params['splitUnit'] );

// Resolve the duration token to a numeric ms value for stagger math.
$basalio_duration_token = $basalio_motion_params['duration'];
if ( 'short' === $basalio_duration_token ) {
	$basalio_duration = 200;
} elseif ( 'long' === $basalio_duration_token ) {
	$basalio_duration = 1200;
} else {
	$basalio_duration = 600;
}

// Per-element fade duration (ms). The stagger between words and stages scales
// with it so a slower duration also spreads the entrance out.
$basalio_word_step  = max( 20, (int) round( $basalio_duration * 0.09 ) );
$basalio_stage_step = max( 60, (int) round( $basalio_duration * 0.23 ) );
$basalio_cursor     = 0; // running delay in ms.

$basalio_parts = '';

// --- Headline -----------------------------------------------------------
if ( '' !== $basalio_headline ) {
	if ( $basalio_word_by_word ) {
		$basalio_words = preg_split( '/\s+/u', $basalio_headline, -1, PREG_SPLIT_NO_EMPTY );
		$basalio_spans = array();
		foreach ( $basalio_words as $basalio_i => $basalio_word ) {
			$basalio_delay   = $basalio_cursor + ( $basalio_i * $basalio_word_step );
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

// --- CSS variables ------------------------------------------------------
// Resolve motion params generically from the schema. Adding a new typed param
// to motion.schema.json now surfaces on the front end without editing this file.

$basalio_schema = class_exists( 'Basalio', false ) ? Basalio::get_block_schema( 'basalio/text-reveal' ) : array();
$basalio_trigger  = isset( $basalio_schema['trigger'] ) ? $basalio_schema['trigger'] : 'Scroll into view';

$basalio_motion_css = class_exists( 'Basalio', false )
	? Basalio::resolve_motion_params_to_css( 'basalio/text-reveal', $basalio_motion_params )
	: array( 'style' => '', 'classes' => array() );

$basalio_wrapper_class = 'basalio-text-reveal';
if ( ! empty( $basalio_motion_css['classes'] ) ) {
	$basalio_wrapper_class .= ' ' . implode( ' ', array_map( 'esc_attr', $basalio_motion_css['classes'] ) );
}

$basalio_wrapper_attrs = array(
	'class' => $basalio_wrapper_class,
	'style' => $basalio_motion_css['style'],
);

if ( 'Scroll into view' === $basalio_trigger ) {
	$basalio_wrapper_attrs['data-basalio-motion-reveal'] = '';
}

$basalio_wrapper = get_block_wrapper_attributes( $basalio_wrapper_attrs );
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-text-reveal>
	<?php echo wp_kses( $basalio_parts, basalio_allowed_html() ); ?>
</div>
