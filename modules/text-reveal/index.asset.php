<?php
/**
 * Dependency + version manifest for the editor script (index.js).
 *
 * Hand-authored (Basalio ships no build step). Note there is no front-end script
 * for this module at all, the reveal is entirely CSS.
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
	'version'      => '0.6.3',
);
