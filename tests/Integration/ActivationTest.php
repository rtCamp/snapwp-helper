<?php

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper;
use lucatume\WPBrowser\TestCase\WPTestCase;


class ActivationTest extends WPTestCase {
	private $wp_version;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wp_version = $GLOBALS['wp_version'];
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$GLOBALS['wp_version'] = $this->wp_version;
	}

	/**
	 * Test the min_wp_version function.
	 */
	public function testMinWpVersion(): void {
		$this->assertIsNumeric( Helper\min_wp_version() );
	}

	/**
	 * Test the min_php_version function.
	 */
	public function testMinPhpVersion(): void {
		$this->assertIsNumeric( Helper\min_php_version() );
	}

	/**
	 * Test the has_wp_requirements function.
	 */
	public function testHasWpRequirements(): void {
		// By virtue of the tests, we know that the plugin is running in a WordPress environment.
		$this->assertTrue( Helper\has_wp_requirements() );

		global $wp_version;

		$old_version = $wp_version;

		$wp_version = '5.0';

		$this->assertFalse( Helper\has_wp_requirements() );

		// Reset the global variable.
		$wp_version = $old_version;
	}

	/**
	 * Test the has_php_requirements function.
	 */
	public function testHasPhpRequirements(): void {
		$this->assertTrue( Helper\has_php_requirements() );
	}

	/**
	 * Test the check_requirements function.
	 */
	public function testCheckRequirements(): void {
		$this->expectNotToPerformAssertions();
		Helper\check_requirements();
	}

	/**
	 * Test the check_requirements function with unmet WordPress requirements.
	 */
	public function testCheckRequirementsUnmetWp(): void {
		global $wp_version;
		$old_version = $wp_version;
		$wp_version  = '5.0';

		$this->expectException( \WPDieException::class );
		Helper\check_requirements();

		// Reset the global variable.
		$wp_version = $old_version;
	}

	/**
	 * Tests the activation callback triggers the hook.
	 */
	public function testActivationCallback(): void {
		$actual = false;

		add_action(
			'snapwp_helper/activate',
			static function () use ( &$actual ) {
				$actual = true;
			},
			10,
			0
		);

		Helper\activation_callback();

		$this->assertTrue( $actual );
	}
}
