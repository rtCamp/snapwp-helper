<?php
/**
 * Abstract class to make it easy to register Enum types to WPGraphQL.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Enum
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Enum;

use SnapWP\Helper\Modules\GraphQL\Type\AbstractType;

/**
 * Class - AbstractEnum
 */
abstract class AbstractEnum extends AbstractType {
	/**
	 * Gets the Enum values configuration array.
	 *
	 * @return array<string,array{description:string,value:mixed,deprecationReason?:string}>
	 */
	abstract protected function get_values(): array;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		register_graphql_enum_type( static::get_type_name(), $this->get_type_config() );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_type_config(): array {
		$config = parent::get_type_config();

		$config['values'] = static::get_values();

		return $config;
	}
}
