<?php
/**
 * Registers the ScriptModuleDependency Object to WPGraphQL.
 *
 * Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\WPObject
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use WPGraphQL\AppContext;

/**
 * Class - ScriptModuleDependency
 */
final class ScriptModuleDependency extends AbstractObject {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'ScriptModuleDependency';
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
			'importType'            => [
				'type'        => 'String', // @todo make enum.
				'description' => __( 'The import type for the dependency. Either `static` or `dynamic`.', 'snapwp-helper' ),
				'resolve'     => static function ( $source ) {
					return $source['import'] ?? 'static';
				},
			],
			'connectedScriptModule' => [
				'type'        => ScriptModule::get_type_name(),
				'description' => __( 'The script module.', 'snapwp-helper' ),
				'resolve'     => static function ( $source, $args, AppContext $context ) {
					$script_module_loader = $context->get_loader( 'script_module' );
					return $script_module_loader->load_deferred( $source['id'] );
				},
			],
		];
	}
}
