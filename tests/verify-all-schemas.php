<?php
/**
 * Ad-hoc verification: every existing motion.schema.json passes validation
 * and causes motionParams injection.
 *
 * Run: /Applications/Blocs.app/Contents/MacOS/php tests/verify-all-schemas.php /Users/angelomanzanojr/Projects/products/basalio/local-dev-site
 */

require __DIR__ . '/bootstrap.php';

$registry = WP_Block_Type_Registry::get_instance();
foreach ( $registry->get_all_registered() as $name => $block ) {
	if ( 0 === strpos( $name, 'basalio/' ) ) {
		$registry->unregister( $name );
	}
}

// Reset schemas.
$ref = new ReflectionProperty( 'Basalio', 'block_schemas' );
$ref->setAccessible( true );
$ref->setValue( null, array() );

Basalio::instance()->register_modules();

$method = new ReflectionMethod( 'Basalio', 'validate_block_schema' );
$method->setAccessible( true );

$pass = 0;
$fail = 0;

foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/motion.schema.json' ) as $path ) {
	$module_name = basename( dirname( $path ) );
	$schema      = json_decode( (string) file_get_contents( $path ), true );
	$result      = $method->invoke( null, $schema );

	if ( true === $result ) {
		$pass++;
		echo "PASS  {$module_name}\n";
	} else {
		$fail++;
		echo "FAIL  {$module_name}: " . implode( '; ', $result->get_error_messages() ) . "\n";
	}
}

$total_modules = $pass + $fail;
echo "\n{$pass}/{$total_modules} schemas valid.\n";

// Verify motionParams injection for every module with a schema.
$injected = 0;
$missing  = 0;
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/motion.schema.json' ) as $path ) {
	$module_name = basename( dirname( $path ) );
	$block_name  = 'basalio/' . $module_name;
	$block       = $registry->get_registered( $block_name );

	if ( ! $block instanceof WP_Block_Type ) {
		echo "WARN  {$block_name} not registered\n";
		$missing++;
		continue;
	}

	if ( isset( $block->attributes['motionParams'] ) ) {
		$injected++;
		echo "OK    {$block_name} has motionParams\n";
	} else {
		$missing++;
		echo "FAIL  {$block_name} missing motionParams\n";
	}
}

$total_blocks = $injected + $missing;
echo "\n{$injected}/{$total_blocks} blocks have motionParams.\n";

// Verify every schema parameter has a runtime consumer.
function basalio_param_to_kebab( $name ) {
	$name = str_replace( '_', '-', $name );
	$name = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $name );
	$name = preg_replace( '/-+/', '-', $name );
	return trim( strtolower( $name ), '-' );
}

$consumer_pass = 0;
$consumer_fail = 0;
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/motion.schema.json' ) as $path ) {
	$module_name = basename( dirname( $path ) );
	$schema      = json_decode( (string) file_get_contents( $path ), true );
	$parameters  = isset( $schema['parameters'] ) && is_array( $schema['parameters'] ) ? $schema['parameters'] : array();
	if ( empty( $parameters ) ) {
		continue;
	}

	$runtime_files = array(
		BASALIO_TESTS_PLUGIN_DIR . '/modules/' . $module_name . '/render.php',
		BASALIO_TESTS_PLUGIN_DIR . '/modules/' . $module_name . '/index.js',
		BASALIO_TESTS_PLUGIN_DIR . '/modules/' . $module_name . '/style.css',
	);
	$core_path = BASALIO_TESTS_PLUGIN_DIR . '/assets/js/basalio-core.js';

	$contents = '';
	foreach ( $runtime_files as $f ) {
		if ( file_exists( $f ) ) {
			$contents .= "\n" . file_get_contents( $f );
		}
	}
	$core_contents = file_exists( $core_path ) ? file_get_contents( $core_path ) : '';

	foreach ( $parameters as $param_name => $param ) {
		$kebab = basalio_param_to_kebab( $param_name );
		$found = false;

		// Explicit reference in module runtime.
		if ( false !== strpos( $contents, $param_name ) || false !== strpos( $contents, $kebab ) ) {
			$found = true;
		}

		// Reference in shared core (data attributes, computed styles, etc).
		if ( ! $found && ( false !== strpos( $core_contents, $param_name ) || false !== strpos( $core_contents, $kebab ) ) ) {
			$found = true;
		}

		// reducedMotionFallback is consumed by its emitted fallback class.
		if ( ! $found && 'reducedMotionFallback' === $param_name ) {
			$fallback = isset( $param['default'] ) ? (string) $param['default'] : '';
			$slug     = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $fallback ) );
			$slug     = trim( $slug, '-' );
			if ( '' !== $slug && false !== strpos( $contents, '--fallback-' . $slug ) ) {
				$found = true;
			}
		}

		if ( $found ) {
			$consumer_pass++;
			// echo "OK    {$module_name}::{$param_name}\n";
		} else {
			$consumer_fail++;
			echo "FAIL  {$module_name}::{$param_name} has no runtime consumer\n";
		}
	}
}

$total_consumer = $consumer_pass + $consumer_fail;
echo "\n{$consumer_pass}/{$total_consumer} schema params have runtime consumers.\n";

// Inverse test: every editor control that sets a motion/timing/easing attribute
// must have a corresponding parameter in that block's motion.schema.json.
$editor_motion_attributes = array(
	'text-reveal'               => array( 'splitUnit', 'duration', 'easing', 'triggerThreshold', 'reducedMotionFallback' ),
	'grid-reveal'               => array( 'revealStyle', 'reducedMotionFallback' ),
	'before-after'              => array( 'startPosition', 'dragStep', 'reducedMotionFallback' ),
	'case-study-transition'     => array( 'focusTarget', 'announceChange', 'reducedMotionFallback', 'transitionDuration' ),
	'custom-cursor'             => array( 'size', 'cursorStyle', 'reducedMotionFallback' ),
	'filterable-grid'           => array( 'resultCount', 'reducedMotionFallback' ),
	'magnetic-button'           => array( 'strength', 'reducedMotionFallback' ),
	'pinned-scroll'             => array( 'reducedMotionFallback' ),
	'scroll-sequence'           => array( 'reducedMotionFallback', 'scrollScreens' ),
);

$inverse_pass = 0;
$inverse_fail = 0;
foreach ( $editor_motion_attributes as $module_name => $attributes ) {
	$schema_path = BASALIO_TESTS_PLUGIN_DIR . '/modules/' . $module_name . '/motion.schema.json';
	$schema      = file_exists( $schema_path ) ? json_decode( (string) file_get_contents( $schema_path ), true ) : array();
	$parameters  = isset( $schema['parameters'] ) && is_array( $schema['parameters'] ) ? array_keys( $schema['parameters'] ) : array();

	foreach ( $attributes as $attr ) {
		if ( in_array( $attr, $parameters, true ) ) {
			$inverse_pass++;
			echo "OK    {$module_name}::{$attr} has schema param\n";
		} else {
			$inverse_fail++;
			echo "FAIL  {$module_name}::{$attr} missing from motion.schema.json\n";
		}
	}
}

$total_inverse = $inverse_pass + $inverse_fail;
echo "\n{$inverse_pass}/{$total_inverse} editor motion attributes have schema params.\n";

// Verify every motion block has a prefers-reduced-motion: reduce rule that does
// NOT require the fallback class to apply. The fallback class is an authoring
// override; reduced-motion suppression must work for any visitor who sets the
// preference, whether or not the saved block happens to carry the class.
function basalio_extract_reduce_media_blocks( $css ) {
	$blocks   = array();
	$len      = strlen( $css );
	$i        = 0;
	while ( $i < $len ) {
		// Find the next @media.
		$at = strpos( $css, '@media', $i );
		if ( false === $at ) {
			break;
		}
		// Parse the media query prelude up to the opening brace.
		$brace = strpos( $css, '{', $at );
		if ( false === $brace ) {
			break;
		}
		$prelude = substr( $css, $at, $brace - $at );
		// Match the value "reduce" as a standalone token, not as a substring of
		// "reduced" (e.g. prefers-reduced-motion: no-preference must not match).
		$is_reduce = (bool) preg_match( '/prefers-reduced-motion\s*:\s*reduce\b/i', $prelude );
		if ( $is_reduce ) {
			// Capture content until matching close brace.
			$depth = 1;
			$j     = $brace + 1;
			while ( $j < $len && $depth > 0 ) {
				if ( '{' === $css[ $j ] ) {
					$depth++;
				} elseif ( '}' === $css[ $j ] ) {
					$depth--;
				}
				$j++;
			}
			$blocks[] = substr( $css, $brace + 1, $j - $brace - 2 );
			$i        = $j;
		} else {
			$i = $brace + 1;
		}
	}
	return $blocks;
}

function basalio_reduce_rule_requires_fallback_class_only( $css ) {
	$blocks = basalio_extract_reduce_media_blocks( $css );
	if ( empty( $blocks ) ) {
		return true; // No reduce rule at all.
	}

	foreach ( $blocks as $block ) {
		// Split the block into individual rule sets by top-level braces.
		$depth    = 0;
		$selector = '';
		$len      = strlen( $block );
		for ( $i = 0; $i < $len; $i++ ) {
			$char = $block[ $i ];
			if ( '{' === $char ) {
				if ( 0 === $depth ) {
					// $selector holds the selectors for this rule set.
					$parts = explode( ',', $selector );
					foreach ( $parts as $part ) {
						$part = trim( $part );
						if ( '' === $part ) {
							continue;
						}
						// Ignore keyframes selectors and at-rules.
						if ( preg_match( '/^(@|from|to|[0-9]+%)/i', $part ) ) {
							continue;
						}
						// A selector that does not require the fallback class means
						// the reduce rule applies regardless of the class.
						if ( false === strpos( $part, '--fallback-' ) ) {
							return false;
						}
					}
				}
				$depth++;
				$selector = '';
			} elseif ( '}' === $char ) {
				$depth--;
				$selector = '';
			} else {
				if ( 0 === $depth ) {
					$selector .= $char;
				}
			}
		}
	}

	return true; // Every reduce selector required a fallback class.
}

$trap_pass = 0;
$trap_fail = 0;
foreach ( glob( BASALIO_TESTS_PLUGIN_DIR . '/modules/*/motion.schema.json' ) as $path ) {
	$module_name = basename( dirname( $path ) );
	$style_path  = BASALIO_TESTS_PLUGIN_DIR . '/modules/' . $module_name . '/style.css';

	if ( ! file_exists( $style_path ) ) {
		echo "FAIL  {$module_name} has schema but no style.css\n";
		$trap_fail++;
		continue;
	}

	$css = file_get_contents( $style_path );
	if ( basalio_reduce_rule_requires_fallback_class_only( $css ) ) {
		$trap_fail++;
		echo "FAIL  {$module_name}: prefers-reduced-motion: reduce rule requires fallback class or is missing\n";
	} else {
		$trap_pass++;
		echo "PASS  {$module_name}: reduce rule applies without fallback class\n";
	}
}

$total_trap = $trap_pass + $trap_fail;
echo "\n{$trap_pass}/{$total_trap} blocks have reduce rules that work without the fallback class.\n";

exit( $fail > 0 || $missing > 0 || $consumer_fail > 0 || $inverse_fail > 0 || $trap_fail > 0 ? 1 : 0 );
