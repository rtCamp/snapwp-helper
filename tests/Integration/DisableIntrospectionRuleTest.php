<?php
/**
 * Tests the DisableIntrospectionRule class.
 *
 * @package SnapWP\Helper\Integration\Tests
 */

use lucatume\WPBrowser\TestCase\WPTestCase;
// use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;
// use SnapWP\Helper\Modules\GraphQL\Server\DisableIntrospectionRule;

// use function Codeception\Extension\codecept_log;
use WPGraphQL;

/**
 * Tests the DisableIntrospectionRule class.
 */
class DisableIntrospectionRuleTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	
	public $admin;
	public $subscriber;

	public function setUp(): void {
		parent::setUp();

		// Create an admin user for testing.
		$this->admin = $this->factory()->user->create([
			'role' => 'administrator',
		]);

		// Create a subscriber user for testing.
		$this->subscriber = $this->factory()->user->create([
			'role' => 'subscriber',
		]);

		// Enable public introspection by default.
		$settings = get_option('graphql_general_settings');
		$settings['public_introspection_enabled'] = 'on';
		update_option('graphql_general_settings', $settings);
	}

	public function tearDown(): void {
		// Cleanup: Reset the graphql_general_settings option.
		$settings = get_option('graphql_general_settings');
		unset($settings['public_introspection_enabled']);
		update_option('graphql_general_settings', $settings);

		parent::tearDown();
	}

	/**
	 * Helper to execute a GraphQL introspection query.
	 */
	private function introspectionQuery(): string {
		return '
			query IntrospectionQuery {
				__schema {
					queryType {
						name
					}
				}
			}
		';
	}

	public function testPublicDisabledNoTokenReturnsError() {
		// Disable public introspection.
		 $current_setting = get_graphql_setting('public_introspection_enabled', 'on');
		 codecept_debug("Current setting: ");
		 codecept_debug($current_setting);
   		 update_option('graphql_general_settings', ['public_introspection_enabled' => 'off']);
		 $new_setting = get_graphql_setting('public_introspection_enabled', 'on');
		 codecept_debug("New setting: ");
		 codecept_debug($new_setting);

		 // Log in as subscriber.
		 wp_set_current_user($this->subscriber);

 
		 // Execute the GraphQL introspection query without a token.
		 $query = $this->introspectionQuery();
		 $actual = $this->graphql(['query' => $query]);

		 codecept_debug($actual);
 
		 // Assert response contains an error.
		 $this->assertArrayHasKey('errors', $actual);
		 $this->assertStringContainsString('Introspection is not allowed', $actual['errors'][0]['message']);

		 update_option('graphql_general_settings', ['public_introspection_enabled' => $current_setting]);
	}

	public function testPublicDisabledInvalidTokenReturnsError() {
		// Disable public introspection.
		$settings = get_option('graphql_general_settings');
		$settings['public_introspection_enabled'] = 'off';
		update_option('graphql_general_settings', $settings);

		// Simulate invalid token in the authorization header.
		$_SERVER['HTTP_AUTHORIZATION'] = 'invalid_token';

		// Log in as subscriber.
		wp_set_current_user($this->subscriber);

		// Execute the GraphQL introspection query.
		$query = $this->introspectionQuery();
		$actual = $this->graphql(['query' => $query]);

		codecept_debug($actual);

		// Assert response contains an error.
		$this->assertArrayHasKey('errors', $actual);
		$this->assertStringContainsString('Introspection is not allowed', $actual['errors'][0]['message']);
	}

	public function testPublicDisabledAdminUserNoTokenReturnsSuccess() {
		// Disable public introspection.
		  $settings = get_option('graphql_general_settings');
		  $settings['public_introspection_enabled'] = 'off';
		  update_option('graphql_general_settings', $settings);
  
		  // Log in as admin.
		  wp_set_current_user($this->admin);
  
		  // Execute the GraphQL introspection query without a token.
		  $query = $this->introspectionQuery();
		  $actual = $this->graphql(['query' => $query]);

		  codecept_debug($actual);
  
		  // Assert response does not contain an error.
		  $this->assertArrayNotHasKey('errors', $actual);
		  $this->assertArrayHasKey('data', $actual);
	}

	public function testPublicEnabledNoTokenReturnsSuccess() {
		// Enable public introspection.
		$settings = get_option('graphql_general_settings');
		$settings['public_introspection_enabled'] = 'on';
		update_option('graphql_general_settings', $settings);

		// Log in as subscriber.
		wp_set_current_user($this->subscriber);

		// Execute the GraphQL introspection query without a token.
		$query = $this->introspectionQuery();
		$actual = $this->graphql(['query' => $query]);

		codecept_debug($actual);

		// Assert response does not contain an error.
		$this->assertArrayNotHasKey('errors', $actual);
		$this->assertArrayHasKey('data', $actual);
	}
}
