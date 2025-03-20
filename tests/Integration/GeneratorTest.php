<?php
/**
 * Tests the Generator class initialization.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;
use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Class GeneratorTest
 *
 * @package SnapWP\Helper\Tests\Integration
 */
class GeneratorTest extends IntegrationTestCase {
	/**
	 * Tests if the Generator class initializes properly.
	 */
	public function testGeneratorInitialization(): void {
		$registry = new VariableRegistry();

		$values = [
			'NODE_TLS_REJECT_UNAUTHORIZED'     => '',
			'NEXT_PUBLIC_FRONTEND_URL'         => 'http://localhost:3000',
			'NEXT_PUBLIC_WP_HOME_URL'          => 'https://headless-demo.local',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'     => '',
			'NEXT_PUBLIC_WP_UPLOADS_DIRECTORY' => '',
			'NEXT_PUBLIC_REST_URL_PREFIX'      => '',
		];

		$generator = new Generator( $values, $registry );

		$this->assertInstanceOf( Generator::class, $generator );
	}

	/**
	 * Tests if the Generator generates the correct formatt .ENV content.
	 */
	public function testGenerateEnvContent(): void {
		$registry = new VariableRegistry();
		$values   = [
			'NODE_TLS_REJECT_UNAUTHORIZED'     => '5',
			'NEXT_PUBLIC_CORS_PROXY_PREFIX'    => '/proxy',
			'NEXT_PUBLIC_FRONTEND_URL'         => 'http://localhost:3000',
			'NEXT_PUBLIC_WP_HOME_URL'          => 'https://headless-demo.local',
			'NEXT_PUBLIC_WP_SITE_URL'          => '',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'     => '/test_endpoint',
			'NEXT_PUBLIC_WP_UPLOADS_DIRECTORY' => 'uploads',
			'NEXT_PUBLIC_REST_URL_PREFIX'      => 'api',
			'INTROSPECTION_TOKEN'              => '0123456789',
			'INVALID_VARIABLE'                 => 'should-not-be-included', // This should not be included in the output.
		];

		$generator = new Generator( $values, $registry );

		// Generate the .env content.
		$content = $generator->generate();

		$expectedContent = '
# Only enable if connecting to a self-signed cert
NODE_TLS_REJECT_UNAUTHORIZED=5

# The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: /proxy, This means for script module next app will make request NEXT_PUBLIC_FRONTEND_URL/proxy/{module-path}
# NEXT_PUBLIC_CORS_PROXY_PREFIX=/proxy

# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.
NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000

# The WordPress "frontend" domain URL e.g. https://my-headless-site.local
NEXT_PUBLIC_WP_HOME_URL=https://headless-demo.local

# The WordPress "backend" Site Address. Uncomment if different than `NEXT_PUBLIC_WP_HOME_URL` e.g. https://my-headless-site.local/wp/
# NEXT_PUBLIC_WP_SITE_URL=

# The WordPress GraphQL endpoint
NEXT_PUBLIC_GRAPHQL_ENDPOINT=/test_endpoint

# The WordPress Uploads directory path
NEXT_PUBLIC_WP_UPLOADS_DIRECTORY=uploads

# The WordPress REST URL Prefix
NEXT_PUBLIC_REST_URL_PREFIX=api

# Token used for authenticating GraphQL introspection queries
INTROSPECTION_TOKEN=0123456789';

		$this->assertSame( $expectedContent, $content );
	}

	/**
	 * Tests if the Generator class throws correct error when missing required values.
	 */
	public function testMissingRequiredValuesEnvContent(): void {
		$registry = new VariableRegistry();
		$values   = [
			'NODE_TLS_REJECT_UNAUTHORIZED'     => '',
			'NEXT_PUBLIC_FRONTEND_URL'         => '',
			'NEXT_PUBLIC_WP_HOME_URL'          => '',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'     => '',
			'NEXT_PUBLIC_WP_UPLOADS_DIRECTORY' => '',
			'NEXT_PUBLIC_REST_URL_PREFIX'      => '',
			'INTROSPECTION_TOKEN'              => '',
		];

		$generator = new Generator( $values, $registry );

		// Expect an exception when calling generate() because of missing required values.
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Required variables must have a value.' );

		// Generate the .env content, which should throw an exception.
		$generator->generate();
	}

	/**
	 * Tests if the Generator class handles missing values using the defaults.
	 */
	public function testDefaultValuesForEnvContent(): void {
		$registry = new VariableRegistry();

		// CASE : For NODE_TLS_REJECT_UNAUTHORIZED with no default value, Generator class should comment out the variable in .ENV content.
		$values = [
			'NODE_TLS_REJECT_UNAUTHORIZED'     => '0',
			'NEXT_PUBLIC_CORS_PROXY_PREFIX'    => '',
			'NEXT_PUBLIC_FRONTEND_URL'         => 'http://localhost:3000',
			'NEXT_PUBLIC_WP_HOME_URL'          => 'https://headless-demo.local',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'     => '/test_endpoint',
			'NEXT_PUBLIC_WP_UPLOADS_DIRECTORY' => '',
			'NEXT_PUBLIC_REST_URL_PREFIX'      => '',
		];

		$generator = new Generator( $values, $registry );

		// Generate the .env content.
		$content = $generator->generate();

		// Define expected content.
		$expectedContent = '
# Only enable if connecting to a self-signed cert
NODE_TLS_REJECT_UNAUTHORIZED=0

# The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: /proxy, This means for script module next app will make request NEXT_PUBLIC_FRONTEND_URL/proxy/{module-path}
# NEXT_PUBLIC_CORS_PROXY_PREFIX=/proxy

# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.
NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000

# The WordPress "frontend" domain URL e.g. https://my-headless-site.local
NEXT_PUBLIC_WP_HOME_URL=https://headless-demo.local

# The WordPress GraphQL endpoint
NEXT_PUBLIC_GRAPHQL_ENDPOINT=/test_endpoint

# The WordPress Uploads directory path
# NEXT_PUBLIC_WP_UPLOADS_DIRECTORY=/wp-content/uploads

# The WordPress REST URL Prefix
# NEXT_PUBLIC_REST_URL_PREFIX=/wp-json';

		$this->assertSame( $expectedContent, $content );
	}
}
