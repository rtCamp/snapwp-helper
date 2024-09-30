<?php
/**
 * Global functions for the SnapWP Helper plugin.
 *
 * @package SnapWP\Helper
 */

declare(strict_types=1);

use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;

if ( ! function_exists( 'snapwp_helper_generate_env_content' ) ) {
	/**
	 * Generates the .env file content based on the provided variables.
	 *
	 * @param array<key-of<\SnapWP\Helper\Modules\EnvGenerator\VariableRegistry::VARIABLES>,string> $variables The variables to generate the .env content.
	 *
	 * @return string|\WP_Error The .env file content or an error object.
	 */
	function snapwp_helper_generate_env_content( array $variables ) {
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

		return [
			'NODE_TLS_REJECT_UNAUTHORIZED' => '',
			'NEXT_URL'                     => '',
			'HOME_URL'                     => get_home_url(),
			'GRAPHQL_ENDPOINT'             => graphql_get_endpoint(),
		];
	}
}
