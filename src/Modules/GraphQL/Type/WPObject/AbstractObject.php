<?php
/**
 * Abstract class to make it easy to register Object types to WPGraphQL.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\WPObject
 */

declare( strict_types=1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use SnapWP\Helper\Modules\GraphQL\Interfaces\TypeWithFields;
use SnapWP\Helper\Modules\GraphQL\Type\AbstractType;

/**
 * Class - AbstractObject
 */
abstract class AbstractObject extends AbstractType implements TypeWithFields {
	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		register_graphql_object_type( static::get_type_name(), $this->get_type_config() );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_type_config(): array {
		$config = parent::get_type_config();

		$config['fields'] = $this->get_fields();

		if ( method_exists( $this, 'get_connections' ) ) {
			$config['connections'] = $this->get_connections();
		}

		if ( method_exists( $this, 'get_interfaces' ) ) {
			$config['interfaces'] = $this->get_interfaces();
		}

		return $config;
	}
}
