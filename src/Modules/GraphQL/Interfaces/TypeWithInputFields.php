<?php
/**
 * Interface for for classes that register a GraphQL type with input fields to the GraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Interfaces
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Interfaces;

/**
 * Interface - TypeWithInputFields.
 */
interface TypeWithInputFields extends GraphQLType {
	/**
	 * Gets the input fields for the type.
	 *
	 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:string}>
	 */
	public function get_fields(): array;
}
