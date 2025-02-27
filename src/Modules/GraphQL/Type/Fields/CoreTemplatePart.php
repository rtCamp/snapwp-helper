<?php
/**
 * Registers custom fields to the GraphQL CoreTemplatePart Object.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

/**
 * Class - CoreTemplatePart
 */
final class CoreTemplatePart extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'CoreTemplatePart';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'area_tag' => [
				'type'        => 'String',
				'description' => __( 'The area tag of the template part.', 'snapwp-helper' ),
				'resolve'     => static function ( $block ) {
					// Fetch the value of `area_tag`.
					$area_tag = $block['area_tag'] ?? null;

					return ! empty( $area_tag ) ? $area_tag : null;
				},
			],
		];
	}
}
