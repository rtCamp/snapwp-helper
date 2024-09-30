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

		// Prepare the request body.
		$body = [
			'variables' => [
				[
					'name'  => 'NODE_TLS_REJECT_UNAUTHORIZED',
					'value' => '5',
				],
				[
					'name'  => 'NEXT_URL',
					'value' => 'http://localhost:3000',
				],
				[
					'name'  => 'HOME_URL',
					'value' => 'https://headless-demo.local',
				],
				[
					'name'  => 'GRAPHQL_ENDPOINT',
					'value' => 'test_endpoint',
				],
			],
		];

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );

		$request->set_body( wp_json_encode( $body ) );
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
		$expected = "\n# Enable if connecting to a self-signed cert\nNODE_TLS_REJECT_UNAUTHORIZED=5\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=test_endpoint";

		$this->assertEquals( $expected, str_replace( $search, $replace, $actual_data['content'] ) );

		// CASE : Using of default values.

		// Prepare the request body.
		$body = [
			'variables' => [
				[
					'name'  => 'NODE_TLS_REJECT_UNAUTHORIZED',
					'value' => '',
				],
				[
					'name'  => 'NEXT_URL',
					'value' => 'http://localhost:3000',
				],
				[
					'name'  => 'HOME_URL',
					'value' => 'https://headless-demo.local',
				],
				[
					'name'  => 'GRAPHQL_ENDPOINT',
					'value' => '',
				],
			],
		];

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );

		$request->set_body( wp_json_encode( $body ) );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual   = $this->server->dispatch( $request );
		$subject  = $actual->get_data()['content'];
		$search   = '\n';
		$replace  = '';
		$expected = "\n# Enable if connecting to a self-signed cert\n# NODE_TLS_REJECT_UNAUTHORIZED='0'\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=graphql";

		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( $expected, str_replace( $search, $replace, $subject ) );
		$this->assertEquals( 200, $actual->get_status() );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}

	/**
	 * Tests the schema for the endpoint is correct and followed.
	 */
	public function testSchemaForEndpoint(): void {

		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Prepare the request body.
		$body = [
			'variables' => [
				[
					'name'  => 'NODE_TLS_REJECT_UNAUTHORIZED',
					'value' => '',
				],
				[
					'name'  => 'NEXT_URL',
					'value' => 'http://localhost:3000',
				],
				[
					'name'  => 'HOME_URL',
					'value' => 'https://headless-demo.local',
				],
			],
		];

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );

		$request->set_body( wp_json_encode( $body ) );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual   = $this->server->dispatch( $request );
		$subject  = $actual->get_data()['content'];
		$search   = '\n';
		$replace  = '';
		$expected = "\n# Enable if connecting to a self-signed cert\n# NODE_TLS_REJECT_UNAUTHORIZED='0'\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local";

		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( 200, $actual->get_status() );
		$this->assertEquals( $expected, str_replace( $search, $replace, $subject ) );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}

	/**
	 * Tests that endpoint returns an error when required values are missing.
	 */
	public function testMissingRequiredValues(): void {

		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Prepare the request body.
		$body = [
			'variables' => [
				[
					'name'  => 'NODE_TLS_REJECT_UNAUTHORIZED',
					'value' => '',
				],
				[
					'name'  => 'NEXT_URL',
					'value' => 'https://mynexturl.local',
				],
				[
					'name'  => 'HOME_URL',
					'value' => '',
				],
				[
					'name'  => 'GRAPHQL_ENDPOINT',
					'value' => graphql_get_endpoint_url(),
				],
			],
		];

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );
		$request->set_header( 'Origin', get_site_url() );

		$request->set_body( wp_json_encode( $body ) );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual           = $this->server->dispatch( $request );
		$actual_code      = $actual->get_data()['code'];
		$actual_message   = $actual->get_data()['message'];
		$expected_code    = 'env_generation_failed';
		$expected_message = 'Required variables must have a value.';

		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( $expected_code, $actual_code );
		$this->assertEquals( 500, $actual->get_status() );
		$this->assertStringContainsString( $expected_message, $actual_message );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}

	/**
	 * Tests that non-existent variables are not included in the generated .env file content.
	 */
	public function testNonexistentVariables(): void {

		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Prepare the request body with a NONEXISTENT_VARIABLE.
		$body = [
			'variables' => [
				[
					'name'  => 'NODE_TLS_REJECT_UNAUTHORIZED',
					'value' => '',
				],
				[
					'name'  => 'NEXT_URL',
					'value' => 'http://localhost:3000',
				],
				[
					'name'  => 'HOME_URL',
					'value' => 'https://headless-demo.local',
				],
				[
					'name'  => 'GRAPHQL_ENDPOINT',
					'value' => '',
				],
				[
					'name'  => 'NONEXISTENT_VARIABLE',
					'value' => 'illegal value',
				],
			],
		];

		// Create a new POST request to the REST endpoint.
		$request = new \WP_REST_Request( 'GET', $this->endpoint );

		$request->set_body( wp_json_encode( $body ) );
		$request->set_header( 'Content-Type', 'application/json' );

		// Set the current user as administrator.
		wp_set_current_user( $admin_id );

		$actual         = $this->server->dispatch( $request );
		$actual_message = $actual->get_data()['content'];
		$search         = '\n';
		$replace        = '';

		// Ensuring response does not contain the nonexistent variable or its value.
		$expected = "\n# Enable if connecting to a self-signed cert\n# NODE_TLS_REJECT_UNAUTHORIZED='0'\n# The headless frontend domain URL\nNEXT_URL=http://localhost:3000\n# The WordPress \"frontend\" domain URL\nHOME_URL=https://headless-demo.local\n# The WordPress GraphQL endpoint\nGRAPHQL_ENDPOINT=graphql";

		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertStringContainsString( $expected, str_replace( $search, $replace, $actual_message ) );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}
}
