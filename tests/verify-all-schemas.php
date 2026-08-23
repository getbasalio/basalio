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

exit( $fail > 0 || $missing > 0 ? 1 : 0 );
