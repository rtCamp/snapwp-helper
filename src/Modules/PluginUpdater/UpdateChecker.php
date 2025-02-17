<?php
/**
 * UpdateChecker class that serves as a wrapper API for instantiating PUC with an array configuration.
 *
 * @package SnapWP\Helper\PluginUpdater
 */

namespace SnapWP\Helper\Modules\PluginUpdater;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


/**
 * Class UpdateChecker
 */
class UpdateChecker {
	/**
	 * Array of plugin data.
	 *
	 * @var array{slug:string,file_path:string,update_uri:string}[]
	 */
	private array $plugin_data;

	/**
	 * Array of update checkers.
	 *
	 * @var array<string,\YahnisElsts\PluginUpdateChecker\v5p5\Plugin\UpdateChecker>
	 */
	private array $update_checkers = [];

	/**
	 * UpdateChecker constructor.
	 *
	 * @param array{slug:string,file_path:string,update_uri:string}[] $plugin_data Array of plugin data.
	 */
	public function __construct( array $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	/**
	 * Instantiate and initialize the update checkers.
	 */
	public function init(): void {
		foreach ( $this->plugin_data as $plugin ) {
			// Don't instantiate more than once.
			if ( isset( $this->update_checkers[ $plugin['slug'] ] ) ) {
				continue;
			}

			// Don't instantiate if the plugin isn't installed.
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['file_path'] ) ) {
				continue;
			}

			/**
			 * @var \YahnisElsts\PluginUpdateChecker\v5p5\Vcs\PluginUpdateChecker $update_checker
			 */
			$update_checker = PucFactory::buildUpdateChecker(
				$plugin['update_uri'],
				WP_PLUGIN_DIR . '/' . $plugin['file_path'],
				$plugin['slug']
			);

			// Check if the update checker is a VCS-based checker.
			$release_asset_path = '/' . preg_quote( $plugin['slug'], '/' ) . '\.zip/';

			/**
			 * @var \YahnisElsts\PluginUpdateChecker\v5p5\Vcs\GitHubApi $vcs_api
			 */
			$vcs_api = $update_checker->getVcsApi();
			$vcs_api->enableReleaseAssets( $release_asset_path );

			/**
			 * Store the instance.
			 */
			$this->update_checkers[ $plugin['slug'] ] = $update_checker;
		}
	}

	/**
	 * Get the update checkers.
	 *
	 * @return array<string,\YahnisElsts\PluginUpdateChecker\v5p5\Plugin\UpdateChecker>
	 */
	public function get_update_checkers(): array {
		return $this->update_checkers;
	}
}
