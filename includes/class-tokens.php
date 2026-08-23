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
	 * Default motion and palette token map.
	 *
	 * Populated with the free plugin defaults. Pro and third-party code may
	 * filter these via the `basalio_tokens` hook, but the front-end must always
	 * emit at least these values so blocks render predictably when Pro is
	 * inactive.
	 *
	 * @var array
	 */
	private static $defaults = array(
		'duration-short'           => '200ms',
		'duration-base'            => '600ms',
		'duration-long'            => '1200ms',
		'easing-glide'             => 'cubic-bezier(0.2, 0.7, 0.2, 1)',
		'easing-snap'              => 'cubic-bezier(0.16, 1, 0.3, 1)',
		'easing-ease'              => 'ease',
		'easing-linear'            => 'linear',
		'motion-scroll-threshold'  => '0.5',
		'motion-reveal-distance'   => '14px',
	);

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
