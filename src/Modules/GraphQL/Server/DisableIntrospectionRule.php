<?php
/**
 * Extends the DisableIntrospection validation rule to check for an introspection token.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Server
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Server;

use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;
use WPGraphQL\Server\ValidationRules\DisableIntrospection as ValidationRulesDisableIntrospection;

/**
 * Class - DisableIntrospectionRule
 */
class DisableIntrospectionRule extends ValidationRulesDisableIntrospection {
	/**
	 * {@inheritDoc}
	 *
	 * Overridden to validate the introspection token.
	 */
	public function isEnabled() {
		// Check the original conditions first.
		$is_rule_enabled = parent::isEnabled();

		// Get the authorization header.
		$introspection_token_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : '';

		// Bail early if the header is empty or introspection is already allowed.
		if ( empty( $introspection_token_header ) || ! $is_rule_enabled ) {
			return $is_rule_enabled;
		}

		// Get the introspection token from the database.
		$introspection_token = IntrospectionToken::get_token();

		// If there was an error retrieving the token, return true.
		if ( is_wp_error( $introspection_token ) ) {
			return true;
		}

		// Check if the provided token matches the one stored in the database.
		return ! hash_equals( $introspection_token_header, $introspection_token );
	}
}
