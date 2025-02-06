<?php
/**
 * Tests the Dependencies class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Dependencies;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests the Dependencies class.
 */
class DependenciesTest extends WPTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_dependencies();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->clear_dependencies();

		parent::tearDown();
	}

	/**
	 * Tests the register_dependency method.
	 */
	public function testRegisterDependency(): void {
		Dependencies::register_dependency(
			[
				'slug'           => 'test-register-good',
				'name'           => 'Test Good Dependency',
				'check_callback' => static function () {
					return true;
				},
			],
		);
		Dependencies::register_dependency(
			[
				'slug'           => 'test-register-bad',
				'name'           => 'Test Bad Dependency',
				'check_callback' => static function () {
					return new \WP_Error( 'test-error', 'Test Error' );
				},
			]
		);
		Dependencies::register_dependency(
			[
				'slug'           => 'test-register-exception',
				'name'           => 'Test Exception Dependency',
				'check_callback' => static function () {
					throw new \Exception( 'Test Exception' );
				},
			]
		);

		// Manually initialize the dependency class.
		Dependencies::instance()->init();

		// Check that the "good" dependency is registered and met.
		$is_good_registered = Dependencies::is_dependency_met( 'test-register-good' );

		$this->assertTrue( $is_good_registered );

		// Check that the "bad" dependency is registered and not met.
		$is_bad_registered = Dependencies::is_dependency_met( 'test-register-bad' );

		$this->assertFalse( $is_bad_registered );

		$actual_error_message = Dependencies::instance()->get_dependency_error_message( 'test-register-bad' );

		$this->assertSame( 'Test Error', $actual_error_message );

		// Check that the "invalid" dependency is registered and not met.
		$is_invalid_registered = Dependencies::is_dependency_met( 'test-register-exception' );

		$this->assertFalse( $is_invalid_registered );

		$actual_error_message = Dependencies::instance()->get_dependency_error_message( 'test-register-exception' );

		$this->assertStringStartsWith( 'The check for the Test Exception Dependency plugin failed.', $actual_error_message );

		// Check that the admin notice is displayed.
		Dependencies::instance()->check_and_display_admin_notice();

		ob_start();
		do_action( 'admin_notices' );

		$actual_output = ob_get_clean();

		$this->assertStringNotContainsString( 'Test Good Dependency', $actual_output );
		$this->assertStringContainsString( 'Test Bad Dependency', $actual_output );
		$this->assertStringContainsString( 'Test Exception Dependency', $actual_output );

		// Check that the "non-existent" dependency is not registered.
		$this->expectException( \InvalidArgumentException::class );
		$is_non_existent_registered = Dependencies::is_dependency_met( 'test-register-non-existent' );
	}

	/**
	 * Tests register_dependencies with invalid slug.
	 */
	public function testRegisterDependenciesWithInvalidSlug(): void {
		$this->expectException( \InvalidArgumentException::class );

		Dependencies::register_dependency(
			[
				'slug'           => '',
				'name'           => 'Test Name',
				'check_callback' => static function () {
					return true;
				},
			],
		);
	}

	/**
	 * Tests register_dependencies with invalid name.
	 */
	public function testRegisterDependenciesWithInvalidName(): void {
		$this->expectException( \InvalidArgumentException::class );

		Dependencies::register_dependency(
			[
				'slug'           => 'test-register-good',
				'name'           => '',
				'check_callback' => static function () {
					return true;
				},
			],
		);
	}

	/**
	 * Tests register_dependencies with invalid check_callback.
	 */
	public function testRegisterDependenciesWithInvalidCheckCallback(): void {
		$this->expectException( \InvalidArgumentException::class );

		Dependencies::register_dependency(
			[
				'slug'           => 'test-register-good',
				'name'           => 'Test Name',
				'check_callback' => 'invalid',
			],
		);
	}

	/**
	 * Clears the dependencies singleton.
	 */
	protected function clear_dependencies(): void {
		$reflection = new \ReflectionClass( Dependencies::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null );
	}
}
