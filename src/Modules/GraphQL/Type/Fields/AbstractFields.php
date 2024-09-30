<?php
/**
 * Abstract class to make it easy to register GraphQL fields to an existing GrahQLType.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

use SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType;
use SnapWP\Helper\Modules\GraphQL\Interfaces\TypeWithFields;

/**
 * Class - AbstractFields
 */
abstract class AbstractFields implements GraphQLType, TypeWithFields {
	/**
	 * Defines the GraphQL type name registered in WPGraphQL.
	 */
	abstract public static function get_type_name(): string;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		register_graphql_fields( static::get_type_name(), $this->get_fields() );
	}
}
