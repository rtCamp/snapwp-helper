<?php
/**
 * Extends the DisableIntrospection validation rule to check for an introspection token.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Server
 *
 * @todo This can be cleaned up once we only support WPGraphQL 2.0.0+.
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
	 *
	 * @return bool
	 */
	public function should_be_enabled(): bool {
		// Check the original conditions first.
		// @todo Remove the conditional once we only support WPGraphQL 2.0.0+.
		$is_rule_enabled = version_compare( WPGRAPHQL_VERSION, '2.0.0', '<' ) ? $this->local_should_be_enabled() : parent::should_be_enabled();

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

	/**
	 * {@inheritDoc}
	 *
	 * Overloaded to use our `should_be_enabled()` on older versions of WPGraphQL.
	 */
	public function isEnabled(): bool {
		if ( version_compare( WPGRAPHQL_VERSION, '2.0.0', '<' ) ) {
			return $this->should_be_enabled();
		}

		return parent::isEnabled();
	}

	/**
	 * Mocks the parent `should_be_enabled()` in case we're using an old version of WPGraphQL.
	 *
	 * @see https://github.com/wp-graphql/wp-graphql/blob/aedd4f6abab185974ce512b5dc56f2a766b41618/src/Server/ValidationRules/DisableIntrospection.php#L23
	 */
	private function local_should_be_enabled(): bool {
		if ( ! get_current_user_id() && ! \WPGraphQL::debug() && 'off' === get_graphql_setting( 'public_introspection_enabled', 'off' ) ) {
			return true;
		}

		return false;
	}
}
