<?php
/**
 * Shared helper functions for the Basalio plugin.
 *
 * Kept in one file so render callbacks can use them without duplicating logic
 * across modules. Every function is guarded with function_exists() so the file
 * can be required early and safely.
 *
 * @package Basalio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'basalio_get_attachment_alt' ) ) {
	/**
	 * Return the alt text stored for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	function basalio_get_attachment_alt( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return '';
		}
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		return is_string( $alt ) ? $alt : '';
	}
}

if ( ! function_exists( 'basalio_get_image_html' ) ) {
	/**
	 * Build an <img> tag, preferring the WordPress attachment API for srcset and
	 * falling back to a bare URL. The author's block-level alt is always honoured,
	 * including an intentional empty value (decorative image). This removes the
	 * fragile preg_replace() pattern used in earlier versions.
	 *
	 * @param int    $attachment_id Attachment ID, or 0 to use the URL.
	 * @param string $url           Image URL when no attachment is selected.
	 * @param string $alt           Author-provided alt text.
	 * @param array  $attr          Additional attributes (class, loading, decoding, etc.).
	 * @return string
	 */
	function basalio_get_image_html( $attachment_id, $url, $alt, $attr = array() ) {
		$attr = (array) $attr;

		if ( $attachment_id ) {
			$attr['alt'] = $alt;
			$html = wp_get_attachment_image( $attachment_id, 'large', false, $attr );
			if ( $html ) {
				return $html;
			}
		}

		if ( '' === $url ) {
			return '';
		}

		$defaults = array(
			'src'      => esc_url( $url ),
			'alt'      => esc_attr( $alt ),
			'decoding' => 'async',
			'loading'  => 'lazy',
		);

		// Caller-supplied values win, but src/alt must still be escaped.
		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $attr[ $key ] ) ) {
				$attr[ $key ] = $value;
			}
		}
		$attr['src'] = esc_url( $attr['src'] );
		$attr['alt'] = esc_attr( $attr['alt'] );

		$html = '<img';
		foreach ( $attr as $key => $value ) {
			if ( true === $value ) {
				$html .= ' ' . esc_attr( $key );
			} elseif ( false === $value || null === $value ) {
				continue;
			} else {
				$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
		$html .= ' />';

		return $html;
	}
}

if ( ! function_exists( 'basalio_resolve_image_name' ) ) {
	/**
	 * Resolve a meaningful accessible name for an image/link fallback.
	 *
	 * Returns the first non-empty value from: author alt override, attachment alt,
	 * title, category. Used when the author has left the image alt empty but the
	 * surrounding link still needs a name (Grid Item).
	 *
	 * @param int    $attachment_id Attachment ID, or 0.
	 * @param string $author_alt    Alt entered in the block sidebar.
	 * @param string $title         Optional item title.
	 * @param string $category      Optional item category.
	 * @return string
	 */
	function basalio_resolve_image_name( $attachment_id, $author_alt, $title = '', $category = '' ) {
		if ( '' !== $author_alt ) {
			return $author_alt;
		}
		$attachment_alt = $attachment_id ? basalio_get_attachment_alt( $attachment_id ) : '';
		if ( '' !== $attachment_alt ) {
			return $attachment_alt;
		}
		if ( '' !== $title ) {
			return $title;
		}
		if ( '' !== $category ) {
			return $category;
		}
		return '';
	}
}

if ( ! function_exists( 'basalio_tr_item' ) ) {
	/**
	 * Render one animated Text Reveal item.
	 *
	 * @param string $tag     HTML tag.
	 * @param string $inner   Already-escaped inner HTML.
	 * @param string $classes Space-separated class list.
	 * @param int    $delay   animation-delay in ms.
	 * @param string $extra   Extra attributes (already escaped), e.g. href.
	 * @return string
	 */
	function basalio_tr_item( $tag, $inner, $classes, $delay, $extra = '' ) {
		return sprintf(
			'<%1$s class="%2$s" style="--basalio-tr-delay:%3$dms"%4$s>%5$s</%1$s>',
			$tag,
			esc_attr( $classes ),
			(int) $delay,
			$extra,
			$inner
		);
	}
}

if ( ! function_exists( 'basalio_get_audit_checks' ) ) {
	/**
	 * Get the full list of compliance audit checks.
	 *
	 * Pro injects its own checks via the `basalio_audit_checks` filter.
	 *
	 * @return array
	 */
	function basalio_get_audit_checks() {
		return apply_filters( 'basalio_audit_checks', array() );
	}
}
