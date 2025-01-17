<?php
/**
 * Global functions for the SnapWP Helper plugin.
 *
 * @package SnapWP\Helper
 */

declare(strict_types=1);

use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;
use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;

if ( ! function_exists( 'snapwp_helper_get_env_content' ) ) {
	/**
	 * Generates the .env file content based on the provided variables.
	 *
	 * @return string|\WP_Error The .env file content or an error object.
	 */
	function snapwp_helper_get_env_content() {

		// Fetch the environment variables and check for errors.
		$variables = snapwp_helper_get_env_variables();
		if ( is_wp_error( $variables ) ) {
			return new \WP_Error(
				'env_variables_error',
				$variables->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		$generator = new Generator( $variables, new VariableRegistry() );

		// Generate and return the content for env file.
		$content = null;

		try {
			$content = $generator->generate();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'env_generation_failed', $e->getMessage() );
		}

		if ( empty( $content ) ) {
			return new \WP_Error( 'env_generation_failed', 'No content generated.' );
		}

		return $content;
	}
}

if ( ! function_exists( 'snapwp_helper_get_env_variables' ) ) {
	/**
	 * Get the list of environment variables.
	 *
	 * @return array<key-of<\SnapWP\Helper\Modules\EnvGenerator\VariableRegistry::VARIABLES>,string>|\WP_Error The environment variables and their values.
	 */
	function snapwp_helper_get_env_variables() {
		if ( ! function_exists( 'graphql_get_endpoint' ) ) {
			return new \WP_Error( 'graphql_not_found', 'WPGraphQL must be installed and activated.', [ 'status' => 500 ] );
		}

		// Get the introspection token.
		$token = IntrospectionToken::get_token();

		// Bail if we couldn't get the token.
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$upload_dir = wp_get_upload_dir();

		return [
			'NODE_TLS_REJECT_UNAUTHORIZED'          => '',
			'NEXT_PUBLIC_URL'                       => '',
			'NEXT_PUBLIC_WORDPRESS_URL'             => snapwp_helper_sanitize_url( get_home_url() ),
			'NEXT_PUBLIC_GRAPHQL_ENDPOINT'          => graphql_get_endpoint(),
			'NEXT_PUBLIC_WORDPRESS_UPLOADS_PATH'    => snapwp_helper_sanitize_path( str_replace( ABSPATH, '', $upload_dir['basedir'] ) ),
			'NEXT_PUBLIC_WORDPRESS_REST_URL_PREFIX' => snapwp_helper_sanitize_path( rest_get_url_prefix() ),
			'INTROSPECTION_TOKEN'                   => $token,
		];
	}

	/**
	 * Ensure the URL has the correct protocol and trailing slash.
	 *
	 * @param string $url The URL to sanitize.
	 *
	 * @return string The sanitized URL.
	 */
	function snapwp_helper_sanitize_url( $url ) {
		// Ensure the URL has a protocol.
		if ( ! preg_match( '/^https?:\/\//', $url ) ) {
			$url = 'http://' . ltrim( $url, '/' );
		}

		// Ensure the URL ends with a trailing slash.
		return rtrim( $url, '/' ) . '/';
	}

	/**
	 * Ensure the path is correctly formatted (no leading ABSPATH).
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string The sanitized path.
	 */
	function snapwp_helper_sanitize_path( $path ) {
		// Ensure the path is relative (remove ABSPATH prefix) with a leading slash.
		return '/' . ltrim( str_replace( ABSPATH, '', $path ), '/' );
	}
}
