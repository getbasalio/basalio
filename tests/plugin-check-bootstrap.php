<?php
/**
 * Bootstrap to load Plugin Check CLI without requiring plugin.php early.
 * Defines the constants cli.php expects.
 */
$plugin_check_dir = '/Users/angelomanzanojr/Projects/products/basalio/local-dev-site/wp-content/plugins/plugin-check/';
$plugin_check_url = 'http://127.0.0.1:8091/wp-content/plugins/plugin-check/';

if ( ! defined( 'WP_PLUGIN_CHECK_VERSION' ) ) {
    define( 'WP_PLUGIN_CHECK_VERSION', '2.1.0' );
}
if ( ! defined( 'WP_PLUGIN_CHECK_MINIMUM_PHP' ) ) {
    define( 'WP_PLUGIN_CHECK_MINIMUM_PHP', '7.4' );
}
if ( ! defined( 'WP_PLUGIN_CHECK_MAIN_FILE' ) ) {
    define( 'WP_PLUGIN_CHECK_MAIN_FILE', $plugin_check_dir . 'plugin.php' );
}
if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH' ) ) {
    define( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH', $plugin_check_dir );
}
if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_URL' ) ) {
    define( 'WP_PLUGIN_CHECK_PLUGIN_DIR_URL', $plugin_check_url );
}

require_once $plugin_check_dir . 'cli.php';
