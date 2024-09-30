<?php
/**
 * The main plugin file.
 *
 * @package SnapWP\Helper
 */

declare(strict_types=1);

namespace SnapWP\Helper;

use SnapWP\Helper\Traits\Singleton;

if ( ! class_exists( 'SnapWP\Helper\Main' ) ) :
	/**
	 * Class - Main
	 */
	final class Main {
		use Singleton;

		/**
		 * Get the instance of this class.
		 */
		public static function instance(): self {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->setup();

				/**
				 * Fires after the main plugin class has been initialized.
				 *
				 * @param self $instance The main plugin class instance.
				 */
				do_action( 'snapwp_helper/init', self::$instance );
			}

			return self::$instance;
		}

		/**
		 * Setup the plugin.
		 */
		private function setup(): void {
			$this->load();

			// Maybe display a notice if the plugin dependencies are not met.
			$this->check_dependencies();
		}

		/**
		 * Load the plugin classes.
		 */
		public function load(): void {
			$instances = [];

			// Get the class slits.
			$class_names = array_merge(
				$this->get_module_classes(),
			);

			// Loop through all the classes, instantiate them, and register any hooks.
			foreach ( $class_names as $class_name ) {
				// Modules handle hooks in the init method.
				if ( is_subclass_of( $class_name, Interfaces\Module::class ) ) {
					$instance                       = new $class_name();
					$instances[ $instance->name() ] = $instance;
					$instances[ $instance->name() ]->init();

					continue;
				}

				// Registerables handle hooks in the register_hooks method.
				if ( is_subclass_of( $class_name, Interfaces\Registrable::class ) ) {
					$instances[ $class_name ] = new $class_name();
					$instances[ $class_name ]->register_hooks();

					continue;
				}

				// If the class does not implement either required interface, log an error.
				_doing_it_wrong(
					esc_html( $class_name ),
					esc_html__( 'The class should implement either the Module or Registrable interface.', 'snapwp-helper' ),
					'0.0.1'
				);
			}
		}

		/**
		 * Get the module classes to load.
		 *
		 * @return class-string<\SnapWP\Helper\Interfaces\Module>[] List of modules.
		 */
		private function get_module_classes(): array {
			$modules = [
				Modules\Assets::class,
				Modules\Admin::class,
				Modules\EnvGenerator::class,
				Modules\GraphQL::class,
				Modules\PluginUpdater::class,
			];

			/**
			 * Filter the list of modules to load.
			 *
			 * @param class-string<\SnapWP\Helper\Interfaces\Module>[] $modules List of modules to load.
			 */
			return (array) apply_filters( 'snapwp_helper/init/module_classes', $modules );
		}

		/**
		 * Check if the plugin dependencies are met.
		 */
		private function check_dependencies(): void {
			Dependencies::instance()->init();
			Dependencies::instance()->check_and_display_admin_notice();
		}
	}
endif;
