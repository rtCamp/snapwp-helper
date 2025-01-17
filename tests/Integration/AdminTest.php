<?php
/**
 * Tests the Admin class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Modules\Admin;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests the Admin class.
 */
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

	/**
	 * Test the handle_token_regeneration method with correct parameters.
	 */
	public function testHandleTokenRegeneration(): void {
		// Stub the current screen global.
		global $current_screen;
		$current_screen = (object) [
			'id' => 'graphql_page_snapwp-helper',
		];

		// Stub $_POST variables for token regeneration.
		$_POST['regenerate_token']       = true;
		$_POST['regenerate_token_nonce'] = wp_create_nonce( 'regenerate_token_action' );

		// Capture the admin notices output.
		ob_start();
		do_action( 'admin_notices' );
		$admin = new Admin();
		$admin->handle_token_regeneration();
		$output = ob_get_clean();

		// Verify the admin notice for success.
		$this->assertStringContainsString(
			__( 'Introspection token regenerated successfully. Please make sure to update your `.env` file.', 'snapwp-helper' ),
			$output
		);
	}

	/**
	 * Test the handle_token_regeneration method with a wrong screen ID should not regenerate the token.
	 */
	public function testHandleTokenRegenerationWithWrongScreenId(): void {
		// Stub the current screen global.
		global $current_screen;
		$current_screen = (object) [
			'id' => 'NOT_ON_graphql_page_snapwp-helper',
		];

		// Stub $_POST variables for token regeneration.
		$_POST['regenerate_token']       = true;
		$_POST['regenerate_token_nonce'] = wp_create_nonce( 'regenerate_token_action' );

		// Capture the admin notices output.
		ob_start();
		do_action( 'admin_notices' );
		$admin = new Admin();
		$admin->handle_token_regeneration();
		$output = ob_get_clean();

		// Verify that the token was not regenerated.
		$this->assertEmpty( $output );
	}

	/**
	 * Test the handle_token_regeneration method with an invalid nonce.
	 */
	public function testHandleTokenRegenerationWithInvalidNonce(): void {
		// Stub the current screen global.
		global $current_screen;
		$current_screen = (object) [
			'id' => 'graphql_page_snapwp-helper',
		];

		// Stub $_POST variables for token regeneration.
		$_POST['regenerate_token']       = true;
		$_POST['regenerate_token_nonce'] = 'invalid_nonce';

		// Capture the admin notices output.
		ob_start();
		do_action( 'admin_notices' );
		$admin = new Admin();
		$admin->handle_token_regeneration();
		$output = ob_get_clean();

		// Verify the admin notice for nonce failure.
		$this->assertStringContainsString(
			__( 'Could not regenerate the introspection token: nonce verification failed.', 'snapwp-helper' ),
			$output
		);
	}
}
