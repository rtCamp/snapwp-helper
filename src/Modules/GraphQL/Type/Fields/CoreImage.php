<?php
/**
 * Registers custom fields to the GraphQL CoreImage Object.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

/**
 * Class - CoreImage
 */
final class CoreImage extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'CoreImageAttributes';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'maxWidth' => [
				'type'        => 'String',
				'description' => __( 'Maximum width of image based on theme settings or big image size threshold.', 'snapwp-helper' ),
				'resolve'     => static function () {
					$max_width = 1200;

					// 1. Get theme settings to check for wide size.
					if ( function_exists( 'wp_get_global_settings' ) ) {
						$theme_settings = wp_get_global_settings();
						$wide_size      = $theme_settings['layout']['wideSize'] ?? null;

						// Check if wide size is a string and contains a number followed by 'px'.
						if ( is_string( $wide_size ) && preg_match( '/^(\d+)px$/', $wide_size, $matches ) ) {
							$max_width = (int) $matches[1];
						}
					}

					// 2. Fallback to big_image_size_threshold if wide size is not set.
					if ( empty( $max_width ) || $max_width < 1 ) {
						$max_width = (int) apply_filters( 'big_image_size_threshold', 2560 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.
					}

					return "{$max_width}px";
				},
			],
		];
	}
}
