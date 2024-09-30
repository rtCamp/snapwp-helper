<?php
/**
 * Interface for for classes that register a GraphQL type with fields to the GraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Interfaces
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Interfaces;

/**
 * Interface - TypeWithFields.
 */
interface TypeWithFields extends GraphQLType {
	/**
	 * Gets the fields for the type.
	 *
	 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,args?:array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:mixed}>,resolve?:callable,deprecationReason?:string}>
	 */
	public function get_fields(): array;
}
