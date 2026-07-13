<?php
/**
 * Server-side render for the Basalio: Filterable Grid block.
 *
 * The parent iterates its inner grid-item blocks to (a) collect the distinct
 * categories that become the filter buttons, and (b) render each item. Filtering
 * itself is a small progressive enhancement in basalio-core.js, with JS off,
 * every item is simply visible.
 *
 * Accessibility: filters are real <button>s with aria-pressed inside a labelled
 * group; the result count lives in a role="status" (aria-live="polite") region
 * that basalio-core.js updates on each filter change.
 *
 * @package Basalio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (unused, rendered manually below).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Conditional, load-once shared core.
if ( function_exists( 'basalio_enqueue_core' ) ) {
	basalio_enqueue_core();
}

$basalio_cols = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
$basalio_cols = max( 2, min( 4, $basalio_cols ) );

$basalio_all_label = ( isset( $attributes['allLabel'] ) && '' !== $attributes['allLabel'] )
	? $attributes['allLabel']
	: __( 'All', 'basalio' );

// Walk the inner grid-item blocks once: collect categories + render each.
$basalio_cats     = array(); // slug => display label (first-seen order)
$basalio_grid     = '';
$basalio_total    = 0;

if ( isset( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $basalio_inner ) {
		$basalio_html = $basalio_inner->render();
		if ( '' === trim( $basalio_html ) ) {
			continue; // e.g. an item with no image yet
		}
		$basalio_total++;

		$basalio_cat = isset( $basalio_inner->attributes['category'] ) ? trim( (string) $basalio_inner->attributes['category'] ) : '';
		if ( '' !== $basalio_cat ) {
			$basalio_slug = sanitize_title( $basalio_cat );
			if ( '' !== $basalio_slug && ! isset( $basalio_cats[ $basalio_slug ] ) ) {
				$basalio_cats[ $basalio_slug ] = $basalio_cat;
			}
		}

		$basalio_grid .= $basalio_html;
	}
}

// Nothing to show.
if ( 0 === $basalio_total ) {
	return '';
}

$basalio_btn_radius = isset( $attributes['buttonRadius'] ) ? max( 0, absint( $attributes['buttonRadius'] ) ) : 2;
$basalio_wrapper    = get_block_wrapper_attributes(
	array(
		'class' => 'basalio-filterable-grid',
		'style' => '--basalio-fg-radius:' . $basalio_btn_radius . 'px',
	)
);
?>
<div <?php echo $basalio_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-basalio-filterable-grid>
	<?php if ( ! empty( $basalio_cats ) ) : ?>
		<div class="basalio-filterable-grid__filters" role="toolbar" aria-orientation="horizontal" aria-label="<?php esc_attr_e( 'Filter by discipline', 'basalio' ); ?>">
			<button type="button" class="basalio-filterable-grid__filter is-active" data-basalio-filter="*" aria-pressed="true"><?php echo esc_html( $basalio_all_label ); ?></button>
			<?php foreach ( $basalio_cats as $basalio_slug => $basalio_label ) : ?>
				<button type="button" class="basalio-filterable-grid__filter" data-basalio-filter="<?php echo esc_attr( $basalio_slug ); ?>" aria-pressed="false"><?php echo esc_html( $basalio_label ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="basalio-filterable-grid__status" role="status" data-basalio-total="<?php echo esc_attr( $basalio_total ); ?>">
		<?php
		printf(
			/* translators: %d: number of projects currently shown. */
			esc_html( _n( 'Showing %d project.', 'Showing %d projects.', $basalio_total, 'basalio' ) ),
			(int) $basalio_total
		);
		?>
	</p>

	<div class="basalio-filterable-grid__grid" style="--basalio-fg-cols:<?php echo (int) $basalio_cols; ?>">
		<?php echo $basalio_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each item is escaped in grid-item/render.php. ?>
	</div>
</div>
