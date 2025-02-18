<?php
/**
 * Registers custom fields to the GraphQL EnqueuedScript Object.
 *
 * Temporary (non-conflicting) backport until supported by WPGraphQL.
 *
 * @see https://github.com/wp-graphql/wp-graphql/pull/3196
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

/**
 * Class - EnqueuedScript
 */
final class EnqueuedScript extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'EnqueuedScript';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		$field_resolver = static function ( \_WP_Dependency $script ) {
			if ( isset( $script->extra['group'] ) && 1 === (int) $script->extra['group'] ) {
				return 'footer';
			}
			return 'header';
		};

		if ( defined( 'WPGRAPHQL_VERSION' ) && version_compare( WPGRAPHQL_VERSION, '1.30.0', '>=' ) ) {
			return [
				'groupLocation' => [
					'type'        => 'String',
					'description' => __( 'The location where this script should be loaded', 'snapwp-helper' ),
					'resolve'     => $field_resolver,
				],
			];
		}

		return [
			'location' => [
				'type'        => 'String',
				'description' => __( 'The location where this script should be loaded', 'snapwp-helper' ),
				'resolve'     => $field_resolver,
			],
		];
	}
}
