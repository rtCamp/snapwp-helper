<?php
/**
 * The plugin activation hook.
 *
 * Unlike the rest of the plugin, we want this file to be as lax as possible, to increase the changes of it running on an older version of PHP or WordPress.
 *
 * @package SnapWP\Helper
 */

namespace SnapWP\Helper;

/**
 * The minimum WordPress version required to run this plugin.
 *
 * @return string
 */
function min_wp_version() {
	return '6.0';
}

/**
 * The minimum PHP version required to run this plugin.
 *
 * @return string
 */
function min_php_version() {
	return '7.4';
}

/**
 * Checks whether the WordPress requirements are met.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function has_wp_requirements() {
	global $wp_version;
	return version_compare( $wp_version, min_wp_version(), '>=' );
}

/**
 * Checks whether the PHP requirements are met.
 *
 * @return bool
 */
function has_php_requirements() {
	return version_compare( phpversion(), min_php_version(), '>=' );
}

/**
 * Checks the requirements for the plugin, deactivating it if they are not met.
 *
 * @return void
 */
function check_requirements() {
	$unmet_dependencies = [];

	if ( ! has_wp_requirements() ) {
		$unmet_dependencies['WordPress'] = min_wp_version();
	}

	if ( ! has_php_requirements() ) {
		$unmet_dependencies['PHP'] = min_php_version();
	}

	$count = count( $unmet_dependencies );

	// Return if all requirements are met.
	if ( 0 === $count ) {
		return;
	}

	// Deactivate the plugin if the requirements are not met.
	deactivate_plugins( plugin_basename( __FILE__ ) );

	$plugins  = array_keys( $unmet_dependencies );
	$versions = array_values( $unmet_dependencies );

	$message = __( 'SnapWP Helper: The plugin has been deactivated because it requires ', 'snapwp-helper' );

	for ( $i = 0; $i < $count; $i++ ) {
		$message .= sprintf(
			// translators: 1: Plugin name, 2: Required version.
			__( '%1$s version %2$s or higher', 'snapwp-helper' ),
			esc_html( $plugins[ $i ] ),
			esc_html( $versions[ $i ] )
		);

		if ( $i < $count - 1 ) {
			$message .= __( ' and ', 'snapwp-helper' );
		}
	}

	// Die with the error message.
	wp_die( esc_html( $message ) );
}

/**
 * Callback for the activation hook.
 *
 * @return void
 */
function activation_callback() {
	// Ensure the requirements are met.
	check_requirements();

	/**
	 * Fires when the plugin is activated.
	 */
	do_action( 'snapwp_helper/activate' );
}
