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
 * Class - CustomDisableIntrospection
 */
class CustomDisableIntrospection extends ValidationRulesDisableIntrospection {
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
			return $enabled;
		}

		// Retrieve the custom "Introspection-Token" header from $_SERVER.
		$introspection_token_header = isset( $_SERVER['HTTP_INTROSPECTION_TOKEN'] ) ? sanitize_text_field( $_SERVER['HTTP_INTROSPECTION_TOKEN'] ) : '';

		// Check if the provided token matches the one stored in the database.
		if ( ! empty( $introspection_token_header ) && $introspection_token_header === $introspection_token ) {
			$enabled = true;
		}

		return $enabled;
	}
}
