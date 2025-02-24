<?php
/**
 * Registers the `ScriptModuleImportTypeEnum` to the WPGraphQL Schema.
 *
 * @todo Remove once script modules are supported by WPGraphQL Core.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Enum;

/**
 * Class - ScriptModuleImportTypeEnum
 */
final class ScriptModuleImportTypeEnum extends AbstractEnum {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'ScriptModuleImportTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'The import type of a Script Module dependency.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/8a52d746e9bb85604f6a309a87d22296ce1c4280/src/wp-includes/class-wp-script-modules.php#L55
	 */
	public function get_values(): array {
		return [
			'DYNAMIC' => [
				'name'        => 'DYNAMIC',
				'value'       => 'dynamic',
				'description' => __( 'Dynamic import.', 'snapwp-helper' ),
			],
			'STATIC'  => [
				'name'        => 'STATIC',
				'value'       => 'static',
				'description' => __( 'Static import.', 'snapwp-helper' ),
			],
		];
	}
}
