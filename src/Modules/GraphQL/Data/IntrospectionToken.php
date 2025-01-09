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
		$encrypted_token = get_option( self::SNAPWP_INTROSPECTION_TOKEN );

		// If the token is not found, generate a new one.
		if ( empty( $encrypted_token ) ) {
			$encrypted_token = self::generate_token();
			$encrypted_token = get_option( self::SNAPWP_INTROSPECTION_TOKEN );
		}

		// Decrypt the token.
		return self::decrypt_token( $encrypted_token );
	}

	/**
	 * Generate a new introspection token, encrypt it, and store it.
	 *
	 * @return string|\WP_Error The new token or an error object.
	 */
	private static function generate_token() {
		// Delete token if it exists.
		$delete_result = ( get_option( self::SNAPWP_INTROSPECTION_TOKEN ) ) ? self::delete_token() : true;

		if ( is_wp_error( $delete_result ) ) {
			// Return the WP_Error from the delete operation if it fails.
			return $delete_result;
		}

		// Generate a new token.
		$new_token = bin2hex( random_bytes( 32 ) );

		// Store the new token in the database.
		$updated = self::update_token( $new_token );

		if ( is_wp_error( $updated ) ) {
			// Return the WP_Error from the store operation if it fails.
			return $updated;
		}

		return $new_token;
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
	 * Delete the existing token from the database.
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	private static function delete_token() {
		$delete = delete_option( self::SNAPWP_INTROSPECTION_TOKEN );

		if ( false === $delete ) {
			// Return an error if the token could not be deleted.
			return new \WP_Error(
				'token_deletion_failed',
				__( 'Failed to delete the existing token. It may not exist.', 'snapwp-helper' )
			);
		}

		// Return true if the token was successfully deleted or didn't exist.
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
		$encryption_key = defined( 'ENCRYPTION_KEY' ) ? ENCRYPTION_KEY : '';
		$iv             = defined( 'ENCRYPTION_IV' ) ? ENCRYPTION_IV : '';

		// Encrypt the token.
		$encrypted_token = openssl_encrypt( $token, 'aes-256-cbc', $encryption_key, 0, $iv );

		if ( false === $encrypted_token ) {
			return new \WP_Error( 'token_encryption_failed', 'Failed to encrypt the token.' );
		}

		return $encrypted_token;
	}

	/**
	 * Decrypt the introspection token.
	 *
	 * @param string $encrypted_token The encrypted token to decrypt.
	 *
	 * @return string|\WP_Error The decrypted token or an error object.
	 */
	private static function decrypt_token( string $encrypted_token ) {
		$encryption_key = defined( 'ENCRYPTION_KEY' ) ? ENCRYPTION_KEY : '';
		$iv             = defined( 'ENCRYPTION_IV' ) ? ENCRYPTION_IV : '';

		$decrypted_token = openssl_decrypt( $encrypted_token, 'aes-256-cbc', $encryption_key, 0, $iv );

		if ( false === $decrypted_token ) {
			return new \WP_Error( 'token_decryption_failed', 'Failed to decrypt the token.' );
		}

		return $decrypted_token;
	}
}
