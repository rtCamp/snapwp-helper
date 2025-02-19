<?php
/**
 * Registers custom fields to the GraphQL EnqueuedScript Object.
 *
 * @todo Temporary (non-conflicting) backport until supported by WPGraphQL.
 *
 * @see https://github.com/wp-graphql/wp-graphql/pull/3196
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
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
	 * Register fields to the Type.
	 */
	public function register(): void {
		// Early return if WPGraphQL will register the field itself.
		if ( defined( 'WPGRAPHQL_VERSION' ) && version_compare( WPGRAPHQL_VERSION, '1.30.0', '>=' ) ) {
			return;
		}

		parent::register();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'groupLocation' => [
				'type'        => 'String',
				'description' => __( 'The location where this script should be loaded', 'snapwp-helper' ),
				'resolve'     => static function ( \_WP_Dependency $script ) {
					if ( isset( $script->extra['group'] ) && 1 === (int) $script->extra['group'] ) {
						return 'footer';
					}
					return 'header';
				},
			],
		];
	}
}
