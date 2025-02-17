<?php
/**
 * Plugin Name: SnapWP - Helper
 * Plugin URI: https://github.com/rtCamp/snapwp-helper
 * GitHub Plugin URI: https://github.com/rtCamp/snapwp-helper
 * Description: Manages WPGraphQL extensions updates and discovery.
 * Author: rtCamp
 * Author URI: https://github.com/rtCamp
 * Update URI: https://github.com/rtCamp/snapwp-helper
 * Version: 0.0.1
 * Text Domain: snapwp-helper
 * Domain Path: /languages
 * Requires at least: 6.7
 * Tested up to: 6.7.2
 * Requires PHP: 7.4
 * WPGraphQL tested up to: 1.31.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package SnapWP\Helper
 * @author rtCamp
 * @license GPL-3
 * @version 0.0.1
 */

declare(strict_types=1);

namespace SnapWP\Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		define( 'SNAPWP_HELPER_VERSION', '0.0.1' );
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

// Check for dependencies.
add_action( 'plugins_loaded', __NAMESPACE__ . '\check_dependencies' );

/**
 * Check if required plugins are installed and activated.
 */
function check_dependencies(): void {
	// Get the list of active plugins.
	$active_plugins = get_option( 'active_plugins' );

	// Required plugins.
	$required_plugins = [
		'wp-graphql/wp-graphql.php' => 'https://wordpress.org/plugins/wp-graphql/',
		'wp-graphql-content-blocks/wp-graphql-content-blocks.php' => 'https://github.com/wp-graphql/wp-graphql-content-blocks',
	];

	// Check if required plugin are installed and activated.
	foreach ( $required_plugins as $slug => $url ) {
		if ( ! in_array( $slug, $active_plugins, true ) ) {
			add_action(
				'admin_notices',
				static function () use ( $slug, $url ) {
					$slug_name = explode( '\\', $slug );
					$slug_name = end( $slug_name );
					echo '<div class="notice notice-error"><p><strong>SnapWP Helper:</strong> ' . esc_html( $slug_name ) . ' is not installed or activated. Please install and activate <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $slug_name ) . '</a> to use this plugin.</p></div>';
				}
			);

			// Bail early.
			return;
		}
	}

	// Load the plugin if dependencies are met.
	plugin_init();
}

/**
 * Initialize the plugin.
 */
function plugin_init(): void {
	// Load the autoloader.
	require_once __DIR__ . '/src/Autoloader.php';
	if ( ! \SnapWP\Helper\Autoloader::autoload() ) {
		return;
	}

	// Run activation callback if file exists.
	if ( file_exists( __DIR__ . '/activation.php' ) ) {
		require_once __DIR__ . '/activation.php';
		register_activation_hook( __FILE__, __NAMESPACE__ . '\activation_callback' );
	}

	// Load the plugin.
	if ( class_exists( 'SnapWP\Helper\Main' ) ) {
		constants();

		\SnapWP\Helper\Main::instance();
	}
}
