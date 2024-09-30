<?php
/**
 * Tests the PluginUpdater class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\PluginUpdater;

/**
 * Tests the PluginUpdater class.
 */
class PluginUpdaterTest extends WPTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$plugins = $this->get_plugins();
		foreach ( $plugins as $plugin ) {
			remove_all_filters( 'puc_is_slug_in_use-' . $plugin['slug'] );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$plugins = $this->get_plugins();
		foreach ( $plugins as $plugin ) {
			remove_all_filters( 'puc_is_slug_in_use-' . $plugin['slug'] );
		}

		parent::tearDown();
	}

	/**
	 * Test the instantiation and initialization of the PluginUpdater class.
	 */
	public function testPluginUpdater() {
		$plugin_updater = new PluginUpdater();
		$plugin_updater->instantiate_update_checker();

		// Get the update_checkers property from the PluginUpdater class.
		$reflection = new \ReflectionClass( $plugin_updater );
		$property   = $reflection->getProperty( 'update_checker' );
		$property->setAccessible( true );
		$update_checker = $property->getValue( $plugin_updater );

		$actual_update_checkers = $update_checker->get_update_checkers();

		$this->assertIsArray( $actual_update_checkers, 'Update checkers should be an array.' );
		$this->assertNotEmpty( $actual_update_checkers, 'Update checkers should not be empty.' );
	}

	/**
	 * Gets the plugins to check for updates.
	 */
	protected function get_plugins(): array {
		$instance   = new PluginUpdater();
		$reflection = new \ReflectionClass( $instance );

		$method = $reflection->getMethod( 'get_plugins' );

		$method->setAccessible( true );

		return $method->invoke( $instance );
	}
}
