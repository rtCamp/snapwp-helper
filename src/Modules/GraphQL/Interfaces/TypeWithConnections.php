<?php
/**
 * Interface for for classes that register a GraphQL type with connections to the GraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Interfaces
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Interfaces;

/**
 * Interface - TypeWithConnections
 */
interface TypeWithConnections extends GraphQLType {
	/**
	 * Gets the properties for the type.
	 *
	 * @return array<string,array{toType:string,description:string,args?:array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:mixed}>,connectionInterfaces?:string[],oneToOne?:bool,resolve?:callable}>
	 */
	public function get_connections(): array;
}
