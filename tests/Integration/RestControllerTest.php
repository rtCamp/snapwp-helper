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

		$actual = $this->server->dispatch( $request );
		$this->assertInstanceOf( \WP_REST_Response::class, $actual );
		$this->assertEquals( 200, $actual->get_status() );

		$actual_data = $actual->get_data();

		$this->assertNotEmpty( $actual_data['content'] );
		$search   = '\n';
		$replace  = '';
		$expected = "\n# Token used for authenticating GraphQL introspection queries\nINTROSPECTION_TOKEN=" . IntrospectionToken::get_token() . "\n\n# The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: /proxy, This means for script module next app will make request NEXT_PUBLIC_FRONTEND_URL/proxy/{module-path}\n# NEXT_PUBLIC_CORS_PROXY_PREFIX=/proxy\n\n# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.\nNEXT_PUBLIC_FRONTEND_URL=http://localhost:3000\n\n# The WordPress GraphQL endpoint\nNEXT_PUBLIC_GRAPHQL_ENDPOINT=" . graphql_get_endpoint() . "\n\n# The WordPress REST URL Prefix\n# NEXT_PUBLIC_REST_URL_PREFIX=/" . rest_get_url_prefix() . "\n\n# The WordPress \"frontend\" domain URL e.g. https://my-headless-site.local\nNEXT_PUBLIC_WP_HOME_URL=" . get_home_url() . "\n\n# The WordPress \"backend\" Site Address. Uncomment if different than `NEXT_PUBLIC_WP_HOME_URL` e.g. https://my-headless-site.local/wp/\n# NEXT_PUBLIC_WP_SITE_URL=\n\n# The WordPress Uploads directory path\n# NEXT_PUBLIC_WP_UPLOADS_DIRECTORY=/" . str_replace( ABSPATH, '', wp_get_upload_dir()['basedir'] ) . "\n\n# Only enable if connecting to a self-signed cert\nNODE_TLS_REJECT_UNAUTHORIZED=0";

		$this->assertEquals( $expected, str_replace( $search, $replace, $actual_data['content'] ) );

		// Clean up.
		wp_delete_user( $admin_id, true );
	}
}
