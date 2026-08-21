<?php
/**
 * Basalio token registry.
 *
 * Owns the default token map and the token-related extension hooks.
 *
 * @package Basalio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Basalio_Tokens
 */
class Basalio_Tokens {

	/**
	 * Default token map. Empty in Stage 1; populated in Stage 3.
	 *
	 * @var array
	 */
	private static $defaults = array();

	/**
	 * Return the final token map after extensions have filtered it.
	 *
	 * @return array
	 */
	public static function get() {
		$tokens = apply_filters( 'basalio_tokens', self::$defaults );

		/**
		 * Fires after the token map has been filtered.
		 *
		 * @param array $tokens Final token map.
		 */
		do_action( 'basalio_tokens_registered', $tokens );

		return $tokens;
	}

	/**
	 * Generate a CSS custom-property block from the token map.
	 *
	 * @return string
	 */
	public static function to_css() {
		$tokens = self::get();
		$css    = ':root{ ';

		foreach ( $tokens as $name => $value ) {
			$css .= "--{$name}: {$value}; ";
		}

		$css .= '}';

		return apply_filters( 'basalio_token_css', $css, $tokens );
	}
}
