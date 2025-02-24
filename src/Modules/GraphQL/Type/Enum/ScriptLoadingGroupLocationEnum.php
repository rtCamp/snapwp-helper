<?php
/**
 * Registers the `ScriptLoadingGroupLocationEnum` to the WPGraphQL Schema.
 *
 * @todo Remove once WPGraphQL Core >= 1.30.0 is required.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Enum;

/**
 * Class - ScriptLoadingGroupLocationEnum
 */
final class ScriptLoadingGroupLocationEnum extends AbstractEnum {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'ScriptLoadingGroupLocationEnum';
	}

	/**
	 * {@inheritDoc}
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
	public function get_description(): string {
		return __( 'The location where the script should be loaded.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_values(): array {
		return [
			'HEADER' => [
				'name'        => 'HEADER',
				'value'       => 'HEADER',
				'description' => __( 'Script is loaded in the header', 'snapwp-helper' ),
			],
			'FOOTER' => [
				'name'        => 'FOOTER',
				'value'       => 'FOOTER',
				'description' => __( 'Script is loaded in the footer', 'snapwp-helper' ),
			],
		];
	}
}
