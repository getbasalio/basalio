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
		$css    = ':root{';
		if ( is_array( $tokens ) ) {
			foreach ( $tokens as $name => $value ) {
				$css .= self::declaration( $name, $value );
			}
		}
		$css .= '}';

		// Validate AFTER the final extension filter, immediately before output.
		return self::sanitize_stylesheet( apply_filters( 'basalio_token_css', $css, $tokens ) );
	}

	/**
	 * Validate a single motion/palette CSS value, without HTML entity encoding.
	 *
	 * No URLs, strings, escapes, comments, declarations or markup are accepted.
	 * Keep this grammar deliberately limited to scalar design tokens and the
	 * numeric, color and variable functions needed by the public token API.
	 *
	 * @param mixed $value Candidate value.
	 * @return string Valid value, or an empty string.
	 */
	public static function sanitize_value( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return '';
		}
		$value = trim( (string) $value );
		if ( '' === $value || strlen( $value ) > 1024 || ! preg_match( '~\A[a-zA-Z0-9#.,%() +*/_\t\r\n-]+\z~D', $value ) || false !== strpos( $value, '/*' ) || false !== strpos( $value, '*/' ) ) {
			return '';
		}
		preg_match_all( '/([a-zA-Z_-][a-zA-Z0-9_-]*)\s*\(/', $value, $functions );
		$allowed = array( 'var', 'calc', 'min', 'max', 'clamp', 'cubic-bezier', 'steps', 'linear', 'rgb', 'rgba', 'hsl', 'hsla', 'hwb', 'lab', 'lch', 'oklab', 'oklch' );
		foreach ( $functions[1] as $function ) {
			if ( ! in_array( strtolower( $function ), $allowed, true ) ) {
				return '';
			}
		}
		$depth = 0;
		for ( $i = 0, $length = strlen( $value ); $i < $length; $i++ ) {
			if ( '(' === $value[ $i ] ) {
				$depth++;
			} elseif ( ')' === $value[ $i ] && --$depth < 0 ) {
				return '';
			}
		}
		return 0 === $depth ? $value : '';
	}

	/**
	 * Build one validated CSS custom-property declaration.
	 *
	 * @param mixed $name Name without the leading two hyphens.
	 * @param mixed $value Value before interpolation.
	 * @return string Safe declaration, or an empty string.
	 */
	public static function declaration( $name, $value ) {
		if ( ! is_string( $name ) || ! preg_match( '/\A[a-zA-Z_][a-zA-Z0-9_-]*\z/D', $name ) ) {
			return '';
		}
		$value = self::sanitize_value( $value );
		return '' !== $value ? '--' . $name . ':' . $value . ';' : '';
	}

	/**
	 * Restrict the final token stylesheet to one :root custom-property block.
	 *
	 * @param mixed $css Filtered token stylesheet.
	 * @return string Safe inline CSS (never HTML).
	 */
	public static function sanitize_stylesheet( $css ) {
		if ( ! is_string( $css ) || ! preg_match( '/\A\s*:root\s*\{([^{}<>]*)\}\s*\z/sD', $css, $match ) ) {
			return '';
		}
		$declarations = '';
		foreach ( explode( ';', $match[1] ) as $item ) {
			if ( preg_match( '/\A\s*--([a-zA-Z_][a-zA-Z0-9_-]*)\s*:(.*)\z/sD', $item, $parts ) ) {
				$declarations .= str_replace( ':', ': ', self::declaration( $parts[1], $parts[2] ) );
			}
		}
		return '' !== $declarations ? ':root{' . $declarations . '}' : '';
	}
}
