<?php
/**
 * Uninstall cleanup for Basalio.
 *
 * Runs only when the plugin is deleted from the WordPress admin. Blocks store
 * their data in post content, so there is nothing to migrate, we only remove
 * the version option written on activation.
 *
 * @package Basalio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'basalio_version' );
