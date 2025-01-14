<?php
/**
 * Integration tests for IntrospectionToken class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;

/**
 * Class - IntrospectionTokenTest
 */
class IntrospectionTokenTest extends WPTestCase {
	/**
	 * Option key for storing the token.
	 */
	private const TOKEN_OPTION_KEY = 'snapwp_helper_introspection_token';

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear the token option before each test.
		delete_option( self::TOKEN_OPTION_KEY );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		// After each test, make sure we clean up by deleting any token set.
		delete_option( self::TOKEN_OPTION_KEY );

		parent::tearDown();
	}

	/**
	 * Test that a new token is generated and stored when no token exists.
	 */
	public function testGenerateTokenWhenNoTokenExists(): void {
		$token = IntrospectionToken::get_token();

		$this->assertIsString( $token, 'The generated token should be a string.' );
		$this->assertNotEmpty( $token, 'The generated token should not be empty.' );

		$storedToken = get_option( self::TOKEN_OPTION_KEY );
		$this->assertNotEmpty( $storedToken, 'The token should be stored in the database.' );
		$this->assertNotSame( $token, $storedToken, 'The stored token should be encrypted.' );
	}

	/**
	 * Test retrieving and decrypting an existing token.
	 */
	public function testRetrieveExistingToken(): void {
		$originalToken = IntrospectionToken::generate_token();

		$this->assertIsString( $originalToken, 'The generated token should be a string.' );
		$this->assertNotEmpty( $originalToken, 'The generated token should not be empty.' );

		$retrievedToken = IntrospectionToken::get_token();

		$this->assertSame( $originalToken, $retrievedToken, 'The retrieved token should match the original token.' );
	}

	/**
	 * Test token update failure handling.
	 */
	public function testTokenUpdateFailure(): void {
		// Mock update_option to return false (simulate failure).
		add_filter( 'pre_update_option_' . self::TOKEN_OPTION_KEY, static fn () => false );

		$result = IntrospectionToken::generate_token();

		$this->assertInstanceOf( \WP_Error::class, $result, 'A WP_Error should be returned on update failure.' );
		$this->assertSame( 'token_update_failed', $result->get_error_code(), 'Error code should indicate update failure.' );

		// Remove the mock.
		remove_filter( 'pre_update_option_' . self::TOKEN_OPTION_KEY, '__return_false' );
	}
}
