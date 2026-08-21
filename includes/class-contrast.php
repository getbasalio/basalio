<?php
/**
 * Shared sRGB contrast maths for Basalio and Basalio Pro.
 *
 * @package Basalio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Basalio_Contrast' ) ) {

	/**
	 * Contrast utility.
	 */
	class Basalio_Contrast {

		/**
		 * Convert a hex colour to an [r, g, b] array (0-255).
		 *
		 * @param string $hex Hex colour, with or without #.
		 * @return array|null [r, g, b] or null on failure.
		 */
		public static function hex_to_rgb( $hex ) {
			$hex = ltrim( trim( (string) $hex ), '#' );

			if ( 3 === strlen( $hex ) ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}

			if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
				return null;
			}

			return array(
				(int) hexdec( substr( $hex, 0, 2 ) ),
				(int) hexdec( substr( $hex, 2, 2 ) ),
				(int) hexdec( substr( $hex, 4, 2 ) ),
			);
		}

		/**
		 * Compute relative luminance of an sRGB colour.
		 *
		 * @param array $rgb [r, g, b] 0-255.
		 * @return float
		 */
		public static function relative_luminance( array $rgb ) {
			$lin = function ( $c ) {
				$c = $c / 255;
				return $c <= 0.04045 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
			};

			return 0.2126 * $lin( $rgb[0] ) + 0.7152 * $lin( $rgb[1] ) + 0.0722 * $lin( $rgb[2] );
		}

		/**
		 * Compute WCAG contrast ratio between two sRGB colours.
		 *
		 * @param array $a [r, g, b] 0-255.
		 * @param array $b [r, g, b] 0-255.
		 * @return float
		 */
		public static function contrast_ratio( array $a, array $b ) {
			$l1 = self::relative_luminance( $a );
			$l2 = self::relative_luminance( $b );

			$lighter = max( $l1, $l2 );
			$darker  = min( $l1, $l2 );

			return ( $lighter + 0.05 ) / ( $darker + 0.05 );
		}

		/**
		 * Pick the candidate colour with the highest contrast against a background.
		 *
		 * @param string $background_hex Background colour.
		 * @param array  $candidates     Array of hex strings; defaults to white/black.
		 * @return string|null Highest-ratio candidate hex, or null on failure.
		 */
		public static function choose_foreground( $background_hex, $candidates = array( '#ffffff', '#000000' ) ) {
			$bg = self::hex_to_rgb( $background_hex );
			if ( null === $bg ) {
				return null;
			}

			$best       = null;
			$best_ratio = 0;

			foreach ( $candidates as $candidate ) {
				$fg = self::hex_to_rgb( $candidate );
				if ( null === $fg ) {
					continue;
				}

				$ratio = self::contrast_ratio( $bg, $fg );
				if ( $ratio > $best_ratio ) {
					$best_ratio = $ratio;
					$best       = ltrim( $candidate, '#' );
				}
			}

			return $best ? '#' . $best : null;
		}
	}
}
