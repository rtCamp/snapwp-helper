<?php
/**
 * Plugin Name: SnapWP - Helper
 * Plugin URI: https://github.com/rtCamp/snapwp-helper
 * GitHub Plugin URI: https://github.com/rtCamp/snapwp-helper
 * Description: Manages WPGraphQL extensions updates and discovery.
 * Author: rtCamp
 * Author URI: https://github.com/rtCamp
 * Update URI: https://github.com/rtCamp/snapwp-helper
 * Version: 0.2.3
 * Text Domain: snapwp-helper
 * Domain Path: /languages
 * Requires at least: 6.7
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WPGraphQL tested up to: 2.1.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package SnapWP\Helper
 * @author rtCamp
 * @license GPL-3
 */

declare(strict_types=1);

namespace SnapWP\Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! \SnapWP\Helper\Autoloader::autoload() ) {
	return;
}

/**
 * Define the plugin constants.
 */
function constants(): void {
	if ( ! defined( 'SNAPWP_HELPER_VERSION' ) ) {
		/**
		 * The plugin version.
		 *
		 * @const string
		 */
		define( 'SNAPWP_HELPER_VERSION', '0.2.3' );
	}

	if ( ! defined( 'SNAPWP_HELPER_PLUGIN_DIR' ) ) {
		/**
		 * The plugin directory path.
		 *
		 * @const string
		 */
		define( 'SNAPWP_HELPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'SNAPWP_HELPER_PLUGIN_URL' ) ) {
		/**
		 * The plugin directory URL.
		 *
		 * @const string
		 */
		define( 'SNAPWP_HELPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'SNAPWP_HELPER_PLUGIN_FILE' ) ) {
		/**
		 * The plugin file.
		 *
		 * @const string
		 */
		define( 'SNAPWP_HELPER_PLUGIN_FILE', __FILE__ );
	}
}

// Run this function when the plugin is activated.
if ( file_exists( __DIR__ . '/activation.php' ) ) {
	require_once __DIR__ . '/activation.php';
	register_activation_hook( __FILE__, 'SnapWP\Helper\activation_callback' );
}

// Load the plugin.
if ( class_exists( 'SnapWP\Helper\Main' ) ) {
	constants();

	\SnapWP\Helper\Main::instance();
}
