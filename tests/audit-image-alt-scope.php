<?php
/**
 * Basalio: scope image-alt fallback behavior across image blocks.
 *
 * Renders each image-emitting block via do_blocks() with an attachment that HAS
 * media-library alt text but an EMPTY block-level alt attribute.
 *
 * Run: php tests/audit-image-alt-scope.php [/path/to/wordpress]
 */

require __DIR__ . '/bootstrap.php';

$attachment_id = 77;
$attachment_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

echo "Attachment ID: $attachment_id\n";
echo "Media-library alt text: '$attachment_alt'\n\n";

$blocks = array(
	'case-study-transition' => array(
		'imageId'  => $attachment_id,
		'imageAlt' => '',
		'role'     => 'hero',
	),
	'grid-reveal' => array(
		'imageId'  => $attachment_id,
		'imageAlt' => '',
	),
	'before-after' => array(
		'beforeImageId'  => $attachment_id,
		'beforeImageAlt' => '',
		'afterImageId'   => $attachment_id,
		'afterImageAlt'  => '',
	),
	'grid-item' => array(
		'imageId'  => $attachment_id,
		'imageAlt' => '',
		'title'    => 'Grid item title',
	),
	'pinned-scroll' => array(
		'pinnedImageId'  => $attachment_id,
		'pinnedImageAlt' => '',
	),
	'scroll-sequence' => array(
		'frames' => array(
			array(
				'id'  => $attachment_id,
				'alt' => '',
			),
		),
	),
);

foreach ( $blocks as $slug => $attrs ) {
	$block_name = 'basalio/' . $slug;

	$json = wp_json_encode( $attrs );
	$markup = "<!-- wp:$block_name $json /--><!-- wp:paragraph --><p>break</p><!-- /wp:paragraph -->";

	try {
		$html = do_blocks( $markup );
	} catch ( Throwable $e ) {
		echo "[$block_name] FATAL: " . $e->getMessage() . "\n\n";
		continue;
	}

	preg_match_all( '/<img[^>]+>/i', $html, $matches );

	echo "[$block_name]\n";
	foreach ( $matches[0] as $img ) {
		if ( preg_match( '/alt="([^"]*)"/', $img, $alt_match ) ) {
			$alt_value = $alt_match[1];
			$kept      = ( $alt_value === $attachment_alt ) ? 'KEPT attachment alt' : 'OVERRIDE';
			echo "  $kept alt='$alt_value'\n  $img\n";
		} else {
			echo "  NO alt attribute\n  $img\n";
		}
	}
	if ( empty( $matches[0] ) ) {
		echo "  NO <img> rendered (empty output)\n";
	}
	echo "\n";
}
