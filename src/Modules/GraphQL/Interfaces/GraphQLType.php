<?php
/**
 * Interface for GraphQL types.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Interfaces
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Interfaces;

/**
 * Interface - GraphQLType
 */
interface GraphQLType {
	/**
	 * Registers the type to the WPGraphQL Schema.
	 */
	public function register(): void;
}
