<?php

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Modules\Assets;
use lucatume\WPBrowser\TestCase\WPTestCase;

class AssetsTest extends WPTestCase {
	/**
	 * Test the name method.
	 */
	public function testName(): void {
		$assets = new Assets();
		$this->assertEquals( 'assets', $assets->name() );
	}

	/**
	 * Test the register_hooks method.
	 */
	public function testRegisterHooks(): void {
		$assets = new Assets();
		$assets->register_hooks();

		$this->assertTrue( (bool) has_action( 'admin_enqueue_scripts', [ $assets, 'register_admin_assets' ] ) );
	}

	/**
	 * Test the register_admin_assets method.
	 */
	public function testRegisterAdminAssets(): void {
		$assets = new Assets();
		$assets->register_admin_assets();

		$this->markTestIncomplete( 'Needs local testing' );

		$this->assertTrue( wp_script_is( Assets::ADMIN_SCRIPT_HANDLE, 'registered' ) );
	}

	/**
	 * Test the register_asset method.
	 */
	public function testRegisterAsset(): void {
		$assets     = new Assets();
		$reflection = new \ReflectionClass( $assets );

		$method = $reflection->getMethod( 'register_asset' );
		$method->setAccessible( true );

		// Test bad file
		$actual = $method->invoke( $assets, 'bad-handle', 'nofile' );

		$this->assertFalse( (bool) $actual );
		$this->assertFalse( wp_script_is( 'bad-handle', 'registered' ) );
	}
}
