<?php
/**
 * Tests the DisableIntrospectionRule class.
 *
 * @package SnapWP\Helper\Integration\Tests
 */

namespace SnapWP\Helper\Integration\Tests;

use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Tests the DisableIntrospectionRule class.
 */
class DisableIntrospectionRuleTest extends IntegrationTestCase {
	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		// Add filter to disable GRAPHQL_DEBUG mode.
		add_filter( 'graphql_debug_enabled', '__return_false' );

		// Enable public introspection by default.
		$settings                                 = get_option( 'graphql_general_settings' );
		$settings['public_introspection_enabled'] = 'on';
		update_option( 'graphql_general_settings', $settings );
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown(): void {
		// Reset the graphql_general_settings option.
		$settings                                 = get_option( 'graphql_general_settings' );
		$settings['public_introspection_enabled'] = 'off';
		update_option( 'graphql_general_settings', $settings );

		remove_all_filters( 'graphql_debug_enabled' );

		wp_delete_user( $this->admin );

		parent::tearDown();
	}

	/**
	 * Helper to get the GraphQL introspection query.
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

	/**
	 * Test that with the public introspection disabled, a query without a token returns an error.
	 */
	public function testPublicDisabledNoTokenReturnsError(): void {
		$this->assertFalse( \WPGraphQL::debug(), 'GraphQL debugging should be disabled during this test.' );

		// Disable debug mode.
		get_graphql_setting( 'debug_mode_enabled', 'off' );
		update_option( 'graphql_general_settings', [ 'debug_mode_enabled' => 'off' ] );
		get_graphql_setting( 'debug_mode_enabled', 'off' );

		// Disable public introspection.
		$current_setting = get_graphql_setting( 'public_introspection_enabled', 'on' );
		update_option( 'graphql_general_settings', [ 'public_introspection_enabled' => 'off' ] );
		get_graphql_setting( 'public_introspection_enabled', 'on' );

		// Execute the GraphQL introspection query without a token.
		$query  = $this->introspectionQuery();
		$actual = $this->graphql( [ 'query' => $query ] );

		// Assert response contains an error.
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertStringContainsString( 'introspection is not allowed', $actual['errors'][0]['message'] );

		// Cleanup.
		update_option( 'graphql_general_settings', [ 'public_introspection_enabled' => $current_setting ] );
	}

	/**
	 * Test that with the public introspection disabled, a query with an invalid token returns an error.
	 */
	public function testPublicDisabledInvalidTokenReturnsError(): void {
		$this->assertFalse( \WPGraphQL::debug(), 'GraphQL debugging should be disabled during this test.' );

		// Disable public introspection.
		$settings                                 = get_option( 'graphql_general_settings' );
		$settings['public_introspection_enabled'] = 'off';
		update_option( 'graphql_general_settings', $settings );

		// Simulate invalid token in the authorization header.
		$_SERVER['HTTP_AUTHORIZATION'] = 'invalid_token';

		// Execute the GraphQL introspection query.
		$query  = $this->introspectionQuery();
		$actual = $this->graphql( [ 'query' => $query ] );

		// Assert response contains an error.
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertStringContainsString( 'introspection is not allowed', $actual['errors'][0]['message'] );
	}

	/**
	 * Test that with the public introspection disabled, a query from admin with no token returns a success response.
	 */
	public function testPublicDisabledAdminUserNoTokenReturnsSuccess(): void {
		// Disable public introspection.
		$settings                                 = get_option( 'graphql_general_settings' );
		$settings['public_introspection_enabled'] = 'off';
		update_option( 'graphql_general_settings', $settings );

		// Set the current user to the admin user.
		wp_set_current_user( $this->admin );

		// Execute the GraphQL introspection query without a token.
		$query  = $this->introspectionQuery();
		$actual = $this->graphql( [ 'query' => $query ] );

		// Assert response does not contain an error.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );
	}

	/**
	 * Test that with the public introspection enabled, a query with no token returns a success response.
	 */
	public function testPublicEnabledNoTokenReturnsSuccess(): void {
		// Execute the GraphQL introspection query without a token.
		$query  = $this->introspectionQuery();
		$actual = $this->graphql( [ 'query' => $query ] );

		// Assert response does not contain an error.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );
	}
}
