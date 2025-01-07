<?php
/**
 * Token manager for generating and storing introspection tokens.
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL;

/**
 * Class - TokenManager
 */
class TokenManager {
	// Option name for storing the token.
	private const SNAPWP_INTROSPECTION_TOKEN = 'snapwp_helper_introspection_token';

	/**
	 * Generate a new introspection token, encrypt it, and store it.
	 *
	 * @return string The generated token.
	 */
	public static function generate_token(): string {
		// Generate a random token.
		$token = bin2hex( random_bytes( 32 ) );

		// Encrypt and store the token in the database.
		self::store_token( $token );

		return $token;
	}

	/**
	 * Store the encrypted introspection token in the database.
	 *
	 * @param string $token The token to store.
	 */
	private static function store_token( string $token ): void {
		$encrypted_token = self::encrypt_token( $token );

		// Store the encrypted token in the options table.
		update_option( self::SNAPWP_INTROSPECTION_TOKEN, $encrypted_token );
	}

	/**
	 * Retrieve and decrypt the introspection token from the database.
	 *
	 * @return string The decrypted token.
	 */
	public static function get_token(): ?string {
		// Retrieve the encrypted token.
		$encrypted_token = get_option( self::SNAPWP_INTROSPECTION_TOKEN );

		// If the token is not found, generate a new one.
		if ( ! $encrypted_token ) {
			$encrypted_token = self::generate_token();
			$encrypted_token = get_option( self::SNAPWP_INTROSPECTION_TOKEN );
		}

		// Decrypt the token.
		$decrypted_token = self::decrypt_token( $encrypted_token );

		return $decrypted_token;
	}

	/**
	 * Encrypt the introspection token.
	 *
	 * @param string $token The token to encrypt.
	 *
	 * @throws \Exception If the token cannot be encrypted.
	 *
	 * @return string The encrypted token.
	 */
	private static function encrypt_token( string $token ): string {
		// Encryption key and initialization vector (IV).
		$encryption_key = 'rtcamp_snapwp_helper';
		$iv             = 'iv_1234567890123456';

		// Encrypt the token.
		$encrypted_token = openssl_encrypt( $token, 'aes-256-cbc', $encryption_key, 0, $iv );

		if ( false === $encrypted_token ) {
			throw new \Exception( 'Failed to encrypt the token.' );
		}

		return $encrypted_token;
	}

	/**
	 * Decrypt the introspection token.
	 *
	 * @param string $encrypted_token The encrypted token to decrypt.
	 *
	 * @throws \Exception If the token cannot be decrypted.
	 *
	 * @return string The decrypted token.
	 */
	private static function decrypt_token( string $encrypted_token ): string {

		// Decrypt the token.
		$encryption_key = 'rtcamp_snapwp_helper';
		$iv             = 'iv_1234567890123456';

		$decrypted_token = openssl_decrypt( $encrypted_token, 'aes-256-cbc', $encryption_key, 0, $iv );

		if ( false === $decrypted_token ) {
			throw new \Exception( 'Failed to decrypt the token.' );
		}

		return $decrypted_token;
	}
}
