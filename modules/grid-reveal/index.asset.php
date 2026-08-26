<?php
/**
 * Dependency + version manifest for the editor script (index.js).
 *
 * WordPress normally generates this file via @wordpress/scripts. Basalio ships
 * no build step (the editor script is hand-written against WordPress's bundled
 * globals), so this sidecar is authored by hand. register_block_type reads it
 * to know which core scripts index.js depends on and how to version it.
 *
 * @package Basalio
 */

return array(
	'dependencies' => array(
		'basalio-editor',
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
	),
	'version'      => '0.6.4',
);
