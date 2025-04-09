<?php
/**
 * Registers the GlobalStyles Object to WPGraphQL.
 *
 * @todo Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\SiteEditor
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use SnapWP\Helper\Utils\Utils;

/**
 * Class - GlobalStyles
 */
final class GlobalStyles extends AbstractObject {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'GlobalStyles';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'The Global Styles.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'customCss'         => [
				'type'        => 'String',
				'description' => __( 'The Global custom css defined in the theme or theme.json.', 'snapwp-helper' ),
				'resolve'     => static function () {
					// Don't enqueue Customizer's custom CSS separately.
					remove_action( 'wp_head', 'wp_custom_css_cb', 101 );

					$custom_css  = wp_get_custom_css();
					$custom_css .= wp_get_global_stylesheet( [ 'custom-css' ] );
					return $custom_css ?: null;
				},
			],
			'fontFaces'         => [
				'type'        => [ 'list_of' => FontFace::get_type_name() ],
				'description' => __( 'The font faces.', 'snapwp-helper' ),
				'resolve'     => static function () {
					$fonts = \WP_Font_Face_Resolver::get_fonts_from_theme_json();

					if ( empty( $fonts ) ) {
						return null;
					}

					// Font faces are nested by collection, so flatten them.
					$fonts = array_reduce( $fonts, 'array_merge', [] );

					// Convert the array keys to camelCase.
					return array_map(
						static function ( $font ) {
							/** @var string[] $font_keys */
							$font_keys = array_keys( $font );

							return array_combine(
								array_map( [ Utils::class, 'kebab_to_camel_case' ], $font_keys ),
								$font
							);
						},
						$fonts
					);
				},
			],
			'renderedFontFaces' => [
				'type'        => 'String',
				'description' => __( 'The rendered @font-face style.', 'snapwp-helper' ),
				'resolve'     => static function () {
					ob_start();

					wp_print_font_faces();

					return ob_get_clean();
				},
			],
			'stylesheet'        => [
				'type'        => 'String',
				'description' => __( 'The Global Stylesheet css.', 'snapwp-helper' ),
				'resolve'     => static function () {
					return wp_get_global_stylesheet() ?: null;
				},
			],
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
