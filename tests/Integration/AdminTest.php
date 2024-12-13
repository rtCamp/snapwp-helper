<?php

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\Admin;

class AdminTest extends WPTestCase {
	/**
	 * @param \IntegrationTester
	 */
	protected $tester;

	/**
	 * Test the name method.
	 */
	public function testName(): void {
		$admin = new Admin();
		$this->assertEquals( 'admin', $admin->name() );
	}

	/**
	 * Test the register_hooks method.
	 */
	public function testRegisterHooks(): void {
		$admin = new Admin();
		$admin->register_hooks();

		$this->assertTrue( (bool) has_action( 'admin_menu', [ $admin, 'register_menu' ] ) );
	}

	/**
	 * Test the render_menu method.
	 */
	public function testRenderMenu(): void {
		$assets = new Admin();

		$this->expectOutputRegex( '/SnapWP Frontend Setup Guide/' );
		$assets->render_menu();
	}
}
