<?php
/**
 * Basalio: verification fixtures.
 *
 * Creates (or updates, it is idempotent) the pages that exercise the shipped
 * accessibility behaviour, plus the placeholder attachments they need.
 * Dev-only. Excluded from the distributed zip.
 *
 * The local PHP build has neither GD nor zlib, so images are written with a
 * minimal pure-PHP PNG encoder using uncompressed deflate blocks, and
 * attachment metadata is set by hand (with no image editor available,
 * wp_generate_attachment_metadata() cannot produce intermediate sizes).
 *
 * Run:  php tests/make-verify-pages.php [/path/to/wordpress]
 */

require __DIR__ . '/bootstrap.php';

const BV_MARKER = '_basalio_verify_fixture';
const BV_W      = 400;
const BV_H      = 300;

/** Wrap a PNG chunk with its length and CRC. */
function bv_chunk( $type, $data ) {
	return pack( 'N', strlen( $data ) ) . $type . $data . pack( 'N', crc32( $type . $data ) );
}

/** Adler-32, the checksum a zlib stream ends with. */
function bv_adler32( $data ) {
	$a   = 1;
	$b   = 0;
	$len = strlen( $data );
	for ( $i = 0; $i < $len; $i++ ) {
		$a = ( $a + ord( $data[ $i ] ) ) % 65521;
		$b = ( $b + $a ) % 65521;
	}
	return ( $b << 16 ) | $a;
}

/**
 * Wrap raw bytes in a valid zlib stream using STORED deflate blocks.
 *
 * PNG requires its IDAT payload to be a zlib stream, but deflate allows
 * uncompressed "stored" blocks (BTYPE=00), so a conformant stream needs no
 * compression library: a two-byte header, stored blocks capped at 65535 bytes
 * each, and an Adler-32 tail.
 */
function bv_zlib_stored( $raw ) {
	$out = "\x78\x01"; // CM=8, CINFO=7, FCHECK making the pair divisible by 31.
	$len = strlen( $raw );
	$pos = 0;
	do {
		$chunk = substr( $raw, $pos, 65535 );
		$clen  = strlen( $chunk );
		$pos  += $clen;
		$out  .= chr( $pos >= $len ? 1 : 0 );  // BFINAL, BTYPE=00 (stored).
		$out  .= pack( 'v', $clen );            // LEN, little-endian.
		$out  .= pack( 'v', ~$clen & 0xFFFF );  // NLEN, one's complement.
		$out  .= $chunk;
	} while ( $pos < $len );

	return $out . pack( 'N', bv_adler32( $raw ) );
}

/**
 * Encode a 2-colour palette PNG with a progress bar across the top, so Scroll
 * Sequence frames are visually distinguishable without any text rendering.
 */
function bv_png( $rgb, $progress = 0.0 ) {
	// Colour type 3 (palette), bit depth 8: one byte per pixel is an index.
	$ihdr = pack( 'NN', BV_W, BV_H ) . chr( 8 ) . chr( 3 ) . chr( 0 ) . chr( 0 ) . chr( 0 );

	$plte = chr( $rgb[0] ) . chr( $rgb[1] ) . chr( $rgb[2] )
		. chr( min( 255, $rgb[0] + 150 ) ) . chr( min( 255, $rgb[1] + 150 ) ) . chr( min( 255, $rgb[2] + 150 ) );

	$body_row = chr( 0 ) . str_repeat( chr( 0 ), BV_W );

	$bar_cols = (int) round( BV_W * max( 0, min( 1, $progress ) ) );
	$bar_row  = chr( 0 ) . str_repeat( chr( 1 ), $bar_cols ) . str_repeat( chr( 0 ), BV_W - $bar_cols );

	$bar_height = 45;
	$raw        = str_repeat( $bar_row, $bar_height ) . str_repeat( $body_row, BV_H - $bar_height );

	return "\x89PNG\r\n\x1a\n"
		. bv_chunk( 'IHDR', $ihdr )
		. bv_chunk( 'PLTE', $plte )
		. bv_chunk( 'IDAT', bv_zlib_stored( $raw ) )
		. bv_chunk( 'IEND', '' );
}

/** Write a fixture PNG and attach it. Idempotent via a marker meta key. */
function bv_image( $key, $rgb, $alt = '', $progress = 0.0 ) {
	$found = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => 1,
			'meta_key'    => BV_MARKER,
			'meta_value'  => $key,
			'fields'      => 'ids',
		)
	);
	if ( ! empty( $found ) ) {
		$id = (int) $found[0];
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		return $id;
	}

	$uploads = wp_upload_dir();
	$path    = trailingslashit( $uploads['path'] ) . 'basalio-verify-' . sanitize_title( $key ) . '.png';

	file_put_contents( $path, bv_png( $rgb, $progress ) );

	if ( ! file_exists( $path ) || filesize( $path ) < 100 ) {
		fwrite( STDERR, "Failed to write fixture image: $path\n" );
		exit( 1 );
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $key,
			'post_status'    => 'inherit',
		),
		$path
	);

	wp_update_attachment_metadata(
		$id,
		array(
			'width'  => BV_W,
			'height' => BV_H,
			'file'   => ltrim( str_replace( trailingslashit( $uploads['basedir'] ), '', $path ), '/' ),
			'sizes'  => array(),
		)
	);

	update_post_meta( $id, BV_MARKER, $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );

	return (int) $id;
}

/** Create or update a page by slug. */
function bv_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		wp_update_post(
			array(
				'ID'           => $existing->ID,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);
		return (int) $existing->ID;
	}
	return (int) wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}

// ---------------------------------------------------------------------------

$img_pinned = bv_image( 'pinned', array( 20, 20, 24 ), 'A pinned hero image' );

// Frame 3's ATTACHMENT alt deliberately differs from the block-level override
// set below: exactly the condition the alt-override fix addresses.
$seq = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$seq[ $i ] = bv_image( 'seq-' . $i, array( 24, 40, 90 ), 'ATTACHMENT alt for frame ' . $i, $i / 6 );
}

// `grid-blank` has NO alt and is the item given a link: the empty-link case.
$grid_blank  = bv_image( 'grid-blank', array( 120, 30, 30 ), '' );
$grid_design = bv_image( 'grid-design', array( 30, 90, 70 ), 'A design project' );
$grid_brand  = bv_image( 'grid-brand', array( 70, 60, 120 ), 'A branding project' );
$grid_nocat  = bv_image( 'grid-nocat', array( 90, 90, 40 ), 'An uncategorised project' );

$story = '';
for ( $i = 1; $i <= 8; $i++ ) {
	$story .= '<!-- wp:paragraph --><p>Story paragraph ' . $i .
		'. Scroll past the pinned image, then click it to confirm the lightbox opens.</p><!-- /wp:paragraph -->' . "\n";
}

$page_pinned = bv_page(
	'verify-pinned-lightbox',
	'Verify: Pinned Scroll lightbox',
	'<!-- wp:basalio/pinned-scroll {"pinnedImageId":' . $img_pinned .
		',"pinnedImageAlt":"A pinned hero image","lightbox":true,"mediaPosition":"left"} -->' . "\n" .
		$story . '<!-- /wp:basalio/pinned-scroll -->'
);

$page_pinned_right = bv_page(
	'verify-pinned-right',
	'Verify: Pinned Scroll media-right DOM order (open issue 7.6)',
	'<!-- wp:basalio/pinned-scroll {"pinnedImageId":' . $img_pinned .
		',"pinnedImageAlt":"A pinned hero image","lightbox":true,"mediaPosition":"right"} -->' . "\n" .
		$story . '<!-- /wp:basalio/pinned-scroll -->'
);

$frames = array();
foreach ( $seq as $n => $id ) {
	$frames[] = array(
		'id'  => $id,
		'url' => wp_get_attachment_image_url( $id, 'full' ),
		'alt' => ( 3 === $n ) ? 'OVERRIDE alt for frame 3' : 'ATTACHMENT alt for frame ' . $n,
	);
}

$page_seq = bv_page(
	'verify-scroll-sequence',
	'Verify: Scroll Sequence',
	'<!-- wp:paragraph --><p>Scroll into the sequence with a screen reader running.</p><!-- /wp:paragraph -->' . "\n" .
	'<!-- wp:basalio/scroll-sequence ' . wp_json_encode(
		array(
			'frames'        => $frames,
			'scrollScreens' => 4,
			'showControls'  => true,
		)
	) . ' /-->' . "\n" .
	'<!-- wp:paragraph --><p>End of sequence.</p><!-- /wp:paragraph -->'
);

$items =
	'<!-- wp:basalio/grid-item ' . wp_json_encode(
		array(
			'imageId'  => $grid_blank,
			'imageAlt' => '',
			'title'    => 'Untitled Case Study',
			'category' => 'Design',
			'url'      => '#linked-with-no-alt',
		)
	) . ' /-->' . "\n" .
	'<!-- wp:basalio/grid-item ' . wp_json_encode(
		array(
			'imageId'  => $grid_design,
			'imageAlt' => 'A design project',
			'title'    => 'Design Project',
			'category' => 'Design',
			'url'      => '#design',
		)
	) . ' /-->' . "\n" .
	'<!-- wp:basalio/grid-item ' . wp_json_encode(
		array(
			'imageId'  => $grid_brand,
			'imageAlt' => 'A branding project',
			'title'    => 'Brand Project',
			'category' => 'Branding',
		)
	) . ' /-->' . "\n" .
	'<!-- wp:basalio/grid-item ' . wp_json_encode(
		array(
			'imageId'  => $grid_nocat,
			'imageAlt' => 'An uncategorised project',
			'title'    => 'Uncategorised Project',
		)
	) . ' /-->';

$page_grid = bv_page(
	'verify-filterable-grid',
	'Verify: Filterable Grid',
	'<!-- wp:basalio/filterable-grid {"columns":2} -->' . "\n" . $items . "\n" . '<!-- /wp:basalio/filterable-grid -->'
);

$page_empty_alt = bv_page(
	'verify-empty-alt-override',
	'Verify: empty author alt still overrides attachment alt',
	'<!-- wp:basalio/grid-item ' . wp_json_encode(
		array(
			'imageId'  => $grid_design,
			'imageAlt' => '',
			'title'    => '',
			'category' => '',
			'url'      => '#empty-alt-override',
		)
	) . ' /-->'
);

$base = home_url();
echo "\nFixtures ready.\n\n";
printf( "1. Pinned Scroll lightbox        %s/?page_id=%d\n", $base, $page_pinned );
printf( "2. Pinned Scroll media-right     %s/?page_id=%d\n", $base, $page_pinned_right );
printf( "3. Scroll Sequence               %s/?page_id=%d\n", $base, $page_seq );
printf( "4. Filterable Grid               %s/?page_id=%d\n", $base, $page_grid );
echo "\n";
