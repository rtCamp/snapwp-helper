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

		$actual = $this->server->dispatch( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( 200, $actual->get_status() );

		$actual_data = $actual->get_data();

		$this->assertNotEmpty( $actual_data['content'] );
		$search   = '\n';
		$replace  = '';
		$expected = "\n# Enable if connecting to a self-signed cert\n# NODE_TLS_REJECT_UNAUTHORIZED=0\n# The headless frontend domain URL\n# NEXT_PUBLIC_URL=http://localhost:3000\n# The WordPress \"frontend\" domain URL\nNEXT_PUBLIC_WORDPRESS_URL=" . get_home_url() . "\n# The WordPress GraphQL endpoint\n# NEXT_PUBLIC_GRAPHQL_ENDPOINT=" . graphql_get_endpoint() . "\n# WordPress Uploads Directory Path\n# NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH=" . str_replace( ABSPATH, '', wp_get_upload_dir()['basedir'] ) . "\n# WordPress REST URL Prefix\n# NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX=" . rest_get_url_prefix();

		$this->assertEquals( $expected, str_replace( $search, $replace, $actual_data['content'] ) );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}
}
