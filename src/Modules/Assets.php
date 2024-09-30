<?php
/**
 * Registers assets (scripts/styles) to WordPress.
 *
 * @package SnapWP\Helper\Modules
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules;

use SnapWP\Helper\Interfaces\Module;

/**
 * Class - Assets
 */
class Assets implements Module {
	/**
	 * The prefix for the handle of the assets.
	 */
	public const HANDLE_PREFIX = 'snapwp-helper-';

	/**
	 * The handle for the admin script.
	 */
	public const ADMIN_SCRIPT_HANDLE = 'snapwp-helper-admin';

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return 'assets';
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
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
	}

	/**
	 * Register admin assets.
	 */
	public function register_admin_assets(): void {
		$this->register_asset( self::ADMIN_SCRIPT_HANDLE, 'admin' );
	}

	/**
	 * Register a script.
	 *
	 * @param  string           $handle   Name of the script. Should be unique.
	 * @param  string           $filename Path of the script relative to the assets/build/ directory, excluding the .js extension.
	 * @param 'script'|'style' $type      Optional. The type of asset to register. Default 'script'.
	 */
	private function register_asset( string $handle, string $filename, string $type = 'script' ): bool {
		// Bail if missing constants.
		if ( ! defined( 'SNAPWP_HELPER_PLUGIN_DIR' ) || ! defined( 'SNAPWP_HELPER_PLUGIN_URL' ) ) {
			return false;
		}

		$asset_file = ( (string) SNAPWP_HELPER_PLUGIN_DIR ) . "build/{$filename}.asset.php";

		// Bail if the asset file does not exist.
		if ( ! file_exists( $asset_file ) ) {
			return false;
		}

		$asset = require_once $asset_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- The file is checked for existence above.

		// Register as a script.
		if ( 'script' === $type ) {
			return wp_register_script(
				$handle,
				SNAPWP_HELPER_PLUGIN_URL . "build/{$filename}.js",
				$asset['dependencies'],
				$asset['version'],
				true
			);
		}

		// Register as a style.
		return wp_register_style(
			$handle,
			SNAPWP_HELPER_PLUGIN_URL . "build/{$filename}.css",
			$asset['dependencies'],
			$asset['version']
		);
	}
}
