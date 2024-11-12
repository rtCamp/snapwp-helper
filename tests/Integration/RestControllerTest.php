<?php
/**
 * Tests the RestController class.
 *
 * @package SnapWP\Helper\Integration\Tests
 */

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests the RestController class.
 */
class RestControllerTest extends WPTestCase {
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
		global $wp_rest_server;
		$wp_rest_server = null;

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
	 */
	public function testGenerateEnvEndpoint(): void {

		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual = $this->server->dispatch( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( 200, $actual->get_status() );

		$actual_data = $actual->get_data();

		$this->assertNotEmpty( $actual_data['content'] );
		$search   = '\n';
		$replace  = '';
		$expected = "\n# Enable if connecting to a self-signed cert\n# NODE_TLS_REJECT_UNAUTHORIZED='0'\n# The headless frontend domain URL\n# NEXT_URL='0'\n# The WordPress \"frontend\" domain URL\nHOME_URL=http://localhost\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=graphql";

		$this->assertEquals( $expected, str_replace( $search, $replace, $actual_data['content'] ) );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}

	/**
	 * Tests that endpoint returns an error when required values are missing.
	 */
	public function testMissingRequiredValues(): void {

		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );


		// Mock missing required variables (e.g., HOME_URL) in WordPress options.
		$original_home = get_home_url();
		update_option( 'home', '' ); // Make sure this option is missing or empty.
		
		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual           = $this->server->dispatch( $request );
		
		// temp : error_log( print_r( $actual, true ) );
		error_log( print_r( $actual, true ) );
		$actual_code      = $actual->get_data()['code'];
		$actual_message   = $actual->get_data()['message'];
		$expected_code    = 'env_generation_failed';
		$expected_message = 'Required variables must have a value.';

		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( $expected_code, $actual_code );
		$this->assertEquals( 500, $actual->get_status() );
		$this->assertStringContainsString( $expected_message, $actual_message );

		// Clean up.
		update_option( 'home', $original_home );
		wp_delete_user( $admin_id, true );
	}
}
