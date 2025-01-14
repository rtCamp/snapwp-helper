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
	 * {@inheritDoc}
	 *
	 * Overridden to validate the introspection token.
	 *
	 * @return bool
	 */
	public function isEnabled() {
		// Check the original conditions first.
		$enabled = parent::isEnabled();

		// Get the authorization header.
		$introspection_token_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : '';

		// Bail early if the header is empty or introspection is already enabled.
		if ( empty( $introspection_token_header ) || $enabled ) {
			return $enabled;
		}

		// Get the introspection token from the database.
		$introspection_token = IntrospectionToken::get_token();

		// If there was an error retrieving the token, return the original value.
		if ( is_wp_error( $introspection_token ) ) {
			return false;
		}

		// Check if the provided token matches the one stored in the database.
		return hash_equals( $introspection_token_header, $introspection_token );
	}
}
