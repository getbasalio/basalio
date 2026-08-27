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
 * Exit:         0 if no real failures, 1 if any assertion failed.
 *               Missing fixtures are skipped, not failed.
 */

require __DIR__ . '/bootstrap.php';

$GLOBALS['bv_pass']           = 0;
$GLOBALS['bv_fail']           = 0;
$GLOBALS['bv_skip']           = 0;
$GLOBALS['bv_missing_fixtures'] = array();

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

function bv_skip( $label, $reason = '' ) {
	$GLOBALS['bv_skip']++;
	echo "  SKIP  $label";
	if ( '' !== $reason ) {
		echo " ($reason)";
	}
	echo "\n";
}

function bv_skip_fixture( $label, $reason = '' ) {
	bv_skip( $label, $reason );
}

/**
 * Render a fixture page's blocks, with the script queue reset first.
 *
 * @param string $slug Page slug.
 * @return string|null Markup, or null if the fixture page does not exist.
 */
function bv_render( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		echo "  SKIP  fixture page missing: $slug (run tests/make-verify-pages.php)\n";
		$GLOBALS['bv_missing_fixtures'][] = $slug;
		return null;
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
if ( null === $html ) {
	bv_skip_fixture( 'lightbox trigger button is rendered', 'fixture page missing: verify-pinned-lightbox' );
	bv_skip_fixture( 'basalio-core SCRIPT is enqueued by the lightbox path', 'fixture page missing: verify-pinned-lightbox' );
	bv_skip_fixture( 'basalio-core STYLE is enqueued by the lightbox path', 'fixture page missing: verify-pinned-lightbox' );
} else {
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
}

echo "\n=== Scroll Sequence: per-frame alt override (1.1.1) ===\n";

$seq = bv_render( 'verify-scroll-sequence' );
if ( null === $seq ) {
	bv_skip_fixture( 'frame 3 uses the block-level OVERRIDE alt', 'fixture page missing: verify-scroll-sequence' );
	bv_skip_fixture( 'frame 3 does not fall back to the attachment alt', 'fixture page missing: verify-scroll-sequence' );
	bv_skip_fixture( 'other frames still render their own alt', 'fixture page missing: verify-scroll-sequence' );
} else {
	bv_ok( 'frame 3 uses the block-level OVERRIDE alt', false !== strpos( $seq, 'alt="OVERRIDE alt for frame 3"' ) );
	bv_ok( 'frame 3 does not fall back to the attachment alt', false === strpos( $seq, 'alt="ATTACHMENT alt for frame 3"' ) );
	bv_ok( 'other frames still render their own alt', false !== strpos( $seq, 'alt="ATTACHMENT alt for frame 5"' ) );
}

echo "\n=== Scroll Sequence: live region separated from scroll counter (4.1.3) ===\n";

if ( null === $seq ) {
	bv_skip_fixture( 'a dedicated announcer element exists', 'fixture page missing: verify-scroll-sequence' );
	bv_skip_fixture( 'the announcer carries role="status"', 'fixture page missing: verify-scroll-sequence' );
	bv_skip_fixture( 'the announcer starts EMPTY so the first press is announced', 'fixture page missing: verify-scroll-sequence' );
	bv_skip_fixture( 'the visible counter no longer carries live semantics', 'fixture page missing: verify-scroll-sequence' );
} else {
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
}

$core_js = bv_src( 'assets/js/basalio-core.js' );
bv_ok(
	'JS announces only when explicitly asked',
	(bool) preg_match( '/show\(\s*target\s*,\s*true\s*\)/', $core_js ),
	'Button clicks must pass the announce flag; the scroll driver must not.'
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
if ( null === $grid ) {
	bv_skip_fixture( 'the alt-less linked item carries an aria-label fallback', 'fixture page missing: verify-filterable-grid' );
	bv_skip_fixture( 'items that DO have alt are left alone', 'fixture page missing: verify-filterable-grid' );
} else {
	bv_ok(
		'the alt-less linked item carries an aria-label fallback',
		(bool) preg_match( '/<a class="basalio-grid-item__frame"[^>]*aria-label="Untitled Case Study"/', $grid )
	);
	bv_ok(
		'items that DO have alt are left alone',
		! preg_match( '/aria-label="Design Project"/', $grid ),
		'The fallback must not override a good alt-derived name.'
	);
}

echo "\n=== M1/M2: robust image alt handling across all image blocks ===\n";

$empty_alt = bv_render( 'verify-empty-alt-override' );
if ( null === $empty_alt ) {
	bv_skip_fixture( 'empty author alt is honoured on the image (decorative)', 'fixture page missing: verify-empty-alt-override' );
	bv_skip_fixture( 'empty author alt falls back to attachment alt on the surrounding link', 'fixture page missing: verify-empty-alt-override' );
} else {
	bv_ok(
		'empty author alt is honoured on the image (decorative)',
		(bool) preg_match( '/<img[^>]*class="basalio-grid-item__image"[^>]*alt=""/', $empty_alt ),
		'The attachment alt must not leak into a deliberately blank author alt.'
	);
	bv_ok(
		'empty author alt falls back to attachment alt on the surrounding link',
		(bool) preg_match( '/<a class="basalio-grid-item__frame"[^>]*aria-label="A design project"/', $empty_alt ),
		'The link still needs an accessible name when the image is decorative.'
	);
}

echo "\n=== Alt fallback for images outside named controls ===\n";

$cst_hero_alt = bv_render( 'verify-cst-hero-alt-fallback' );
bv_ok(
	'case-study-transition hero falls back to attachment alt',
	null !== $cst_hero_alt && false !== strpos( $cst_hero_alt, 'alt="A pinned hero image"' ),
	null === $cst_hero_alt ? 'fixture page missing: verify-cst-hero-alt-fallback' : 'Rendered: ' . substr( $cst_hero_alt, 0, 200 )
);

$grid_reveal_alt = bv_render( 'verify-grid-reveal-alt-fallback' );
bv_ok(
	'grid-reveal falls back to attachment alt',
	null !== $grid_reveal_alt && false !== strpos( $grid_reveal_alt, 'alt="A pinned hero image"' ),
	null === $grid_reveal_alt ? 'fixture page missing: verify-grid-reveal-alt-fallback' : 'Rendered: ' . substr( $grid_reveal_alt, 0, 200 )
);

$before_after_alt = bv_render( 'verify-before-after-alt-fallback' );
bv_ok(
	'before-after before image falls back to attachment alt',
	null !== $before_after_alt && false !== strpos( $before_after_alt, 'class="basalio-before-after__img basalio-before-after__img--before"' ) && false !== strpos( $before_after_alt, 'alt="A pinned hero image"' ),
	null === $before_after_alt ? 'fixture page missing: verify-before-after-alt-fallback' : 'Rendered: ' . substr( $before_after_alt, 0, 200 )
);
bv_ok(
	'before-after after image falls back to attachment alt',
	null !== $before_after_alt && false !== strpos( $before_after_alt, 'class="basalio-before-after__img basalio-before-after__img--after"' ) && false !== strpos( $before_after_alt, 'alt="A design project"' ),
	null === $before_after_alt ? 'fixture page missing: verify-before-after-alt-fallback' : 'Rendered: ' . substr( $before_after_alt, 0, 200 )
);

$seq_alt = bv_render( 'verify-scroll-sequence-alt-fallback' );
bv_ok(
	'scroll-sequence frame falls back to attachment alt',
	null !== $seq_alt && false !== strpos( $seq_alt, 'alt="ATTACHMENT alt for frame 1"' ),
	null === $seq_alt ? 'fixture page missing: verify-scroll-sequence-alt-fallback' : 'Rendered: ' . substr( $seq_alt, 0, 200 )
);

$pinned_link_empty = bv_render( 'verify-pinned-scroll-link-empty-alt' );
bv_ok(
	'pinned-scroll link adds aria-label when image alt is empty',
	null !== $pinned_link_empty && (bool) preg_match( '/<a class="basalio-media-link"[^>]*aria-label="Open link"/', $pinned_link_empty ),
	null === $pinned_link_empty ? 'fixture page missing: verify-pinned-scroll-link-empty-alt' : 'Rendered: ' . substr( $pinned_link_empty, 0, 200 )
);

$pinned_link_with_alt = bv_render( 'verify-pinned-scroll-link-with-alt' );
bv_ok(
	'pinned-scroll link keeps image alt and does not add overriding aria-label',
	null !== $pinned_link_with_alt
		&& false !== strpos( $pinned_link_with_alt, 'alt="Custom pinned alt"' )
		&& ! preg_match( '/<a class="basalio-media-link"[^>]*aria-label/', $pinned_link_with_alt ),
	null === $pinned_link_with_alt ? 'fixture page missing: verify-pinned-scroll-link-with-alt' : 'Rendered: ' . substr( $pinned_link_with_alt, 0, 200 )
);

$pinned_draft_link = bv_render( 'verify-pinned-scroll-draft-link' );
bv_ok(
	'pinned-scroll link to draft does not expose the draft title',
	null !== $pinned_draft_link
		&& false === strpos( $pinned_draft_link, 'SECRET DRAFT TITLE' )
		&& (bool) preg_match( '/<a class="basalio-media-link"[^>]*aria-label="Open link"/', $pinned_draft_link ),
	null === $pinned_draft_link ? 'fixture page missing: verify-pinned-scroll-draft-link' : 'Rendered: ' . substr( $pinned_draft_link, 0, 200 )
);

$grid_item_plain = bv_render( 'verify-grid-item-plain-alt-fallback' );
bv_ok(
	'plain grid-item falls back to title/category alt',
	null !== $grid_item_plain && false !== strpos( $grid_item_plain, 'alt="Plain item title"' ),
	null === $grid_item_plain ? 'fixture page missing: verify-grid-item-plain-alt-fallback' : 'Rendered: ' . substr( $grid_item_plain, 0, 200 )
);

$render_files = glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/render.php' );
$regex_alt_override = false;
foreach ( $render_files as $rf ) {
	if ( preg_match( '/preg_replace\(\s*[\'"]\/alt="\[^"\]"\/|preg_replace\(\s*[\'"]\/alt="[^"]*"\//', file_get_contents( $rf ) ) ) {
		$regex_alt_override = true;
		break;
	}
}
bv_ok(
	'no render file uses regex alt replacement',
	! $regex_alt_override,
	'Use basalio_get_image_html() so author alt always wins, even when empty.'
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
if ( null === $grid ) {
	bv_skip_fixture( 'grid renders the translated All label', 'fixture page missing: verify-filterable-grid' );
} else {
	bv_ok( 'grid renders the translated All label', false !== strpos( $grid, '>All</button>' ) );
}
if ( null === $seq ) {
	bv_skip_fixture( 'sequence renders the translated counter', 'fixture page missing: verify-scroll-sequence' );
} else {
	bv_ok( 'sequence renders the translated counter', false !== strpos( $seq, 'Frame 1 of 6' ) );
}

$reg = wp_scripts()->registered;
bv_ok(
	'basalio-core declares wp-i18n as a dependency',
	isset( $reg['basalio-core'] ) && in_array( 'wp-i18n', (array) $reg['basalio-core']->deps, true )
);
bv_ok(
	'basalio-core has translations wired',
	isset( $reg['basalio-core'] ) && ! empty( $reg['basalio-core']->textdomain )
);
if ( null === $grid ) {
	bv_skip_fixture( 'server-rendered grid count is translated via _n()', 'fixture page missing: verify-filterable-grid' );
} else {
	bv_ok( 'server-rendered grid count is translated via _n()', false !== strpos( $grid, 'Showing 4 projects.' ) );
}
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

$stale_assets = array();
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/index.asset.php' ) as $asset ) {
	$data = require $asset;
	if ( ! isset( $data['version'] ) || $data['version'] !== $plugin_ver ) {
		$stale_assets[] = basename( dirname( $asset ) ) . ' (' . ( isset( $data['version'] ) ? $data['version'] : 'missing' ) . ')';
	}
}
bv_ok(
	'every index.asset.php version matches the plugin version',
	empty( $stale_assets ),
	empty( $stale_assets ) ? '' : 'Stale editor assets will be cached by WordPress: ' . implode( ', ', $stale_assets )
);

echo "\n=== Filterable Grid: uncategorised items are reachable (M8) ===\n";

if ( null === $grid ) {
	bv_skip_fixture( 'uncategorised items use the "uncategorized" slug', 'fixture page missing: verify-filterable-grid' );
	bv_skip_fixture( 'an Uncategorized filter button is rendered', 'fixture page missing: verify-filterable-grid' );
} else {
	bv_ok(
		'uncategorised items use the "uncategorized" slug',
		false !== strpos( $grid, 'data-basalio-item-category="uncategorized"' ),
		'An empty slug made them disappear under every category filter.'
	);
	bv_ok(
		'an Uncategorized filter button is rendered',
		(bool) preg_match( '/<button[^>]*data-basalio-filter="uncategorized"[^>]*>Uncategorized</', $grid ),
		'Uncategorised items must have a filter of their own.'
	);
}

echo "\n=== Shared contrast class ===\n";

bv_ok( 'Basalio_Contrast class exists', class_exists( 'Basalio_Contrast' ) );

$white = Basalio_Contrast::hex_to_rgb( '#ffffff' );
$black = Basalio_Contrast::hex_to_rgb( '#000000' );
bv_ok(
	'white-on-black contrast is ~21:1',
	abs( Basalio_Contrast::contrast_ratio( $white, $black ) - 21.0 ) < 0.1,
	'got: ' . Basalio_Contrast::contrast_ratio( $white, $black )
);

$amber9 = Basalio_Contrast::hex_to_rgb( '#f5a623' );
bv_ok(
	'amber-9 chooses black foreground',
	'#000000' === Basalio_Contrast::choose_foreground( '#f5a623' ),
	'got: ' . Basalio_Contrast::choose_foreground( '#f5a623' )
);

echo "\n=== Audit hook ===\n";

add_filter(
	'basalio_audit_checks',
	function( $checks ) {
		$checks['test'] = array( 'label' => 'Test check' );
		return $checks;
	}
);

$audit = basalio_get_audit_checks();
bv_ok( 'basalio_audit_checks filter is applied', isset( $audit['test'] ) && 'Test check' === $audit['test']['label'] );

echo "\n=== Block extension hooks ===\n";

add_filter(
	'basalio_block_supports',
	function( $supports, $module_name ) {
		if ( 'text-reveal' === $module_name ) {
			$supports['__basalio_test_support'] = true;
		}
		return $supports;
	},
	10,
	2
);

add_filter(
	'basalio_block_schema',
	function( $schema, $module_name ) {
		if ( 'text-reveal' === $module_name ) {
			$schema['__basalio_test_schema'] = 1;
		}
		return $schema;
	},
	10,
	2
);

add_filter(
	'basalio_block_variations',
	function( $variations, $module_name ) {
		if ( 'text-reveal' === $module_name ) {
			$variations[] = array(
				'name'  => 'test-variation',
				'title' => 'Test Variation',
			);
		}
		return $variations;
	},
	10,
	2
);

$registry = WP_Block_Type_Registry::get_instance();
foreach ( $registry->get_all_registered() as $name => $block ) {
	if ( 0 === strpos( $name, 'basalio/' ) ) {
		$registry->unregister( $name );
	}
}
Basalio::instance()->register_modules();

$text_reveal = $registry->get_registered( 'basalio/text-reveal' );
bv_ok(
	'basalio_block_supports filter is applied',
	isset( $text_reveal->supports['__basalio_test_support'] ) && true === $text_reveal->supports['__basalio_test_support']
);

$schema = Basalio::get_block_schema( 'basalio/text-reveal' );
bv_ok(
	'basalio_block_schema filter is stored and applied',
	isset( $schema['__basalio_test_schema'] ) && 1 === $schema['__basalio_test_schema']
);

$variations = $text_reveal->variations;
$found_variation = false;
if ( is_array( $variations ) ) {
	foreach ( $variations as $v ) {
		if ( isset( $v['name'] ) && 'test-variation' === $v['name'] ) {
			$found_variation = true;
			break;
		}
	}
}
bv_ok(
	'basalio_block_variations filter is applied',
	$found_variation,
	'variations: ' . wp_json_encode( $variations )
);

echo "\n=== Palette-class defect: core CSS loads for pattern-only content ===\n";

// Render a page that contains only core blocks colored with Basalio palette
// classes (no basalio/ block). The shared core stylesheet must still enqueue
// so the palette classes resolve when the Pro add-on is inactive.
$wp_enqueue_scripts_ran = did_action( 'wp_enqueue_scripts' );
$page = get_page_by_path( 'browser-palette-check-hero-01' );
if ( $page ) {
	global $post;
	$post = $page;
	setup_postdata( $post );

	wp_dequeue_style( 'basalio-core' );
	wp_dequeue_script( 'basalio-core' );
	$rendered = do_blocks( $post->post_content );

	// Simulate the front-end enqueue hook firing after the main query is set.
	if ( ! $wp_enqueue_scripts_ran ) {
		do_action( 'wp_enqueue_scripts' );
	}

	bv_ok(
		'fixture page contains Basalio palette classes',
		false !== strpos( $rendered, 'has-basalio-' )
	);
	bv_ok(
		'basalio-core STYLE enqueued for palette-only content',
		wp_style_is( 'basalio-core', 'enqueued' ),
		'If false, expanded patterns render with no Basalio palette.'
	);

	wp_reset_postdata();
} else {
	echo "  SKIP  fixture page missing: browser-palette-check-hero-01 (run tests/make-verify-pages.php)\n";
	$GLOBALS['bv_missing_fixtures'][] = 'browser-palette-check-hero-01';
	bv_skip_fixture( 'fixture page contains Basalio palette classes', 'fixture page missing: browser-palette-check-hero-01' );
	bv_skip_fixture( 'basalio-core STYLE enqueued for palette-only content', 'fixture page missing: browser-palette-check-hero-01' );
}

echo "\n=== Design-token readiness: no bare hex colors in CSS ===\n";

/**
 * Return true if a stylesheet contains a hex color outside a var() fallback.
 *
 * Ignores CSS comments. A hex inside var(--x, #fff) or nested var() is allowed.
 *
 * @param string $css Stylesheet content.
 * @return bool
 */
function bv_css_has_bare_hex( $css ) {
	$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
	// Token definitions (--basalio-*: #hex) are the source of the variables, not
	// bare usages. Strip them before checking component property values.
	$css = preg_replace( '/--basalio-[-a-z0-9]+\s*:\s*#[0-9a-fA-F]{3,6}\b;?/', '', $css );
	if ( ! preg_match_all( '/#[0-9a-fA-F]{3,6}\b/', $css, $matches, PREG_OFFSET_CAPTURE ) ) {
		return false;
	}
	foreach ( $matches[0] as $match ) {
		$before  = substr( $css, 0, $match[1] );
		$last_var = strrpos( $before, 'var(' );
		if ( false === $last_var ) {
			return true;
		}
		$between = substr( $before, $last_var + 4 );
		// If a ')' or ';' appears between the opening var( and the hex, the hex
		// is not inside that var() fallback.
		if ( false !== strpos( $between, ')' ) || false !== strpos( $between, ';' ) ) {
			return true;
		}
	}
	return false;
}

$css_files = array_merge(
	glob( BASALIO_TESTS_PLUGIN_DIR . '/assets/css/*.css' ) ?: array(),
	glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/style.css' ) ?: array()
);

foreach ( $css_files as $css_file ) {
	$rel  = str_replace( BASALIO_TESTS_PLUGIN_DIR . '/', '', $css_file );
	$css  = (string) file_get_contents( $css_file );
	bv_ok(
		"no bare hex in {$rel}",
		! bv_css_has_bare_hex( $css ),
		'All colors must flow through var(--basalio-*, <hex>) so Pro can override them.'
	);
}

echo "\n=== Focus-visible rings: colour contrast against adjacent surfaces ===\n";

$bv_focus_bad     = array();
$bv_focus_files   = array_merge(
	array( BASALIO_TESTS_PLUGIN_DIR . '/assets/css/basalio-core.css' ),
	glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/style.css' ) ?: array()
);
$bv_core_css      = (string) file_get_contents( BASALIO_TESTS_PLUGIN_DIR . '/assets/css/basalio-core.css' );
$bv_root_tokens   = array();
if ( preg_match_all( '/--basalio-([a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{3,6})/', $bv_core_css, $bv_root_matches, PREG_SET_ORDER ) ) {
	foreach ( $bv_root_matches as $bv_rm ) {
		$bv_root_tokens[ $bv_rm[1] ] = strtolower( $bv_rm[2] );
	}
}

$bv_surface_white = Basalio_Contrast::hex_to_rgb( '#ffffff' );
$bv_backdrop      = Basalio_Contrast::hex_to_rgb( '#1a1a1a' ); // rgba(0,0,0,0.9) over white.
$bv_acid          = Basalio_Contrast::hex_to_rgb( '#dfff00' ); // Default close-button background.

foreach ( $bv_focus_files as $bv_focus_file ) {
	$bv_css  = (string) file_get_contents( $bv_focus_file );
	$bv_rel  = str_replace( BASALIO_TESTS_PLUGIN_DIR . '/', '', $bv_focus_file );

	if ( ! preg_match_all( '/([^{}]+):focus-visible\s*\{([^}]+)\}/', $bv_css, $bv_rules, PREG_SET_ORDER ) ) {
		continue;
	}

	foreach ( $bv_rules as $bv_rule ) {
		$bv_selector = trim( $bv_rule[1] );
		$bv_body     = $bv_rule[2];

		// Find every colour used in outline/box-shadow declarations.
		$bv_colours = array();

		// var(--token, #hex) fallback.
		if ( preg_match_all( '/var\(\s*--basalio-([a-z0-9-]+)\s*,\s*(#[0-9a-fA-F]{3,6})\s*\)/', $bv_body, $bv_var_matches, PREG_SET_ORDER ) ) {
			foreach ( $bv_var_matches as $bv_vm ) {
				// Test the fallback (the value that applies when the token is missing).
				$bv_colours[] = array(
					'type'  => 'fallback',
					'name'  => $bv_vm[1],
					'hex'   => strtolower( $bv_vm[2] ),
				);
				// Test the runtime token value from the free default palette.
				if ( isset( $bv_root_tokens[ $bv_vm[1] ] ) ) {
					$bv_colours[] = array(
						'type' => 'runtime',
						'name' => $bv_vm[1],
						'hex'  => $bv_root_tokens[ $bv_vm[1] ],
					);
				}
			}
		}

		// var(--token) with no fallback: resolve from the free default palette.
		if ( preg_match_all( '/var\(\s*--basalio-([a-z0-9-]+)(?!\s*,)\s*\)/', $bv_body, $bv_var_nofb, PREG_SET_ORDER ) ) {
			foreach ( $bv_var_nofb as $bv_vn ) {
				if ( isset( $bv_root_tokens[ $bv_vn[1] ] ) ) {
					$bv_colours[] = array(
						'type' => 'runtime',
						'name' => $bv_vn[1],
						'hex'  => $bv_root_tokens[ $bv_vn[1] ],
					);
				}
			}
		}

		// Plain hex (no var).
		if ( preg_match_all( '/(outline|box-shadow)\s*:\s*[^;]*(#[0-9a-fA-F]{3,6})/', $bv_body, $bv_plain_matches, PREG_SET_ORDER ) ) {
			foreach ( $bv_plain_matches as $bv_pm ) {
				$bv_colours[] = array(
					'type' => 'literal',
					'name' => '',
					'hex'  => strtolower( $bv_pm[2] ),
				);
			}
		}

		foreach ( $bv_colours as $bv_colour ) {
			$bv_rgb = Basalio_Contrast::hex_to_rgb( $bv_colour['hex'] );
			if ( null === $bv_rgb ) {
				continue;
			}

			// For the lightbox close button, assert against both neighbours.
			if ( false !== strpos( $bv_selector, 'basalio-lightbox__close' ) ) {
				if ( 'paper' === $bv_colour['name'] || '#ffffff' === $bv_colour['hex'] ) {
					$bv_ratio = Basalio_Contrast::contrast_ratio( $bv_rgb, $bv_backdrop );
					if ( $bv_ratio < 3.0 ) {
						$bv_focus_bad[] = "{$bv_rel} {$bv_selector} paper on backdrop {$bv_colour['hex']} " . round( $bv_ratio, 2 ) . ':1';
					}
				} elseif ( 'ink' === $bv_colour['name'] || '#0a0a0a' === $bv_colour['hex'] ) {
					$bv_ratio = Basalio_Contrast::contrast_ratio( $bv_rgb, $bv_acid );
					if ( $bv_ratio < 3.0 ) {
						$bv_focus_bad[] = "{$bv_rel} {$bv_selector} ink on acid {$bv_colour['hex']} " . round( $bv_ratio, 2 ) . ':1';
					}
				}
				continue;
			}

			// Everywhere else the ring sits on the (default white) surface.
			$bv_ratio = Basalio_Contrast::contrast_ratio( $bv_rgb, $bv_surface_white );
			if ( $bv_ratio < 3.0 ) {
				$bv_focus_bad[] = "{$bv_rel} {$bv_selector} {$bv_colour['type']} {$bv_colour['name']} {$bv_colour['hex']} " . round( $bv_ratio, 2 ) . ':1';
			}
		}
	}
}

bv_ok(
	'every focus-visible ring meets 3:1 against its adjacent surface',
	empty( $bv_focus_bad ),
	implode( "\n        ", $bv_focus_bad )
);

echo "\n=== Motion schema pipeline (Tasks 1-3) ===\n";

$token_css = Basalio_Tokens::to_css();
bv_ok( 'token CSS contains --duration-base: 600ms', false !== strpos( $token_css, '--duration-base: 600ms' ) );
bv_ok( 'token CSS contains --easing-glide:', false !== strpos( $token_css, '--easing-glide:' ) );

$text_reveal_block = WP_Block_Type_Registry::get_instance()->get_registered( 'basalio/text-reveal' );
bv_ok(
	'text-reveal block type has motionParams attribute',
	$text_reveal_block instanceof WP_Block_Type && isset( $text_reveal_block->attributes['motionParams'] )
);

$motion_params_attr = ( $text_reveal_block instanceof WP_Block_Type && isset( $text_reveal_block->attributes['motionParams'] ) )
	? $text_reveal_block->attributes['motionParams']
	: array();

bv_ok(
	'motionParams type is object',
	isset( $motion_params_attr['type'] ) && 'object' === $motion_params_attr['type']
);

bv_ok(
	'motionParams default contains duration',
	isset( $motion_params_attr['default'] ) && is_array( $motion_params_attr['default'] ) && isset( $motion_params_attr['default']['duration'] )
);

bv_ok(
	'motionParams default contains triggerThreshold',
	isset( $motion_params_attr['default'] ) && is_array( $motion_params_attr['default'] ) && isset( $motion_params_attr['default']['triggerThreshold'] )
);

$text_reveal_schema = Basalio::get_block_schema( 'basalio/text-reveal' );
bv_ok(
	'text-reveal block schema is non-empty',
	! empty( $text_reveal_schema )
);
bv_ok(
	'text-reveal block schema contains parameters',
	isset( $text_reveal_schema['parameters'] ) && is_array( $text_reveal_schema['parameters'] )
);

$validator = new ReflectionMethod( 'Basalio', 'validate_block_schema' );
$validator->setAccessible( true );
$malformed = array(
	'trigger'    => 'scroll',
	'parameters' => array(),
);
$validation_result = $validator->invoke( null, $malformed );
bv_ok(
	'schema validator rejects schema missing motionType',
	$validation_result instanceof WP_Error
);

$text_reveal_html = bv_render( 'verify-text-reveal' );
if ( null === $text_reveal_html ) {
	bv_skip_fixture( 'Text Reveal front end resolves --basalio-tr-duration', 'fixture page missing: verify-text-reveal' );
	bv_skip_fixture( 'Text Reveal front end includes scroll-trigger marker', 'fixture page missing: verify-text-reveal' );
	bv_skip_fixture( 'legacy wordByWord block renders word spans', 'fixture page missing: verify-text-reveal' );
	bv_skip_fixture( 'legacy wordByWord block renders visible text', 'fixture page missing: verify-text-reveal' );
} else {
	bv_ok(
		'Text Reveal front end resolves --basalio-tr-duration',
		(bool) preg_match( '/--basalio-tr-duration:\s*var\(--basalio-duration-(?:short|base|long),\s*\d+ms\)/', $text_reveal_html )
	);
	bv_ok(
		'Text Reveal front end includes scroll-trigger marker',
		false !== strpos( $text_reveal_html, 'data-basalio-motion-reveal' )
	);
	bv_ok(
		'legacy wordByWord block renders word spans',
		false !== strpos( $text_reveal_html, 'basalio-text-reveal__word' )
	);
	bv_ok(
		'legacy wordByWord block renders visible text',
		false !== strpos( wp_strip_all_tags( $text_reveal_html ), 'Word by word legacy' )
	);
}

$inject_dummy_param = function( $schema, $module_name ) {
	if ( 'text-reveal' === $module_name && isset( $schema['parameters'] ) ) {
		$schema['parameters']['__bv_test_param'] = array(
			'label'   => 'Test parameter',
			'type'    => 'toggle',
			'default' => true,
		);
	}
	return $schema;
};
add_filter( 'basalio_block_schema', $inject_dummy_param, 10, 2 );

$registry = WP_Block_Type_Registry::get_instance();
foreach ( $registry->get_all_registered() as $name => $block ) {
	if ( 0 === strpos( $name, 'basalio/' ) ) {
		$registry->unregister( $name );
	}
}
Basalio::instance()->register_modules();

$text_reveal_reregistered = $registry->get_registered( 'basalio/text-reveal' );
bv_ok(
	'dummy schema parameter appears in motionParams default after re-registration',
	$text_reveal_reregistered instanceof WP_Block_Type
		&& isset( $text_reveal_reregistered->attributes['motionParams']['default']['__bv_test_param'] )
		&& true === $text_reveal_reregistered->attributes['motionParams']['default']['__bv_test_param'],
	'A parameter added only to motion.schema.json must reach the registered block type.'
);

remove_filter( 'basalio_block_schema', $inject_dummy_param, 10 );

echo "\n=== Reduced-motion fallback CSS coverage ===\n";

$bv_fallback_css_by_module = array();
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/style.css' ) as $bv_css_path ) {
	$bv_fallback_css_by_module[ basename( dirname( $bv_css_path ) ) ] = (string) file_get_contents( $bv_css_path );
}

foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/motion.schema.json' ) as $bv_schema_path ) {
	$bv_module_name = basename( dirname( $bv_schema_path ) );
	$bv_schema      = json_decode( (string) file_get_contents( $bv_schema_path ), true );

	if ( empty( $bv_schema['parameters']['reducedMotionFallback']['options'] ) ) {
		continue;
	}

	$bv_css = isset( $bv_fallback_css_by_module[ $bv_module_name ] ) ? $bv_fallback_css_by_module[ $bv_module_name ] : '';
	foreach ( $bv_schema['parameters']['reducedMotionFallback']['options'] as $bv_option ) {
		$bv_slug = 'fallback-' . preg_replace( '/\s+/', '-', strtolower( trim( $bv_option['value'] ) ) );
		bv_ok(
			"{$bv_module_name} reducedMotionFallback selector contains {$bv_slug}",
			false !== strpos( $bv_css, $bv_slug ),
			"Expected a selector containing '{$bv_slug}' in modules/{$bv_module_name}/style.css"
		);
	}
}

$total = $GLOBALS['bv_pass'] + $GLOBALS['bv_fail'] + $GLOBALS['bv_skip'];
$fixture_count = count( array_unique( $GLOBALS['bv_missing_fixtures'] ) );
printf(
	"\n%d passed, %d failed, %d skipped (%d fixtures missing).\n",
	$GLOBALS['bv_pass'],
	$GLOBALS['bv_fail'],
	$GLOBALS['bv_skip'],
	$fixture_count
);

echo "\nStill requires a browser (JS runtime, not assertable here):\n";
echo "  - Scroll with a screen reader: silence. Press Next: exactly one announcement.\n";
echo "  - Accessibility tree: one image in the sequence, not six.\n";
echo "  - Click the pinned image: the lightbox actually opens.\n";
echo "  - Translated JS strings need make-json plus a test locale.\n\n";

exit( ( $GLOBALS['bv_fail'] > 0 || ! empty( $GLOBALS['bv_missing_fixtures'] ) ) ? 1 : 0 );
