<?php
/**
 * Integration suite bootstrap file.
 *
 * This file is loaded AFTER the suite modules are initialized, WordPress, plugins and themes are loaded.
 *
 * If you need to load plugins or themes, add them to the Integration suite configuration file, in the
 * "modules.config.WPLoader.plugins" and "modules.config.WPLoader.theme" settings.
 *
 * If you need to load one or more database dump file(s) to set up the test database, add the path to the dump file to
 * the "modules.config.WPLoader.dump" setting.
 *
 * @package SnapWP\Helper
 */

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', true );
}
if ( ! defined( 'GRAPHQL_DEBUG' ) ) {
	define( 'GRAPHQL_DEBUG', true );
}
