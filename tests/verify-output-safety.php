<?php
/**
 * Regression coverage for WordPress.org review R basalio/basalio/26Aug26/T2.
 * Run only against a disposable WordPress install, with Basalio active.
 *
 * @package Basalio
 */
require __DIR__ . '/bootstrap.php';
$passed = 0;
$failed = 0;
function basalio_safety_assert( $condition, $label ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo "PASS $label\n";
	} else {
		++$failed;
		echo "FAIL $label\n";
	}
}
$valid = array( '200ms', '0', '14px', '#abcdef', 'ease-in-out', 'cubic-bezier(0.2, 0.7, 0.2, 1)', 'var(--basalio-duration-base, 600ms)', 'clamp(1rem, calc(2vw + 1rem), 3rem)', 'rgb(10 20 30 / 50%)' );
foreach ( $valid as $value ) {
	basalio_safety_assert( $value === Basalio_Tokens::sanitize_value( $value ), 'Retains valid CSS token: ' . $value );
}
$hostile = array( '</style><script>alert(1)</script>', '1; color:red', '1}body{display:none}', 'url(https://example.test/track)', 'u\\72l(https://example.test)', 'expression(alert(1))', '1/*comment*/', '&lt;/style&gt;', '"quoted"', 'calc(1px', '1px)', array(), new stdClass() );
foreach ( $hostile as $i => $value ) {
	basalio_safety_assert( '' === Basalio_Tokens::sanitize_value( $value ), 'Rejects unsafe CSS value ' . $i );
}
basalio_safety_assert( '' === Basalio_Tokens::declaration( 'bad;}body{', '1' ), 'Rejects invalid token name' );
$filter = function ( $tokens ) {
	$tokens['evil'] = '</style><script>alert(1)</script>';
	$tokens['bad;name'] = '1';
	$tokens['duration-base'] = '1;--injected:999';
	$tokens['color-good'] = '#123456';
	return $tokens;
};
add_filter( 'basalio_tokens', $filter );
$css = Basalio_Tokens::to_css();
basalio_safety_assert( false === strpos( $css, '<' ) && false === strpos( $css, 'injected' ) && false === strpos( $css, 'bad;name' ), 'Filtered token map cannot inject HTML or declarations' );
basalio_safety_assert( false !== strpos( $css, '--color-good: #123456;' ), 'Valid extension tokens survive' );
$motion = Basalio::resolve_motion_params_to_css( 'basalio/text-reveal', array( 'duration' => 'base', 'easing' => 'glide' ) );
basalio_safety_assert( false === strpos( $motion['style'], 'injected' ) && false === strpos( $motion['style'], '<' ), 'Token fallback in motion CSS cannot inject declarations' );
remove_filter( 'basalio_tokens', $filter );
foreach ( array( '</style><script>alert(1)</script>', ':root{--ok:1}body{display:none}', array() ) as $candidate ) {
	$filter = function () use ( $candidate ) { return $candidate; };
	add_filter( 'basalio_token_css', $filter );
	basalio_safety_assert( '' === Basalio_Tokens::to_css(), 'Final CSS filter cannot bypass validation' );
	remove_filter( 'basalio_token_css', $filter );
}
$motion = Basalio::resolve_motion_params_to_css( 'basalio/text-reveal', array( 'easing' => 'x);background:url(https://example.test);--x:(' ) );
basalio_safety_assert( false === strpos( $motion['style'], 'background' ), 'Saved motion parameter cannot inject a declaration' );
$clean = wp_kses( '<button type="button" data-basalio-lightbox="https://example.test/a.jpg" aria-label="Expand" onclick="alert(1)"><img src="https://example.test/a.jpg" alt="Sample" onerror="alert(1)"></button><script>alert(1)</script><a href="javascript:alert(1)">Link</a>', basalio_allowed_html() );
basalio_safety_assert( false === strpos( $clean, 'onclick' ) && false === strpos( $clean, 'onerror' ) && false === strpos( $clean, '<script' ) && false === strpos( $clean, 'javascript:' ), 'Fragment allow-list rejects executable markup' );
basalio_safety_assert( false !== strpos( $clean, '<button' ) && false !== strpos( $clean, 'data-basalio-lightbox=' ) && false !== strpos( $clean, 'aria-label=' ), 'Lightbox button and accessibility attributes survive' );
foreach ( array( 'basalio/pinned-scroll', 'basalio/custom-cursor' ) as $name ) {
	$inner = '<p class="keep">Story <strong>content</strong></p><button type="button" aria-label="Example">Example</button><script>alert(1)</script><img src="https://example.test/a.jpg" onerror="alert(1)">';
	$html = render_block( array( 'blockName' => $name, 'attrs' => array( 'pinnedImageUrl' => 'https://example.test/a.jpg' ), 'innerBlocks' => array(), 'innerHTML' => $inner, 'innerContent' => array( $inner ) ) );
	basalio_safety_assert( false !== strpos( $html, '<strong>content</strong>' ), $name . ' preserves nested story markup' );
	basalio_safety_assert( false === strpos( $html, '<script' ) && false === strpos( $html, 'onerror' ), $name . ' escapes nested output at render' );
}
$nested = '<input type="range" min="0" max="100" value="50" aria-label="Compare"><iframe src="https://example.test/embed" title="Film" srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;"></iframe>';
$clean = wp_kses( $nested, basalio_allowed_html() );
basalio_safety_assert( false !== strpos( $clean, 'type="range"' ) && false !== strpos( $clean, '<iframe' ) && false === strpos( $clean, 'srcdoc' ), 'Nested range control and safe embed survive without srcdoc' );
echo "\nOutput safety: $passed passed, $failed failed.\n";
exit( $failed ? 1 : 0 );
