<?php
/**
 * Global functions for the SnapWP Helper plugin.
 *
 * @package SnapWP\Helper
 *
 * @phpstan-import-type SnapWP\Helper\Modules\EnvGenerator\VariableConfig from \SnapWP\Helper\Modules\EnvGenerator\VariableConfig
 */

declare(strict_types=1);

use SnapWP\Helper\Modules\EnvGenerator\Generator;
use SnapWP\Helper\Modules\EnvGenerator\VariableRegistry;

if ( ! function_exists( 'snapwp_helper_get_env_content' ) ) {
	/**
	 * Generates the SnapWP .env file content based on the site configuration.
	 *
	 * @deprecated @next-version Use \SnapWP\Helper\Modules\EnvGenerator\Generator::generate() directly.
	 * @codeCoverageIgnore
	 *
	 * @return string|\WP_Error The .env file content or an error object.
	 */
	function snapwp_helper_get_env_content() {
		_deprecated_function(
			__FUNCTION__,
			'@next-version',
			sprintf(
				/* translators: %s: function name */
				esc_html__( 'Please use %s directly.', 'snapwp-helper' ),
				Generator::class . '::generate()'
			)
		);

		// Create registry and generator instances.
		try {
			$registry  = new VariableRegistry();
			$generator = new Generator( $registry );

			$content = $generator->generate();

			// Bail if content is empty.
			if ( empty( $content ) ) {
				return new \WP_Error( 'env_generation_failed', esc_html__( 'Unable to generate .env content.', 'snapwp-helper' ) );
			}

			return $content;
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'env_generation_failed', $e->getMessage() );
		}
	}
}

if ( ! function_exists( 'snapwp_helper_get_env_variables' ) ) {
	/**
	 * Get the list of environment variables.
	 *
	 * @deprecated @next-version Use \SnapWP\Helper\Modules\EnvGenerator\VariableRegistry::get_all_values() directly.
	 * @codeCoverageIgnore
	 *
	 * @return array<string,string>|\WP_Error The environment variables and their values.
	 */
	function snapwp_helper_get_env_variables() {
		_deprecated_function(
			__FUNCTION__,
			'@next-version',
			sprintf(
				/* translators: %s: function name */
				esc_html__( 'Please use %s directly.', 'snapwp-helper' ),
				VariableRegistry::class . '::get_all_values()'
			)
		);

		// Create registry and get all values.
		try {
			$registry = new VariableRegistry();
			return $registry->get_all_values();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'env_variables_error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}
}
