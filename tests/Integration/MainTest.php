<?php

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests the main class.
 */
class MainTest extends WPTestCase {
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Test if the plugin is active.
	 */
	public function test_plugin_active(): void {
		$this->assertTrue( is_plugin_active( 'snapwp-helper/snapwp-helper.php' ) );
	}

	/**
	 * Test the instance method.
	 */
	public function testInstance(): void {
		$instance = \SnapWP\Helper\Main::instance();
		$this->assertInstanceOf( \SnapWP\Helper\Main::class, $instance );
	}

	/**
	 * Test the load method.
	 */
	public function testLoad(): void {
		$instance = \SnapWP\Helper\Main::instance();
		$instance->load();
		$this->assertTrue( true );
	}

	/**
	 * Test cloning does not work.
	 */
	public function testClone(): void {
		$this->setExpectedIncorrectUsage( '__clone' );

		$instance        = \SnapWP\Helper\Main::instance();
		$cloned_instance = clone $instance;
	}

	/**
	 * Test deserializing does not work.
	 */
	public function testWakeup(): void {
		$this->setExpectedIncorrectUsage( '__wakeup' );

		$instance            = \SnapWP\Helper\Main::instance();
		$serialized_instance = serialize( $instance );

		unserialize( $serialized_instance );
	}

	/**
	 * Test incompatible class in Loader.
	 */
	public function testIncompatibleClass(): void {
		// Add a class that is not compatible.
		add_filter(
			'snapwp_helper/init/module_classes',
			static function ( $classes ) {
				$classes[] = \stdClass::class;
				return $classes;
			}
		);

		$this->setExpectedIncorrectUsage( 'stdClass' );

		$instance = \SnapWP\Helper\Main::instance();
		// Reset the singleton.
		$reflection = new \ReflectionClass( $instance );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		$instance = \SnapWP\Helper\Main::instance();
	}
}
