<?php
/**
 * Tests the UpdateChecker class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use YahnisElsts\PluginUpdateChecker\v5p4\Plugin\Update;
use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\PluginUpdater\UpdateChecker;

/**
 * Tests the Update Checker class.
 */
class UpdateCheckerTest extends WPTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		remove_all_filters( 'puc_is_slug_in_use-wp-graphql-content-blocks/wp-graphql-content-blocks.php' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		remove_all_filters( 'puc_is_slug_in_use-wp-graphql-content-blocks/wp-graphql-content-blocks.php' );

		parent::tearDown();
	}

	/**
	 * Test the instantiation and update checking of the UpdateChecker class.
	 */
	public function testPluginUpdateChecker() {
		$plugin_data = [
			[
				'slug'       => 'wp-graphql-content-blocks/wp-graphql-content-blocks.php',
				'update_uri' => 'https://github.com/wpengine/wp-graphql-content-blocks',
			],
		];

		// Creating an instance of UpdateChecker for plugins in $plugin_data.
		$update_checker = new UpdateChecker( $plugin_data );
		$update_checker->init();
		$actual_update_checkers = $update_checker->get_update_checkers();

		// Assert that the update checkers have been instantiated correctly, that each plugin in $plugin_data has a corresponding update checker.
		$this->assertIsArray( $actual_update_checkers, 'Update checkers should be an array.' );

		foreach ( $plugin_data as $plugin ) {
			$this->assertArrayHasKey( $plugin['slug'], $actual_update_checkers, "Update checker not found for plugin with slug '{$plugin['slug']}'" );
		}

		// Programmatically checking if there's an update available for a plugin.
		$plugin_slug_to_check    = 'wp-graphql-content-blocks/wp-graphql-content-blocks.php';
		$update_checker_instance = $actual_update_checkers[ $plugin_slug_to_check ];

		// Test if the plugin is up to date.
		$state = $update_checker_instance->getUpdateState();
		$state->setUpdate(
			Update::fromJson(
				wp_json_encode(
					(object) [
						'name'    => 'WP GraphQL Content Blocks',
						'slug'    => $plugin_slug_to_check,
						'id'      => 1,
						'version' => '1.0.0',
					]
				)
			)
		);

		$actual = $update_checker_instance->getUpdate();

		$this->assertNull( $actual, "No update should be available for plugin '$plugin_slug_to_check'." );

		// Test if the plugin is not up to date.
		$state->setUpdate(
			Update::fromJson(
				wp_json_encode(
					(object) [
						'name'    => 'WP GraphQL Content Blocks',
						'slug'    => $plugin_slug_to_check,
						'id'      => 1,
						'version' => '99.0.0',
					]
				)
			)
		);

		$actual = $update_checker_instance->getUpdate();

		$this->assertInstanceOf( Update::class, $actual, "An update should be available for plugin '$plugin_slug_to_check'." );
	}
}
