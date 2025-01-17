<?php
/**
 * Introspection token manager for generating and storing tokens.
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Data;

/**
 * Class - IntrospectionToken
 */
class IntrospectionToken {
	// Option name for storing the token.
	private const SNAPWP_INTROSPECTION_TOKEN = 'snapwp_helper_introspection_token';

	/**
	 * Retrieve and decrypt the introspection token from the database.
	 *
	 * @return string|\WP_Error The decrypted token or an error object.
	 */
	public static function get_token() {
		// Retrieve the encrypted token.
		$token = get_option( self::SNAPWP_INTROSPECTION_TOKEN );

		// If we have a token, decrypt it.
		if ( ! empty( $token ) ) {
			return self::decrypt_token( $token );
		}

		// Otherwise, generate a new token.
		return self::generate_token();
	}

	/**
	 * Generate a new introspection token, encrypt it, and store it.
	 *
	 * @return string|\WP_Error The new token or an error object.
	 */
	public static function generate_token() {
		// Generate a new token.
		$token = bin2hex( random_bytes( 32 ) );

		// Store the new token in the database.
		$updated = self::update_token( $token );

		// Return the WP_Error if it fails.
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		return $token;
	}

	/**
	 * Store the encrypted introspection token in the database.
	 *
	 * @param string $token The token to store.
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	private static function update_token( string $token ) {
		$encrypted_token = self::encrypt_token( $token );

		if ( is_wp_error( $encrypted_token ) ) {
			return $encrypted_token;
		}

		// Store the encrypted token in the options table.
		$updated = update_option( self::SNAPWP_INTROSPECTION_TOKEN, $encrypted_token );

		if ( ! $updated ) {
			return new \WP_Error( 'token_update_failed', __( 'Failed to update the introspection token.', 'snapwp-helper' ) );
		}

		return true;
	}

	/**
	 * Encrypt the introspection token.
	 *
	 * @param string $token The token to encrypt.
	 *
	 * @return string|\WP_Error The encrypted token or an error object.
	 */
	private static function encrypt_token( string $token ) {
		// Bail if the server doesn't support openssl.
		if ( ! extension_loaded( 'openssl' ) ) {
			return $token;
		}

		// CTR is faster than CBC.
		$method    = 'aes-256-ctr';
		$iv_length = (int) openssl_cipher_iv_length( $method );
		$iv        = (string) openssl_random_pseudo_bytes( $iv_length );

		$encrypted_value = openssl_encrypt(
			// Make it salty.
			$token . self::get_encryption_salt(),
			$method,
			self::get_encryption_key(),
			0,
			$iv
		);

		if ( false === $encrypted_value ) {
			return new \WP_Error( 'token_encryption_failed', __( 'Failed to encrypt the token.', 'snapwp-helper' ) );
		}

		return base64_encode( $iv . $encrypted_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This ensures the encrypted value is transmittable.
	}

	/**
	 * Decrypt the introspection token.
	 *
	 * @param string $token The encrypted token to decrypt.
	 *
	 * @return string|\WP_Error The decrypted token or an error object.
	 */
	private static function decrypt_token( string $token ) {
		// Bail if the server doesn't support openssl.
		if ( ! extension_loaded( 'openssl' ) ) {
			return $token;
		}

		$token = base64_decode( $token, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Encoded in ::encrypt_token().

		if ( false === $token ) {
			// Obfuscate the error message to prevent leaking sensitive information.
			return new \WP_Error( 'token_decryption_failed', __( 'Failed to decrypt the token.', 'snapwp-helper' ) );
		}

		$method    = 'aes-256-ctr';
		$iv_length = (int) openssl_cipher_iv_length( 'aes-256-ctr' );

		// Get the token and IV out of the encrypted value.
		$iv    = substr( $token, 0, $iv_length );
		$token = substr( $token, $iv_length );

		$decrypted_value = openssl_decrypt(
			$token,
			$method,
			self::get_encryption_key(),
			0,
			$iv
		);

		if ( false === $decrypted_value ) {
			return new \WP_Error( 'token_decryption_failed', __( 'Failed to decrypt the token.', 'snapwp-helper' ) );
		}

		$salt = self::get_encryption_salt();

		// Ensure it's salted.
		if ( substr( $decrypted_value, -strlen( $salt ) ) !== $salt ) {
			return new \WP_Error( 'token_decryption_failed', __( 'Failed to decrypt the token.', 'snapwp-helper' ) );
		}

		// Return it unsalted.
		return substr( $decrypted_value, 0, -strlen( $salt ) );
	}

	/**
	 * Gets the encryption key to use.
	 */
	private static function get_encryption_key(): string {
		// Use a custom key if defined.
		if ( defined( 'SNAPWP_ENCRYPTION_KEY' ) && '' !== SNAPWP_ENCRYPTION_KEY ) {
			return SNAPWP_ENCRYPTION_KEY;
		}

		// Reuse the LOGGED_IN_KEY if it's defined.
		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return LOGGED_IN_KEY;
		}

		// If there's no key, you probably have a security issue.
		return 'can-you-keep-a-secret';
	}

	/**
	 * Gets the salt to use for encryption.
	 */
	private static function get_encryption_salt(): string {
		// Use a custom salt if defined.
		if ( defined( 'SNAPWP_ENCRYPTION_SALT' ) && '' !== SNAPWP_ENCRYPTION_SALT ) {
			return SNAPWP_ENCRYPTION_SALT;
		}

		// Reuse the LOGGED_IN_SALT if it's defined.
		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return LOGGED_IN_SALT;
		}

		// If there's no salt, you probably have a security issue.
		return 'please-pass-the-salt';
	}
}
