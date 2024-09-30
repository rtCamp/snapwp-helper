<?php
/**
 * Checks for 3rd party plugin dependencies.
 *
 * @package SnapWP\Helper
 */

declare(strict_types=1);

namespace SnapWP\Helper;

use SnapWP\Helper\Traits\Singleton;

/**
 * Class - Dependencies
 *
 * This class is meant to be consumed by other classes using the static methods on this class.
 *
 * - self::register_dependency( $args ) - Registers a plugin dependency. This should be called outside of any hooks, before the Dependencies class is constructed.
 * - self::is_dependency_met( $slug ) - Checks whether the dependency requirements are met.
 *
 * Internally, all the dependencies added via `register_dependency` are combined when the class is initialized.
 * Dependencies are then checked when `is_dependency_met` is first called, and stored in the class for future reference.
 */
final class Dependencies {
	use Singleton;

	/**
	 * The array of plugin dependencies, keyed by the plugin slug.
	 *
	 * @var array<string,array{slug:string,name:string,check_callback:callable():(true|\WP_Error)}>
	 */
	protected $dependencies = [];

	/**
	 * The resolved dependencies.
	 *
	 * @var array<string,array{slug:string,name:string,result:true|\WP_Error}>
	 */
	protected $resolved_dependencies = [];

	/**
	 * Registers a plugin dependency.
	 *
	 * This should be called before the class is constructed.
	 *
	 * @param array{slug:string,name:string,check_callback:callable():(true|\WP_Error)} $args The dependency arguments.
	 *
	 * @throws \LogicException If the dependencies are registered after the class is constructed.
	 */
	public static function register_dependency( array $args ): void {
		// Validate the dependency arguments.
		self::validate_dependency_args( $args );

		// Add the dependency to the list of dependencies.
		add_filter(
			'snapwp_helper/dependencies/registered_dependencies',
			static function ( array $dependencies ) use ( $args ): array {
				$slug           = $args['slug'];
				$name           = $args['name'];
				$check_callback = $args['check_callback'];

				$dependencies[ $slug ] = [
					'slug'           => $slug,
					'name'           => $name,
					'check_callback' => $check_callback,
				];

				return $dependencies;
			}
		);
	}

	/**
	 * Checks whether the dependency requirements is met.
	 *
	 * @param string $slug The plugin slug.
	 */
	public static function is_dependency_met( string $slug ): bool {
		$dep_to_check = self::instance()->get_resolved_dependency( $slug );

		return true === $dep_to_check['result'];
	}

	/**
	 * Initializes the class.
	 */
	public function init(): void {
		$this->dependencies = $this->register_dependencies();
	}

	/**
	 * Registers the plugin dependencies.
	 *
	 * @return array<string,array{slug:string,name:string,check_callback:callable():(true|\WP_Error)}>
	 */
	protected function register_dependencies(): array {
		/**
		 * Filters the plugin dependencies.
		 *
		 * @param array<string,array{slug:string,name:string,check_callback:callable():(true|\WP_Error)}> $dependencies The plugin dependencies.
		 */
		$dependencies = apply_filters( 'snapwp_helper/dependencies/registered_dependencies', [] );

		return is_array( $dependencies ) ? $dependencies : [];
	}

	/**
	 * Runs the dependency checks to see if the requirements are met.
	 *
	 * @param array{slug:string,name:string,check_callback:callable():(true|\WP_Error)} $args The plugin dependencies.
	 * @return array{slug:string,name:string,result:true|\WP_Error}
	 */
	protected function resolve_dependency( array $args ): array {
		$result = null;

		// Run the check callback.
		try {
			$result = $args['check_callback']();
		} catch ( \Throwable $e ) {
			$result = new \WP_Error(
				'dependency_check_failed',
				sprintf(
					// translators: %1$s: Plugin name, %2$s: Error message.
					esc_html__( 'The check for the %1$s plugin failed. Error: %2$s', 'snapwp-helper' ),
					esc_html( $args['name'] ),
					esc_html( $e->getMessage() ),
				),
			);
		}

		// Add the result to the resolved dependencies.
		$this->resolved_dependencies[ $args['slug'] ] = [
			'slug'   => $args['slug'],
			'name'   => $args['name'],
			'result' => $result,
		];

		return $this->resolved_dependencies[ $args['slug'] ];
	}

	/**
	 * Get a resolved dependency.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return array{slug:string,name:string,result:true|\WP_Error}
	 * @throws \InvalidArgumentException If the dependency is not found.
	 */
	protected function get_resolved_dependency( string $slug ): array {
		$dep_to_check = $this->resolved_dependencies[ $slug ] ?? null;

		if ( null === $dep_to_check ) {
			// Bail early if the dependency is not found.
			if ( ! isset( $this->dependencies[ $slug ] ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						// translators: %s: Plugin slug.
						esc_html__( 'The plugin %s is not listed as a dependency.', 'snapwp-helper' ),
						esc_html( $slug )
					)
				);
			}

			$dep_to_check = $this->resolve_dependency( $this->dependencies[ $slug ] );
		}

		return $dep_to_check;
	}

	/**
	 * Gets the dependency error message.
	 *
	 * Returns null if the dependency is met.
	 *
	 * @param string $slug The plugin slug.
	 */
	public function get_dependency_error_message( string $slug ): ?string {
		$dep_to_check = $this->get_resolved_dependency( $slug );

		return $dep_to_check['result'] instanceof \WP_Error ? $dep_to_check['result']->get_error_message() : null;
	}

	/**
	 * Checks the dependencies and display an admin notice if any are not met.
	 */
	public function check_and_display_admin_notice(): void {
		$hooks = [
			'admin_notices',
			'network_admin_notices',
		];

		foreach ( $hooks as $hook ) {
			add_action(
				$hook,
				function (): void {
					$are_dependencies_met = $this->resolve_all_dependencies();

					if ( $are_dependencies_met ) {
						return;
					}

					// Display the admin notice.
					?>
					<div class="notice notice-error">
						<p>
							<?php
							echo wp_kses_post(
								sprintf(
									// translators: %s: Plugin name.
									esc_html__( 'The following plugins are required for the %s plugin to work correctly:', 'snapwp-helper' ),
									'SnapWP Helper'
								)
							);
							?>
						</p>
						<ul>
							<?php foreach ( $this->resolved_dependencies as $dep ) : ?>
								<?php
								$error_message = $this->get_dependency_error_message( $dep['slug'] );

								if ( null === $error_message ) {
									continue;
								}
								?>

								<li>
									<strong>
										<?php
										// translators: %s: Plugin name.
										printf( '%s: ', esc_html( $dep['name'] ) );
										?>
									</strong>
									<?php echo esc_html( $error_message ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
				}
			);
		}
	}

	/**
	 * Checks all dependencies and returns whether all are met.
	 */
	protected function resolve_all_dependencies(): bool {
		$registered_dependencies = $this->dependencies;

		$all_met = true;
		foreach ( array_keys( $registered_dependencies ) as $slug ) {
			if ( ! self::is_dependency_met( $slug ) ) {
				// We don't return early here so that we can check all dependencies.
				$all_met = false;
			}
		}

		return $all_met;
	}

	/**
	 * Validates the dependency arguments.
	 *
	 * @param array{slug:string,name:string,check_callback:callable():(true|\WP_Error)} $args The dependency arguments.
	 *
	 * @throws \InvalidArgumentException If the arguments are invalid.
	 */
	protected static function validate_dependency_args( array $args ): void {
		if ( empty( $args['slug'] ) ) {
			throw new \InvalidArgumentException( 'The plugin slug cannot be empty.' );
		}

		if ( empty( $args['name'] ) ) {
			throw new \InvalidArgumentException( 'The plugin name cannot be empty.' );
		}

		if ( ! is_callable( $args['check_callback'] ) ) {
			throw new \InvalidArgumentException( 'The check callback must be callable.' );
		}
	}
}
