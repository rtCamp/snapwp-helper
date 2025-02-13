<?php
/**
 * Tests the Generator class initialization.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Class GeneratorTest
 *
 * @package SnapWP\Helper\Tests\Integration
 */
class GeneratorTest extends WPTestCase {
	/**
	 * Tests if the Generator class initializes properly.
	 */
	public function testGeneratorInitialization(): void {
		$registry = new VariableRegistry();

		$values = [
			'NODE_TLS_REJECT_UNAUTHORIZED'          => '',
			'NEXT_PUBLIC_URL'                       => 'http://localhost:3000',
			'NEXT_PUBLIC_WORDPRESS_URL'             => 'https://headless-demo.local',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'          => '',
			'NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH'    => '',
			'NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX' => '',
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
			'NODE_TLS_REJECT_UNAUTHORIZED'          => '5',
			'NEXT_PUBLIC_URL'                       => 'http://localhost:3000',
			'NEXT_PUBLIC_WORDPRESS_URL'             => 'https://headless-demo.local',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'          => '/test_endpoint',
			'NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH'    => 'uploads',
			'NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX' => 'api',
			'INVALID_VARIABLE'                      => 'should-not-be-included', // This should not be included in the output.
		];

		$generator = new Generator( $values, $registry );

		// Generate the .env content.
		$content = $generator->generate();

		$expectedContent = '
# Only enable if connecting to a self-signed cert
NODE_TLS_REJECT_UNAUTHORIZED=5

# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.
NEXT_PUBLIC_URL=http://localhost:3000

# The WordPress "frontend" domain URL
NEXT_PUBLIC_WORDPRESS_URL=https://headless-demo.local

# The WordPress GraphQL endpoint
NEXT_PUBLIC_GRAPHQL_ENDPOINT=/test_endpoint

# The WordPress Uploads directory path
NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH=uploads

# The WordPress REST URL Prefix
NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX=api';

		$this->assertSame( $expectedContent, $content );
	}

	/**
	 * Tests if the Generator class throws correct error when missing required values.
	 */
	public function testMissingRequiredValuesEnvContent(): void {
		$registry = new VariableRegistry();
		$values   = [
			'NODE_TLS_REJECT_UNAUTHORIZED'          => '',
			'NEXT_PUBLIC_URL'                       => '',
			'NEXT_PUBLIC_WORDPRESS_URL'             => '',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'          => '',
			'NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH'    => '',
			'NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX' => '',
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
			'NODE_TLS_REJECT_UNAUTHORIZED'          => '0',
			'NEXT_PUBLIC_URL'                       => 'http://localhost:3000',
			'NEXT_PUBLIC_WORDPRESS_URL'             => 'https://headless-demo.local',
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'          => '/test_endpoint',
			'NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH'    => '',
			'NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX' => '',
		];

		$generator = new Generator( $values, $registry );

		// Generate the .env content.
		$content = $generator->generate();

		// Define expected content.
		$expectedContent = '
# Only enable if connecting to a self-signed cert
NODE_TLS_REJECT_UNAUTHORIZED=0

# The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.
NEXT_PUBLIC_URL=http://localhost:3000

# The WordPress "frontend" domain URL
NEXT_PUBLIC_WORDPRESS_URL=https://headless-demo.local

# The WordPress GraphQL endpoint
NEXT_PUBLIC_GRAPHQL_ENDPOINT=/test_endpoint

# The WordPress Uploads directory path
# NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH=/wp-content/uploads

# The WordPress REST URL Prefix
# NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX=/wp-json';

		$this->assertSame( $expectedContent, $content );
	}
}
