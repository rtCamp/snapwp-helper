<?php
/**
 * Registers the ScriptModuleDependency Object to WPGraphQL.
 *
 * @todo Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\WPObject
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use SnapWP\Helper\Modules\GraphQL\Type\Enum\ScriptModuleImportTypeEnum;
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
				'type'        => ScriptModuleImportTypeEnum::get_type_name(),
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
