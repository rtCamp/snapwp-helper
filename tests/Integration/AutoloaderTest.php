<?php

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Autoloader;
use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

class MockAutoloader extends Autoloader {
	public static function reset() {
		self::$is_loaded = false;
	}
}

/**
 * Tests the main class.
 */
class AutoloaderTest extends IntegrationTestCase {
	protected $autoloader;

	protected function setUp(): void {
		parent::setUp();

		$this->autoloader = new MockAutoloader();
		MockAutoloader::reset();
	}

	protected function tearDown(): void {
		unset( $this->autoloader );

		parent::tearDown();
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

		do_action( 'admin_notices' );
	}
}
