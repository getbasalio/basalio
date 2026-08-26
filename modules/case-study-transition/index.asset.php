<?php
/**
 * Dependency + version manifest for the editor script (index.js).
 *
 * Hand-authored (Basalio ships no build step) so register_block_type knows which
 * bundled WordPress scripts index.js depends on.
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
