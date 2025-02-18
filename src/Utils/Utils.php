<?php
/**
 * Shared utility functions.
 *
 * @package SnapWP\Helper\Utils
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Utils;

/**
 * Class - Utils
 */
final class Utils {
	/**
	 * Transforms a kebab-case string to camelCase.
	 *
	 * Mimics WP_Interactivity_API::kebab_to_camel_case().
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/20cb3098c825cef99371f72447aa4986426c331f/src/wp-includes/interactivity-api/class-wp-interactivity-api.php#L644
	 *
	 * @param string $str The kebab-case string to transform to camelCase.
	 * @return string The transformed camelCase string.
	 */
	public static function kebab_to_camel_case( string $str ): string {
		// Replace multiple dashes with a single dash.
		$str = preg_replace( '/-+/', '-', $str );

		// Bail early if empty or failed.
		if ( empty( $str ) ) {
			return '';
		}

		// Remove trailing dash.
		$str = rtrim( $str, '-' );

		$replaced = preg_replace_callback(
			'/(-)(.)/',
			static function ( $matches ) {
					return strtoupper( $matches[2] );
			},
			strtolower( $str )
		);

		return null !== $replaced ? lcfirst( $replaced ) : '';
	}
}
