<?php
/**
 * Registers GraphQL types to the WPGraphQL Schema
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL;

use SnapWP\Helper\Interfaces\Registrable;
use SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType;
use SnapWP\Helper\Modules\GraphQL\Type\Connection;
use SnapWP\Helper\Modules\GraphQL\Type\Enum;
use SnapWP\Helper\Modules\GraphQL\Type\Fields;
use SnapWP\Helper\Modules\GraphQL\Type\WPObject;
use SnapWP\Helper\Traits\Singleton;
use WPGraphQL\AppContext;

/**
 * Class - TypeRegistry
 */
final class TypeRegistry implements Registrable {
	use Singleton;

	/**
	 * The local registry of registered types.
	 *
	 * @var array<class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>,\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>
	 */
	protected array $registry = [];

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( get_graphql_register_action(), [ $this, 'init' ] );
		add_filter( 'graphql_data_loaders', [ $this, 'register_data_loaders' ], 10, 2 );
	}

	/**
	 * Gets an array of all the registered GraphQL types along with their instances.
	 *
	 * @return array<class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>,\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>
	 */
	public function get_registered_types(): array {
		if ( empty( $this->registry ) ) {
			$this->initialize_registry();
		}

		return $this->registry;
	}

	/**
	 * Registers types, connections, unions, and mutations to GraphQL schema.
	 */
	public function init(): void {
		/**
		 * Fires before any types have been registered.
		 */
		do_action( 'snapwp_helper/graphql/init/register_types' );

		$this->initialize_registry();

		/**
		 * Fires after all types have been registered.
		 */
		do_action( 'snapwp_helper/graphql/init/after_register_types' );
	}

	/**
	 * Registers custom data loaders.
	 *
	 * @param array<string,\WPGraphQL\Data\Loader\AbstractDataLoader> $data_loaders The data loaders.
	 * @param \WPGraphQL\AppContext                                   $context      The AppContext object.
	 *
	 * @return array<string,\WPGraphQL\Data\Loader\AbstractDataLoader>
	 */
	public function register_data_loaders( array $data_loaders, AppContext $context ): array {
		$data_loaders['script_module'] = new Data\Loader\ScriptModuleLoader( $context );

		return $data_loaders;
	}

	/**
	 * Initializes the plugin type registry.
	 */
	private function initialize_registry(): void {
		$classes_to_register = array_merge(
			$this->enums(),
			$this->inputs(),
			$this->interfaces(),
			$this->objects(),
			$this->connections(),
			$this->mutations(),
			$this->fields(),
		);

		$this->register_types( $classes_to_register );
	}

	/**
	 * List of Enum classes to register.
	 *
	 * @return string[]
	 */
	private function enums(): array {
		// Enums to register.
		$classes_to_register = [
			Enum\ScriptModuleImportTypeEnum::class,
		];

		/**
		 * Filters the list of enum classes to register.
		 *
		 * Useful for adding/removing specific enums to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_enum_classes', $classes_to_register );
	}

	/**
	 * List of Input classes to register.
	 *
	 * @return string[]
	 */
	private function inputs(): array {
		$classes_to_register = [];

		/**
		 * Filters the list of input classes to register.
		 *
		 * Useful for adding/removing specific inputs to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_input_classes', $classes_to_register );
	}

	/**
	 * List of Interface classes to register.
	 *
	 * @return string[]
	 */
	private function interfaces(): array {
		$classes_to_register = [];

		/**
		 * Filters the list of interfaces classes to register.
		 *
		 * Useful for adding/removing specific interfaces to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register = Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_interface_classes', $classes_to_register );
	}

	/**
	 * List of Object classes to register.
	 *
	 * @return string[]
	 */
	private function objects(): array {
		$classes_to_register = [
			WPObject\FontFace::class,
			WPObject\GlobalStyles::class,
			WPObject\ScriptModuleDependency::class,
			WPObject\ScriptModule::class,
			WPObject\RenderedTemplate::class,
		];

		/**
		 * Filters the list of object classes to register.
		 *
		 * Useful for adding/removing specific objects to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register = Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_object_classes', $classes_to_register );
	}

	/**
	 * List of Field classes to register.
	 *
	 * @return string[]
	 */
	private function fields(): array {
		$classes_to_register = [
			Fields\RootQuery::class,
			Fields\EnqueuedScript::class,
			Fields\CoreCover::class,
			Fields\CoreMediaText::class,
		];

		/**
		 * Filters the list of field classes to register.
		 *
		 * Useful for adding/removing specific fields to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register = Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_field_classes', $classes_to_register );
	}

	/**
	 * List of Connection classes to register.
	 *
	 * @return string[]
	 */
	private function connections(): array {
		$classes_to_register = [
			Connection\BlockMediaItemConnection::class,
		];

		/**
		 * Filters the list of connection classes to register.
		 *
		 * Useful for adding/removing specific connections to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register = Array of classes to be registered to the schema.
		 */
		return apply_filters( 'snapwp_helper/graphql/init/registered_connection_classes', $classes_to_register );
	}

	/**
	 * Registers mutation.
	 *
	 * @return string[]
	 */
	private function mutations(): array {
		$classes_to_register = [];

		/**
		 * Filters the list of connection classes to register.
		 *
		 * Useful for adding/removing specific connections to the schema.
		 *
		 * @param class-string<\SnapWP\Helper\Modules\GraphQL\Interfaces\GraphQLType>[] $classes_to_register = Array of classes to be registered to the schema.
		 */
		$classes_to_register = apply_filters( 'snapwp_helper/graphql/init/registered_mutation_classes', $classes_to_register );

		return $classes_to_register;
	}

	/**
	 * Loops through a list of classes to manually register each GraphQL to the registry, and stores the type name and class in the local registry.
	 *
	 * Classes must implement Interfaces\GraphQLType.
	 *
	 * @param string[] $classes_to_register .
	 *
	 * @throws \Exception .
	 */
	private function register_types( array $classes_to_register ): void {
		// Bail if there are no classes to register.
		if ( empty( $classes_to_register ) ) {
			return;
		}

		foreach ( $classes_to_register as $class ) {
			if ( ! is_a( $class, GraphQLType::class, true ) ) {
				// translators: PHP class.
				throw new \Exception(
					sprintf(
						// translators: 1: Class to be registered, 2: PHP Interface to implement.
						esc_html__( 'To be registered to the WPGraphQL schema, %1$s needs to implement %2$s', 'snapwp-helper' ),
						esc_html( $class ),
						esc_html( GraphQLType::class ),
					)
				);
			}

			// Register the type to the GraphQL schema.
			$instance = new $class();
			$instance->register();

			// Store the type in the local registry.
			$this->registry[ $class ] = $instance;
		}
	}
}
