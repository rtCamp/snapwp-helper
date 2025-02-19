<?php
/**
 * Registers custom fields to the GraphQL CoreMediaText Object.
 *
 * @todo: Temporary (non-conflicting) backport until supported by WPGraphQL Content Blocks.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

/**
 * Class - CoreMediaText
 */
final class CoreMediaText extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'CoreMediaText';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'mediaDetails' => [
				'type'        => 'MediaDetails',
				'description' => sprintf(
					// translators: %s is the block type name.
					__( 'Media Details of the %s Block Type', 'snapwp-helper' ),
					self::get_type_name(),
				),
				'resolve'     => static function ( $block ) {
					$attrs = $block['attrs'];
					$id    = $attrs['mediaId'] ?? null;

					// @TODO: This is necessary becasue WPGraphQL Content Blocks doesn't hydrate from globals.
					if ( empty( $id ) && ! empty( $attrs['useFeaturedImage'] ) ) {
						$id = get_post_thumbnail_id();
					}

					if ( $id ) {
						$media_details = wp_get_attachment_metadata( $id );
						if ( ! empty( $media_details ) ) {
							$media_details['ID'] = $id;

							return $media_details;
						}
					}
					return null;
				},
			],
		];
	}
}
