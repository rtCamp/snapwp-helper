<?php
/**
 * Registers the GlobalStyles Object to WPGraphQL.
 *
 * Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\SiteEditor
 * @since 0.0.1
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
			'blockStyles'       => [
				'type'        => 'String',
				'description' => __( 'The Global block styles.', 'snapwp-helper' ),
				'resolve'     => fn () => $this->get_global_styles_for_blocks(),
			],
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
		];
	}

	/**
	 * Gets the individual global styles for blocks.
	 *
	 * Mimics the behavior of `wp_add_global_styles_for_blocks()`.
	 *
	 * @see wp_add_global_styles_for_blocks()
	 */
	private function get_global_styles_for_blocks(): string {
		$block_css = '';

		$tree        = \WP_Theme_JSON_Resolver::get_merged_data();
		$block_nodes = $tree->get_styles_block_nodes();

		foreach ( $block_nodes as $metadata ) {
			$block_css .= $tree->get_styles_for_block( $metadata );
		}

		return $block_css;
	}
}
