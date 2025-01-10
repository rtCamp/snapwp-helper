<?php
/**
 * Custom validation rules for the WPGraphQL server.
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL;

use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;
use WPGraphQL\Server\ValidationRules\DisableIntrospection as ValidationRulesDisableIntrospection;

/**
 * Class - DisableIntrospection
 */
class DisableIntrospection extends ValidationRulesDisableIntrospection {
	/**
	 * Override the isEnabled method to add custom token validation.
	 *
	 * @return bool
	 */
	public function isEnabled() {
		// Check the original conditions first.
		$enabled = parent::isEnabled();

		// Get the introspection token from the database.
		$introspection_token = IntrospectionToken::get_token();

		if ( is_wp_error( $introspection_token ) ) {
			// If there was an error retrieving the token, return the original value.
			return false;
		}

		// Retrieve the custom "Introspection-Token" header from $_SERVER.
		$introspection_token_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : '';

		// Check if the provided token matches the one stored in the database.
		if ( ! empty( $introspection_token_header ) && hash_equals( $introspection_token_header, $introspection_token ) ) {
			return true;
		}

		return $enabled;
	}
}
