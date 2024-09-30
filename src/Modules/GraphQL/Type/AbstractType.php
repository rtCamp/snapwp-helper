<?php
/**
 * Abstract class to make it easy to register Types to WPGraphQL.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Type;

use SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType;

/**
 * Class - AbstractType
 */
abstract class AbstractType implements GraphQLType {
	/**
	 * Defines the GraphQL type name registered in WPGraphQL.
	 */
	abstract public static function get_type_name(): string;

	/**
	 * Gets the GraphQL type description.
	 */
	abstract public function get_description(): string;

	/**
	 * Gets the $config array used to register the type to WPGraphQL.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_type_config(): array {
		return [
			'description'     => $this->get_description(),
			'eagerlyLoadType' => $this->should_load_eagerly(),
		];
	}

	/**
	 * Whether the type should be loaded eagerly by WPGraphQL. Defaults to false.
	 *
	 * Eager load should only be necessary for types that are not referenced directly (e.g. in Unions, Interfaces ).
	 */
	protected function should_load_eagerly(): bool {
		return false;
	}
}
