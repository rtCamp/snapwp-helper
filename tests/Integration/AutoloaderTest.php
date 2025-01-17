<?php

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Autoloader;
use lucatume\WPBrowser\TestCase\WPTestCase;

class MockAutoloader extends Autoloader {
	public static function reset() {
		self::$is_loaded = false;
	}
}

/**
 * Tests the main class.
 */
class AutoloaderTest extends WPTestCase {
	protected $autoloader;

	protected function setUp(): void {
		$this->autoloader = new MockAutoloader();
		MockAutoloader::reset();
	}

	protected function tearDown(): void {
		unset( $this->autoloader );
	}

	public function testAutoload() {
		$this->assertTrue( $this->autoloader->autoload() );
	}

	public function testRequireAutoloader() {
		$reflection = new \ReflectionClass( $this->autoloader );
		$property   = $reflection->getProperty( 'is_loaded' );
		$property->setAccessible( true );
		$property->setValue( $this->autoloader, false );

		$method = $reflection->getMethod( 'require_autoloader' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invokeArgs( $this->autoloader, [ SNAPWP_HELPER_PLUGIN_DIR . '/vendor/autoload.php' ] ) );
		$this->assertFalse( $method->invokeArgs( $this->autoloader, [ '/path/to/invalid/autoload.php' ] ) );

		// Test if there is an error message
		$this->expectOutputRegex( '/The Composer autoloader was not found/' );

		// Remove conflicting actions from wp-graphql-content-blocks
		$this->remove_actions();

		do_action( 'admin_notices' );
	}

	/**
	 * Remove broken actions from wp-graphql-content-blocks.
	 *
	 * @see https://github.com/wpengine/wp-graphql-content-blocks/pull/262
	 *
	 * @todo remove once PR is merged.
	 */
	protected function remove_actions(): void {
		$namespace = 'WPGraphQL\ContentBlocks\PluginUpdater';

		remove_action( 'admin_notices', $namespace . '\delegate_plugin_row_notice' );
		remove_action( 'admin_notices', $namespace . '\display_update_page_notice' );
	}
}
