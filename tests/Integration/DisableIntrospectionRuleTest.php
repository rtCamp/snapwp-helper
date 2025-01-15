<?php
/**
 * Tests the DisableIntrospectionRule class.
 *
 * @package SnapWP\Helper\Integration\Tests
 */

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;
use SnapWP\Helper\Modules\GraphQL\Server\DisableIntrospectionRule;

use function Codeception\Extension\codecept_log;

/**
 * Tests the DisableIntrospectionRule class.
 */
class DisableIntrospectionRuleTest extends WPTestCase {
	protected function setUp(): void {
		parent::setUp();

		// Clear introspection token.
		delete_option('snapwp_helper_introspection_token');
	}

	protected function tearDown(): void {
		// Clean up the introspection token after tests.
		delete_option('snapwp_helper_introspection_token');

		parent::tearDown();
	}

	/**
	 * Test: Public disabled + no token returns an error response.
	 */
	public function testPublicDisabledNoToken(): void {

		$rule = new DisableIntrospectionRule();

		codecept_debug($rule->isEnabled());
		codecept_debug($rule);

		$_SERVER['HTTP_AUTHORIZATION'] = '';

		$this->assertFalse($rule->isEnabled(), 'Introspection should be disabled when no token is provided, and public introspection is off.');
	}

	/**
	 * Test: Public disabled + invalid token returns an error response.
	 */
	public function testPublicDisabledInvalidToken(): void {
		update_option('snapwp_helper_introspection_token', 'valid-token');

		$rule = new DisableIntrospectionRule();

		$_SERVER['HTTP_AUTHORIZATION'] = 'invalid-token'; // Invalid token.

		$this->assertFalse($rule->isEnabled(), 'Introspection should be disabled when an invalid token is provided, and public introspection is off.');
	}

	/**
	 * Test: Public disabled + admin user + no token returns a successful response.
	 */
	public function testPublicDisabledAdminUser(): void {

		// Simulate admin user.
		wp_set_current_user($this->factory()->user->create(['role' => 'administrator'])); // create in setup and assign here

		$rule = new DisableIntrospectionRule();

		$_SERVER['HTTP_AUTHORIZATION'] = '';

		$this->assertTrue($rule->isEnabled(), 'Introspection should be allowed for admin users, even with no token, when public introspection is off.');
	}

	/**
	 * Test: Public enabled + no token returns a successful response.
	 */
	public function testPublicEnabledNoToken(): void {

		$rule = new DisableIntrospectionRule();

		$_SERVER['HTTP_AUTHORIZATION'] = '';

		$this->assertTrue($rule->isEnabled(), 'Introspection should be allowed when public introspection is enabled, even with no token.');
	}
}
