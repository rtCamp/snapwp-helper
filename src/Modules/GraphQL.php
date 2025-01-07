<?php
/**
 * Registers WPGraphQL functionality.
 *
 * @package SnapWP\Helper\Modules
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules;

use SnapWP\Helper\Dependencies;
use SnapWP\Helper\Interfaces\Module;
use SnapWP\Helper\Modules\GraphQL\SchemaFilters;
use SnapWP\Helper\Modules\GraphQL\TypeRegistry;
use SnapWP\Helper\Modules\GraphQL\TokenManager;

/**
 * Class - GraphQL
 */
class GraphQL implements Module {
	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return 'graphql';
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		$this->register_dependencies();

		$this->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		/**
		 * Classes to register.
		 *
		 * @var array<class-string<\SnapWP\Helper\Interfaces\Registrable>>
		 */
		$classes_to_register = [
			SchemaFilters::class,
		];

		foreach ( $classes_to_register as $class ) {
			$class_instance = new $class();
			$class_instance->register_hooks();
		}

		// We only want to register our types After WPGraphQL has been initialized.
		add_action(
			'graphql_init',
			static function () {
				if ( ! Dependencies::is_dependency_met( 'wp-graphql' ) ) {
					return;
				}

				TypeRegistry::instance()->register_hooks();
			}
		);

		add_action( 'do_graphql_request', [ $this, 'check_introspection_token' ] );
	}

	/**
	 * Register the dependencies for the module.
	 */
	protected function register_dependencies(): void {
		$dependency_args = [
			$this->get_wpgraphql_dependency_args(),
			$this->get_wpgraphql_content_blocks_dependency_args(),
		];

		foreach ( $dependency_args as $args ) {
			Dependencies::register_dependency( $args );
		}
	}

	/**
	 * Get the WPGraphQL dependency args.
	 *
	 * @return array{slug:string,name:string,check_callback:callable():(true|\WP_Error)}
	 */
	protected function get_wpgraphql_dependency_args(): array {
		$minimum_version = '1.28.0';

		return [
			'slug'           => 'wp-graphql',
			'name'           => __( 'WPGraphQL', 'snapwp-helper' ),
			'check_callback' => static function () use ( $minimum_version ) {
				// WPGraphQL must be active.
				if ( ! class_exists( 'WPGraphQL' ) || ! defined( 'WPGRAPHQL_VERSION' ) ) {
					return new \WP_Error(
						'wp-graphql-not-active',
						__( 'WPGraphQL is not active.', 'snapwp-helper' )
					);
				}

				if ( version_compare( WPGRAPHQL_VERSION, $minimum_version, '<' ) ) {
					return new \WP_Error(
						'wp-graphql-version-too-old',
						sprintf(
							/* translators: 1: WPGraphQL version, 2: Minimum WPGraphQL version */
							__( 'WPGraphQL version %1$s is too old. Please update to version %2$s or higher.', 'snapwp-helper' ),
							WPGRAPHQL_VERSION,
							$minimum_version
						)
					);
				}

				return true;
			},
		];
	}

	/**
	 * Get the WPGraphQL Content Blocks dependency args.
	 *
	 * @return array{slug:string,name:string,check_callback:callable():(true|\WP_Error)}
	 */
	protected function get_wpgraphql_content_blocks_dependency_args(): array {
		$minimum_version = '4.3.2';

		return [
			'slug'           => 'wp-graphql-content-blocks',
			'name'           => __( 'WPGraphQL Content Blocks', 'snapwp-helper' ),
			'check_callback' => static function () use ( $minimum_version ) {
				// WPGraphQL Content Blocks must be active.
				if ( ! class_exists( 'WPGraphQLContentBlocks' ) || ! defined( 'WPGRAPHQL_CONTENT_BLOCKS_VERSION' ) ) {
					return new \WP_Error(
						'wp-graphql-content-blocks-not-active',
						__( 'WPGraphQL Content Blocks is not active.', 'snapwp-helper' )
					);
				}

				if ( version_compare( WPGRAPHQL_CONTENT_BLOCKS_VERSION, $minimum_version, '<' ) ) {
					return new \WP_Error(
						'wp-graphql-content-blocks-version-too-old',
						sprintf(
							/* translators: 1: WPGraphQL Content Blocks version, 2: Minimum WPGraphQL Content Blocks version */
							__( 'WPGraphQL Content Blocks version %1$s is too old. Please update to version %2$s or higher.', 'snapwp-helper' ),
							WPGRAPHQL_CONTENT_BLOCKS_VERSION,
							$minimum_version
						)
					);
				}

				return true;
			},
		];
	}

	/**
	 * Check for the Introspection token and allow introspection if the token matches.
	 * 
	 * @return bool True if the token matches, false otherwise.
	 */
	protected function check_introspection_token() : bool {
		// Get the decrypted introspection token from the database.
		$introspection_token = TokenManager::get_token();

		if ( ! $introspection_token ) {
			wp_send_json_error(['message' => 'No token found.']);
		}

		$headers = getallheaders();

		// Check if the Authorization header is set and the token matches.
		if ( isset( $headers['Authorization'] ) && $headers['Authorization'] === "Bearer {$introspection_token}" ) {
			return true;
		}

		// Reject the request if the token does not match.
		wp_send_json_error(['message' => 'Invalid token']);
	}
}
