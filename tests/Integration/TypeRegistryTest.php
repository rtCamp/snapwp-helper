<?php
/**
 * Tests the TypeRegistry class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\GraphQL\TypeRegistry;

/**
 * Tests the TypeRegistry class.
 */
class TypeRegistryTest extends WPTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_registry();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->clear_registry();

		parent::tearDown();
	}

	/**
	 * Tests the Registry is initialized.
	 */
	public function testRegistryIsInitialized(): void {
		do_action( 'graphql_init' );

		$actual = $this->get_registry_property();

		$this->assertIsArray( $actual );

		$this->clear_registry();

		TypeRegistry::instance()->init();

		$actual = $this->get_registry_property();

		$this->assertIsArray( $actual );

		// @todo Add more assertions when the registry is populated.
	}

	/**
	 * Tests get_registered_types method.
	 */
	public function testGetRegisteredTypes(): void {
		$actual = TypeRegistry::instance()->get_registered_types();

		$this->assertIsArray( $actual );

		// @todo Add more assertions when the registry is populated.
	}

	/**
	 * Tests register_types method.
	 */
	public function testRegisterTypes(): void {
		$reflection = new \ReflectionClass( TypeRegistry::class );
		$method = $reflection->getMethod( 'register_types' );
		$method->setAccessible( true );

		// Test with empty array.
		$method->invoke( TypeRegistry::instance(), [] );

		// Test with a bad class.
		$classes_to_register = [
			'bad_class',
		];
		$this->expectException(\Exception::class);
		$method->invoke( TypeRegistry::instance(), $classes_to_register );

		// The valid case is proven by the other tests.
	}

	/**
	 * Clears the registry singleton.
	 */
	protected function clear_registry(): void {
		$reflection = new \ReflectionClass( TypeRegistry::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null );
	}

	protected function get_registry_property(): array {
		$reflection = new \ReflectionClass( TypeRegistry::class );
		$property   = $reflection->getProperty( 'registry' );
		$property->setAccessible( true );

		return $property->getValue( TypeRegistry::instance() );
	}
}
