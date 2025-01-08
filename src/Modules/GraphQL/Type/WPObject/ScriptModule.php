<?php
/**
 * Registers the ScriptModule Object to WPGraphQL.
 *
 * Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\WPObject
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use SnapWP\Helper\Modules\GraphQL\Interfaces\TypeWithInterfaces;

/**
 * Class - ScriptModule
 */
final class ScriptModule extends AbstractObject implements TypeWithInterfaces {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'ScriptModule';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'The Script Module enqueued by WordPress.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'dependencies' => [
				'type'        => [ 'list_of' => ScriptModuleDependency::get_type_name() ],
				'description' => __( 'The dependencies for the script module.', 'snapwp-helper' ),
			],
			'extraData'    => [
				'type'        => 'String',
				'description' => __( 'The (JSON-encoded) data object used by the script module.', 'snapwp-helper' ),
			],
			'handle'       => [
				'type'        => 'String',
				'description' => __( 'The handle for the script module.', 'snapwp-helper' ),
			],
			'src'          => [
				'type'        => 'String',
				'description' => __( 'The source URL for the script module.', 'snapwp-helper' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_interfaces(): array {
		return [ 'Node' ];
	}
}
