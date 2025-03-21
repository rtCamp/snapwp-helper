<?php
/**
 * Tests the RestController class.
 *
 * @package SnapWP\Helper\Integration\Tests
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;
use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Tests the RestController class.
 */
class RestControllerTest extends IntegrationTestCase {
	/**
	 * The REST endpoint to use.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * The REST server instance.
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up a REST server instance.
		global $wp_rest_server;

		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->endpoint = '/snapwp/v1/env';
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		global $wp_rest_server, $wp_registered_settings;
		$wp_rest_server = null;

		// Rest API registers settings, so we need to clear them to prevent conflicts with our GeneralSettings fields.
		$wp_registered_settings = null;

		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests the the route is correctly registered.
	 */
	public function testRegisterRoutes(): void {
		$actual = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $actual );
	}

	/**
	 * Tests if the endpoint is accessible, and the env content in response is correct.
	 * Assuming standard default values.
	 */
	public function testGenerateEnvEndpoint(): void {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$response = $this->server->dispatch( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertNotEmpty( $response_data['content'] );

		$content = $response_data['content'];

		// Check for required variables
		$this->assertStringContainsString( 'NODE_TLS_REJECT_UNAUTHORIZED=', $content );
		$this->assertStringContainsString( 'NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000', $content );
		$this->assertStringContainsString( 'NEXT_PUBLIC_WP_HOME_URL=' . untrailingslashit( get_home_url() ), $content );

		// Check if GraphQL endpoint is present
		$graphql_endpoint = function_exists( 'graphql_get_endpoint' ) ? graphql_get_endpoint() : 'graphql';
		$this->assertStringContainsString( 'NEXT_PUBLIC_GRAPHQL_ENDPOINT=' . $graphql_endpoint, $content );

		// Check for introspection token
		$token = IntrospectionToken::get_token();
		$this->assertStringContainsString( 'INTROSPECTION_TOKEN=' . $token, $content );

		// Check commented variable format
		$this->assertStringContainsString( '# NEXT_PUBLIC_CORS_PROXY_PREFIX=/proxy', $content );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}
}
