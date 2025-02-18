<?php
/**
 * Registers the FontFace Object to WPGraphQL.
 *
 * @todo Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\SiteEditor
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

/**
 * Class - FontFace
 */
final class FontFace extends AbstractObject {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'FontFace';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'The WordPress Font Face.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'ascentOverride'        => [
				'type'        => 'String',
				'description' => __( 'The ascent override for the font face.', 'snapwp-helper' ),
			],
			'descentOverride'       => [
				'type'        => 'String',
				'description' => __( 'The descent override for the font face.', 'snapwp-helper' ),
			],
			'fontDisplay'           => [
				'type'        => 'String',
				'description' => __( 'The font display for the font face.', 'snapwp-helper' ),
			],
			'fontFamily'            => [
				'type'        => 'String',
				'description' => __( 'The font family for the font face.', 'snapwp-helper' ),
			],
			'fontFeatureSettings'   => [
				'type'        => 'String',
				'description' => __( 'The font feature settings for the font face.', 'snapwp-helper' ),
			],
			'fontStretch'           => [
				'type'        => 'String',
				'description' => __( 'The font stretch for the font face.', 'snapwp-helper' ),
			],
			'fontStyle'             => [
				'type'        => 'String',
				'description' => __( 'The font style for the font face.', 'snapwp-helper' ),
			],
			'fontVariant'           => [
				'type'        => 'String',
				'description' => __( 'The font variant for the font face.', 'snapwp-helper' ),
			],
			'fontVariationSettings' => [
				'type'        => 'String',
				'description' => __( 'The font variation settings for the font face.', 'snapwp-helper' ),
			],
			'fontWeight'            => [
				'type'        => 'String',
				'description' => __( 'The font weight for the font face.', 'snapwp-helper' ),
			],
			'lineGapOverride'       => [
				'type'        => 'String',
				'description' => __( 'The line gap override for the font face.', 'snapwp-helper' ),
			],
			'sizeAdjust'            => [
				'type'        => 'String',
				'description' => __( 'The size adjust for the font face.', 'snapwp-helper' ),
			],
			'src'                   => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The URL(s) to each resource containing the font data.', 'snapwp-helper' ),
			],
			'unicodeRange'          => [
				'type'        => 'String',
				'description' => __( 'The unicode range for the font face.', 'snapwp-helper' ),
			],
			'css'                   => [
				'type'        => 'String',
				'description' => __( 'The resolved CSS rule for the font face.', 'snapwp-helper' ),
			],
		];
	}
}
