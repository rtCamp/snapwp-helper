<?php
/**
 * Registers the BlockMediaItemConnection type to the WPGraphQL Schema
 *
 * @TODO temporary solution until the WPGraphQL Content Blocks plugin supports this natively.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Connection
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Connection;

use SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;

/**
 * Class - ConnectionType
 *
 * @phpstan-type ConnectionConfig array{fromType:string,
 *   fromFieldName: string,
 *   resolve: callable,
 *   oneToOne?: bool,
 *   toType?: string,
 *   connectionArgs?: array<string,array{
 *     type: string|array<string,string | array<string,string>>,
 *     description: string,
 *     defaultValue?: mixed
 *   }>,
 *   connectionFields?: array<string,array{
 *     type: string|array<string,string | array<string,string>>,
 *     description: string,
 *     args?: array<string,array{
 *       type: string|array<string,string | array<string,string>>,
 *       description: string,
 *       defaultValue?: mixed,
 *     }>,
 *     resolve?: callable,
 *     deprecationReason?: string,
 *   }>,
 * }
 */
class BlockMediaItemConnection implements GraphQLType {
	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			register_graphql_connection( $this->get_connection_config( $block_type ) );
		}
	}

	/**
	 * Get the block types to connect from.
	 *
	 * @return string[]
	 */
	private function get_block_types(): array {
		return [
			'CoreCover',
			'CoreImage',
			'CoreMediaText',
		];
	}

	/**
	 * Gets the $config array used to register the connection to the GraphQL type.
	 *
	 * @param string $from_type The GraphQL type name to connect from.
	 *
	 * @return ConnectionConfig
	 */
	protected function get_connection_config( string $from_type ): array {
		return array_merge(
			[
				'fromType'      => $from_type,
				'toType'        => 'MediaItem',
				'fromFieldName' => 'connectedMediaItem',
				'oneToOne'      => true,
				'resolve'       => static function ( $source, $args, $context, $info ) {
					// Most blocks (Image, Cover) use the id attribute.
					$media_id = ! empty( $source['attrs']['id'] ) ? (int) $source['attrs']['id'] : null;

					// Fallback to mediaId if id is not set (e.g. MediaText).
					if ( empty( $media_id ) ) {
						$media_id = ! empty( $source['attrs']['mediaId'] ) ? (int) $source['attrs']['mediaId'] : null;
					}

					// If no media ID is found, try to get the post thumbnail ID (e.g. Cover, MediaText).
					if ( empty( $media_id ) && ! empty( $source['attrs']['useFeaturedImage'] ) ) {
						$media_id = get_post_thumbnail_id();
					}

					// Bail early if no media ID is found.
					if ( empty( $media_id ) ) {
						return null;
					}

					$args['where']['id'] = $media_id;

					$resolver = new PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );

					return $resolver->one_to_one()->get_connection();
				},
			],
		);
	}
}
