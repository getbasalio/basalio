<?php
/**
 * Basalio: automated accessibility assertions.
 *
 * Renders the fixture pages through do_blocks() and asserts on real output.
 * Covers everything provable without a browser. Run this after any change to a
 * module's render.php, style.css, block.json, or basalio-core.js.
 *
 * Prerequisite: php tests/make-verify-pages.php
 * Run:          php tests/verify-fixes.php [/path/to/wordpress]
 * Exit:         0 if every assertion passes, 1 otherwise.
 */

require __DIR__ . '/bootstrap.php';

$GLOBALS['bv_pass'] = 0;
$GLOBALS['bv_fail'] = 0;

function bv_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bv_pass']++;
		echo "  PASS  $label\n";
	} else {
		$GLOBALS['bv_fail']++;
		echo "  FAIL  $label\n";
		if ( '' !== $detail ) {
			echo "        $detail\n";
		}
	}
}

/** Render a fixture page's blocks, with the script queue reset first. */
function bv_render( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		echo "  FAIL  fixture page missing: $slug (run tests/make-verify-pages.php)\n";
		$GLOBALS['bv_fail']++;
		return '';
	}
	wp_dequeue_script( 'basalio-core' );
	wp_dequeue_style( 'basalio-core' );

	global $post;
	$post = $page;
	setup_postdata( $post );
	$html = do_blocks( $page->post_content );
	wp_reset_postdata();

	return $html;
}

/** Read a file from the plugin root. */
function bv_src( $rel ) {
	return (string) file_get_contents( BASALIO_TESTS_PLUGIN_DIR . '/' . ltrim( $rel, '/' ) );
}

echo "\n=== Lightbox trigger enqueues the core (Pinned Scroll alone) ===\n";

$html = bv_render( 'verify-pinned-lightbox' );
bv_ok( 'lightbox trigger button is rendered', false !== strpos( $html, 'data-basalio-lightbox' ) );
bv_ok(
	'basalio-core SCRIPT is enqueued by the lightbox path',
	wp_script_is( 'basalio-core', 'enqueued' ),
	'If false, the button announces "Expand image" and does nothing (4.1.2).'
);
bv_ok(
	'basalio-core STYLE is enqueued by the lightbox path',
	wp_style_is( 'basalio-core', 'enqueued' )
);

echo "\n=== Scroll Sequence: per-frame alt override (1.1.1) ===\n";

$seq = bv_render( 'verify-scroll-sequence' );
bv_ok( 'frame 3 uses the block-level OVERRIDE alt', false !== strpos( $seq, 'alt="OVERRIDE alt for frame 3"' ) );
bv_ok( 'frame 3 does not fall back to the attachment alt', false === strpos( $seq, 'alt="ATTACHMENT alt for frame 3"' ) );
bv_ok( 'other frames still render their own alt', false !== strpos( $seq, 'alt="ATTACHMENT alt for frame 5"' ) );

echo "\n=== Scroll Sequence: live region separated from scroll counter (4.1.3) ===\n";

bv_ok( 'a dedicated announcer element exists', false !== strpos( $seq, 'data-basalio-seq-announcer' ) );
bv_ok(
	'the announcer carries role="status"',
	(bool) preg_match( '/class="basalio-scroll-sequence__announcer" role="status"/', $seq )
);
bv_ok(
	'the announcer starts EMPTY so the first press is announced',
	(bool) preg_match( '/data-basalio-seq-announcer><\/span>/', $seq )
);
bv_ok(
	'the visible counter no longer carries live semantics',
	! preg_match( '/class="basalio-scroll-sequence__status"[^>]*role="status"/', $seq ),
	'If this fails, scrolling spams the screen reader again.'
);

$core_js = bv_src( 'assets/js/basalio-core.js' );
bv_ok(
	'JS announces only when explicitly asked',
	false !== strpos( $core_js, 'show( current - 1, true )' ) && false !== strpos( $core_js, 'show( current + 1, true )' )
);
bv_ok(
	'the scroll driver calls show() WITHOUT the announce flag',
	(bool) preg_match( '/show\(\s*Math\.round\(\s*progress\s*\*\s*\(\s*total\s*-\s*1\s*\)\s*\)\s*\)/', $core_js ),
	'A second argument here would reintroduce the announcement spam.'
);

echo "\n=== Scroll Sequence: inactive frames leave the a11y tree (1.3.1) ===\n";

$seq_css = bv_src( 'modules/scroll-sequence/style.css' );
bv_ok( 'inactive frames set visibility: hidden', (bool) preg_match( '/visibility:\s*hidden/', $seq_css ) );
bv_ok( 'active frame restores visibility: visible', (bool) preg_match( '/is-active\s*\{[^}]*visibility:\s*visible/s', $seq_css ) );
bv_ok( 'announcer has a screen-reader-only rule', false !== strpos( $seq_css, '__announcer' ) );

echo "\n=== Grid Item: linked item with no alt gets a name (2.4.4 / 4.1.2) ===\n";

$grid = bv_render( 'verify-filterable-grid' );
bv_ok(
	'the alt-less linked item carries an aria-label fallback',
	(bool) preg_match( '/<a class="basalio-grid-item__frame"[^>]*aria-label="Untitled Case Study"/', $grid )
);
bv_ok(
	'items that DO have alt are left alone',
	! preg_match( '/aria-label="Design Project"/', $grid ),
	'The fallback must not override a good alt-derived name.'
);

echo "\n=== i18n: no unreachable defaults, core script wired ===\n";

// block.json defaults are static JSON with no gettext, so any user-facing
// string default there makes the PHP __() fallback unreachable and the string
// permanently English. These two are the user-facing ones.
$fg_json  = json_decode( bv_src( 'modules/filterable-grid/block.json' ), true );
$seq_json = json_decode( bv_src( 'modules/scroll-sequence/block.json' ), true );

bv_ok(
	'filterable-grid allLabel default is empty (so __() applies)',
	'' === $fg_json['attributes']['allLabel']['default']
);
bv_ok(
	'scroll-sequence paginationFormat default is empty (so __() applies)',
	'' === $seq_json['attributes']['paginationFormat']['default']
);
bv_ok( 'grid renders the translated All label', false !== strpos( $grid, '>All</button>' ) );
bv_ok( 'sequence renders the translated counter', false !== strpos( $seq, 'Frame 1 of 6' ) );

$reg = wp_scripts()->registered;
bv_ok(
	'basalio-core declares wp-i18n as a dependency',
	isset( $reg['basalio-core'] ) && in_array( 'wp-i18n', (array) $reg['basalio-core']->deps, true )
);
bv_ok(
	'basalio-core has translations wired',
	isset( $reg['basalio-core'] ) && ! empty( $reg['basalio-core']->textdomain )
);
bv_ok( 'server-rendered grid count is translated via _n()', false !== strpos( $grid, 'Showing 4 projects.' ) );
bv_ok(
	'JS no longer hardcodes the English count string',
	false === strpos( $core_js, "'Showing ' + shown" )
);
bv_ok(
	'JS no longer hardcodes the lightbox dialog labels',
	false === strpos( $core_js, "'aria-label', 'Image viewer'" )
);

echo "\n=== Case Study Transition: bounded morph duration ===\n";

$cst_json = json_decode( bv_src( 'modules/case-study-transition/block.json' ), true );
$cst_php  = bv_src( 'modules/case-study-transition/render.php' );
$cst_js   = bv_src( 'modules/case-study-transition/index.js' );

bv_ok( 'attribute default is 300ms', 300 === $cst_json['attributes']['transitionDuration']['default'] );
bv_ok(
	'server clamps to 150-600ms',
	(bool) preg_match( '/max\(\s*150,\s*min\(\s*600,/', $cst_php ),
	'A wider range lets a large scaling morph run long enough to be uncomfortable.'
);
bv_ok( 'server default matches the attribute default', (bool) preg_match( '/\)\s*\)\s*:\s*300;/', $cst_php ) );
bv_ok( 'scoped duration style is skipped at the 300ms default', false !== strpos( $cst_php, '300 !== $basalio_dur' ) );
bv_ok( 'editor range matches the server clamp', false !== strpos( $cst_js, 'min: 150' ) && false !== strpos( $cst_js, 'max: 600' ) );
bv_ok( 'editor warns above 400ms', (bool) preg_match( '/duration\s*>\s*400/', $cst_js ) );

echo "\n=== Release consistency ===\n";

// Each block.json's "version" is what register_block_type_from_metadata() uses to
// cache-bust THAT block's own style.css and index.js. A stale value here means
// browsers keep serving the old CSS after a fix, which is invisible in testing
// and very visible to users.
$plugin_php  = bv_src( 'basalio.php' );
preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $plugin_php, $m );
$plugin_ver = isset( $m[1] ) ? $m[1] : '';

bv_ok( 'plugin header declares a version', '' !== $plugin_ver );
bv_ok(
	'BASALIO_VERSION matches the plugin header',
	false !== strpos( $plugin_php, "define( 'BASALIO_VERSION', '" . $plugin_ver . "' )" )
);
bv_ok(
	'readme.txt stable tag matches',
	false !== strpos( bv_src( 'readme.txt' ), 'Stable tag: ' . $plugin_ver )
);
bv_ok( 'no placeholder URLs in the plugin header', false === strpos( $plugin_php, 'example.com' ) );

$stale = array();
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/block.json' ) as $bj ) {
	$data = json_decode( (string) file_get_contents( $bj ), true );
	if ( ! isset( $data['version'] ) || $data['version'] !== $plugin_ver ) {
		$stale[] = basename( dirname( $bj ) ) . ' (' . ( isset( $data['version'] ) ? $data['version'] : 'missing' ) . ')';
	}
}
bv_ok(
	'every block.json version matches the plugin version',
	empty( $stale ),
	empty( $stale ) ? '' : 'Stale, so their CSS/JS will be served from cache: ' . implode( ', ', $stale )
);

echo "\n=== Known caveat 8.11 (behaviour to confirm, not a fix) ===\n";

bv_ok(
	'the uncategorised item renders an empty category slug',
	false !== strpos( $grid, 'data-basalio-item-category=""' ),
	'It appears under All and vanishes under every other filter.'
);

$total = $GLOBALS['bv_pass'] + $GLOBALS['bv_fail'];
printf( "\n%d/%d assertions passed.\n", $GLOBALS['bv_pass'], $total );

echo "\nStill requires a browser (JS runtime, not assertable here):\n";
echo "  - Scroll with a screen reader: silence. Press Next: exactly one announcement.\n";
echo "  - Accessibility tree: one image in the sequence, not six.\n";
echo "  - Click the pinned image: the lightbox actually opens.\n";
echo "  - Translated JS strings need make-json plus a test locale.\n\n";

exit( $GLOBALS['bv_fail'] > 0 ? 1 : 0 );
