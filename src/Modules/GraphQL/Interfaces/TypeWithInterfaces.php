<?php
/**
 * Interface for for classes that register a GraphQL type with input fields to the GraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Interfaces
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Interfaces;

/**
 * Interface - TypeWithInterfaces.
 */
interface TypeWithInterfaces extends GraphQLType {
	/**
	 * Gets the array of GraphQL interfaces that should be applied to the type.
	 *
	 * @return string[]
	 */
	public function get_interfaces(): array;
}
