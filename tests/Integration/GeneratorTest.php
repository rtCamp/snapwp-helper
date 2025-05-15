<?php
/**
 * Tests the Generator class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use ReflectionClass;
use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;
use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Class GeneratorTest
 */
class GeneratorTest extends IntegrationTestCase {
	/**
	 * Tests if the Generator class initializes properly.
	 */
	public function testGeneratorInitialization(): void {
		$registry  = new VariableRegistry();
		$generator = new Generator( $registry );

		$this->assertInstanceOf( Generator::class, $generator );
	}

	/**
	 * Tests if the Generator generates the correctly formatted .ENV content.
	 */
	public function testGenerateEnvContent(): void {
		// Create a custom VariableRegistry with test variables.
		$registry = new VariableRegistry();

		// Use reflection to modify the private variables property.
		$reflection     = new ReflectionClass( $registry );
		$variables_prop = $reflection->getProperty( 'variables' );
		$variables_prop->setAccessible( true );

		// Get current variables to modify them.
		$variables = $variables_prop->getValue( $registry );

		// Set specific test values.
		$variables['NODE_TLS_REJECT_UNAUTHORIZED']['value']     = '5';
		$variables['NEXT_PUBLIC_FRONTEND_URL']['value']         = 'http://localhost:3000';
		$variables['NEXT_PUBLIC_WP_HOME_URL']['value']          = 'https://headless-demo.local';
		$variables['GRAPHQL_ENDPOINT']['value']     = '/test_endpoint';
		$variables['WP_UPLOADS_DIRECTORY']['value'] = 'uploads';
		$variables['REST_URL_PREFIX']['value']      = 'api';
		$variables['INTROSPECTION_TOKEN']['value']              = '0123456789';

		// Update the registry.
		$variables_prop->setValue( $registry, $variables );

		// Create generator with our modified registry.
		$generator = new Generator( $registry );
		$content   = $generator->generate();

		// Ensure content is generated.
		$this->assertNotNull( $content );
		$this->assertIsString( $content );

		// Check for expected values.
		$this->assertStringContainsString( 'NODE_TLS_REJECT_UNAUTHORIZED=5', $content );
		$this->assertStringContainsString( 'NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000', $content );
		$this->assertStringContainsString( 'NEXT_PUBLIC_WP_HOME_URL=https://headless-demo.local', $content );
		$this->assertStringContainsString( 'GRAPHQL_ENDPOINT=/test_endpoint', $content );
		$this->assertStringContainsString( 'INTROSPECTION_TOKEN=0123456789', $content );
	}

	/**
	 * Tests if the Generator class throws correct error when missing required values.
	 */
	public function testMissingRequiredValuesEnvContent(): void {
		// Create registry with a required variable that has no value.
		$registry = new VariableRegistry();

		// Use reflection to modify the private variables property.
		$reflection     = new ReflectionClass( $registry );
		$variables_prop = $reflection->getProperty( 'variables' );
		$variables_prop->setAccessible( true );

		// Create a test variable that is required but has no value.
		$variables = [
			'TEST_REQUIRED_VAR' => [
				'description' => 'Required variable that must have a value',
				'default'     => null,
				'outputMode'  => VariableRegistry::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => '', // Empty value should trigger the exception
			],
		];

		// Set the variables.
		$variables_prop->setValue( $registry, $variables );

		$generator = new Generator( $registry );

		// Expect an exception when calling generate() because of missing required values.
		$this->expectException( \InvalidArgumentException::class );
		$generator->generate();
	}

	/**
	 * Tests if the Generator class handles commented-out variables correctly.
	 */
	public function testDefaultValuesForEnvContent(): void {
		// Create registry with variables that should be commented out.
		$registry = new VariableRegistry();

		// Use reflection to modify the private variables property.
		$reflection     = new ReflectionClass( $registry );
		$variables_prop = $reflection->getProperty( 'variables' );
		$variables_prop->setAccessible( true );

		// Create test variables.
		$variables = [
			'NODE_TLS_REJECT_UNAUTHORIZED'     => [
				'description' => 'Only enable if connecting to a self-signed cert',
				'default'     => '0',
				'outputMode'  => VariableRegistry::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => '0',
			],
			'NEXT_PUBLIC_FRONTEND_URL'         => [
				'description' => 'The headless frontend domain URL',
				'default'     => 'http://localhost:3000',
				'outputMode'  => VariableRegistry::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => 'http://localhost:3000',
			],
			'CORS_PROXY_PREFIX'    => [
				'description' => 'The CORS proxy prefix',
				'default'     => '/proxy',
				'outputMode'  => VariableRegistry::OUTPUT_COMMENTED,
				'required'    => false,
				'value'       => '', // Empty value should show default in commented output.
			],
			'REST_URL_PREFIX'      => [
				'description' => 'The WordPress REST URL Prefix',
				'default'     => '/wp-json',
				'outputMode'  => VariableRegistry::OUTPUT_COMMENTED,
				'required'    => false,
				'value'       => '', // Empty value should show default in commented output.
			],
			'WP_UPLOADS_DIRECTORY' => [
				'description' => 'The WordPress Uploads directory path',
				'default'     => '/wp-content/uploads',
				'outputMode'  => VariableRegistry::OUTPUT_COMMENTED,
				'required'    => false,
				'value'       => '', // Empty value should show default in commented output.
			],
		];

		// Set the variables.
		$variables_prop->setValue( $registry, $variables );

		$generator = new Generator( $registry );
		$content   = $generator->generate();

		// Ensure content is generated.
		$this->assertNotNull( $content );
		$this->assertIsString( $content );

		// Check for standard values.
		$this->assertStringContainsString( 'NODE_TLS_REJECT_UNAUTHORIZED=0', $content );
		$this->assertStringContainsString( 'NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000', $content );

		// Check that commented variables use their default values.
		$this->assertStringContainsString( '# CORS_PROXY_PREFIX=/proxy', $content );
		$this->assertStringContainsString( '# REST_URL_PREFIX=/wp-json', $content );
		$this->assertStringContainsString( '# WP_UPLOADS_DIRECTORY=/wp-content/uploads', $content );
	}
}
