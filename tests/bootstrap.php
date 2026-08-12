<?php
/**
 * Basalio: bootstrap for the test scripts in this folder.
 *
 * These scripts live inside the plugin repo so they are version controlled and
 * run on every change, but they need a WordPress install to load. The plugin is
 * typically symlinked into one, and PHP resolves symlinks in __DIR__, so walking
 * up from here does NOT find wp-load.php. Resolution order:
 *
 *   1. First CLI argument, if it points at a directory containing wp-load.php.
 *   2. The WP_ROOT environment variable.
 *   3. ../../local-dev-site, relative to the plugin root (the usual layout).
 *
 * Usage: php tests/verify-fixes.php [/path/to/wordpress]
 *
 * @package Basalio
 */

/**
 * Locate and load WordPress, or exit with a usable message.
 *
 * @param array $argv CLI arguments.
 * @return void
 */
function basalio_tests_bootstrap( $argv ) {
	$candidates = array();

	if ( isset( $argv[1] ) && '' !== $argv[1] ) {
		$candidates[] = rtrim( $argv[1], '/' );
	}

	$env = getenv( 'WP_ROOT' );
	if ( false !== $env && '' !== $env ) {
		$candidates[] = rtrim( $env, '/' );
	}

	// Default layout: <products>/basalio/{plugin,local-dev-site}
	$candidates[] = dirname( __DIR__, 2 ) . '/local-dev-site';

	foreach ( $candidates as $dir ) {
		if ( is_readable( $dir . '/wp-load.php' ) ) {
			require $dir . '/wp-load.php';
			return;
		}
	}

	fwrite( STDERR, "Could not locate WordPress. Tried:\n" );
	foreach ( $candidates as $dir ) {
		fwrite( STDERR, "  $dir/wp-load.php\n" );
	}
	fwrite( STDERR, "\nPass the path explicitly:  php tests/verify-fixes.php /path/to/wordpress\n" );
	fwrite( STDERR, "Or export WP_ROOT=/path/to/wordpress\n" );
	exit( 1 );
}

basalio_tests_bootstrap( isset( $argv ) ? $argv : array() );

/** Absolute path to the plugin root, for reading shipped source in assertions. */
define( 'BASALIO_TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
