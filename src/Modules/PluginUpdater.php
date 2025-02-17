<?php
/**
 * Plugin Update Checker module to define an array of plugins to check for updates.
 *
 * @package SnapWP\Helper\Modules
 */

namespace SnapWP\Helper\Modules;

use SnapWP\Helper\Interfaces\Module;
use SnapWP\Helper\Modules\PluginUpdater\UpdateChecker;

/**
 * PluginUpdater class.
 */
class PluginUpdater implements Module {
	/**
	 * The instance of the UpdateChecker class.
	 *
	 * @var \SnapWP\Helper\Modules\PluginUpdater\UpdateChecker
	 */
	private UpdateChecker $update_checker;

	/**
	 * Retrieve the plugins to check for updates and array with filters applied.
	 *
	 * @return array{slug:string,file_path:string,update_uri:string}[]
	 */
	private function get_plugins(): array {
		$plugins = [
			[
				'slug'       => 'snapwp-helper',
				'file_path'  => 'snapwp-helper/snapwp-helper.php',
				'update_uri' => 'https://github.com/rtCamp/snapwp-helper',
			],
			[
				'slug'       => 'wpgraphql-ide',
				'file_path'  => 'wpgraphql-ide/wpgraphql-ide.php',
				'update_uri' => 'https://github.com/wp-graphql/wpgraphql-ide',
			],
		];

		/**
		 * Filters the plugins that should be checked for updates.
		 *
		 * @param array{slug:string,file_path:string,update_uri:string}[] $plugins The plugins to check for updates.
		 */
		return apply_filters( 'snapwp_helper/plugin_updater/plugins', $plugins );
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return 'plugin-updater';
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'instantiate_update_checker' ] );
	}

	/**
	 * Instantiate the update checker on init.
	 */
	public function instantiate_update_checker(): void {
		if ( ! isset( $this->update_checker ) ) {
			$this->update_checker = new UpdateChecker( $this->get_plugins() );
		}

		$this->update_checker->init();
	}
}
