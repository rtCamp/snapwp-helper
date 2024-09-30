<?php
/**
 * Custom REST API class for EnvGenerator.
 *
 * @package SnapWP\Helper\EnvGenerator
 */

namespace SnapWP\Helper\Modules\EnvGenerator;

use SnapWP\Helper\Abstracts\AbstractRestAPI;

/**
 * Class - RestController
 */
class RestController extends AbstractRestAPI {
	/**
	 * {@inheritDoc}
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace . $this->version,
			'/env',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'args'                => $this->get_args(),
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * This function is responsible for generating the REST controller for the EnvGenerator module.
	 *
	 * @param \WP_REST_Request<array{variables:array<string,mixed>[]}> $request The REST request object.
	 *
	 * @return \WP_REST_Response|\WP_Error The response object.
	 */
	public function get_item( $request ) {
		$args = $request->get_params();

		if ( empty( $args['variables'] ) ) {
			return new \WP_Error( 'missing_variables', 'No variables provided.', [ 'status' => 400 ] );
		}

		/**
		 * Validate and generate the .env content.
		 *
		 * @var array<key-of<\SnapWP\Helper\Modules\EnvGenerator\VariableRegistry::VARIABLES>,string> $variables
		 */
		$variables = $this->prepare_variables( $args['variables'] );

		$content = $this->generate_env_content( $variables );

		if ( $content instanceof \WP_Error ) {
			return $content;
		}

		// Return the generated content in the response.
		$response = new \WP_REST_Response( [ 'content' => $content ], 200 );

		return rest_ensure_response( $response );
	}

	/**
	 * Define the schema for the expected arguments.
	 *
	 * @return array<string,mixed>[] Array of argument schema definitions.
	 */
	public function get_args() {
		return [
			'variables' => [
				'description'       => __( 'The variables to be included in the .env file.', 'snapwp-helper' ),
				'type'              => 'array',
				'items'             => [
					'type'       => 'object',
					'properties' => [
						'variable' => [
							'type'        => 'string',
							'description' => __( 'The ENV variable.', 'snapwp-helper' ),
						],
						'value'    => [
							'type'        => 'string',
							'description' => __( 'The value of the ENV variable.', 'snapwp-helper' ),
						],
					],
				],
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'rest_sanitize_request_arg',
			],
		];
	}

	/**
	 * Permissions check for the REST API.
	 *
	 * @param \WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool True if the user has permissions, false otherwise.
	 */
	public function permissions_check( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Generate the .env content based on the passed arguments.
	 *
	 * @param array<key-of<\SnapWP\Helper\Modules\EnvGenerator\VariableRegistry::VARIABLES>,string> $variables The variables to generate the .env content.
	 *
	 * @return string|\WP_Error The generated .env content or WP_Error if the generation fails.
	 */
	private function generate_env_content( array $variables ) {
		$content = snapwp_helper_generate_env_content( $variables );

		if ( is_wp_error( $content ) ) {
			return new \WP_Error( 'env_generation_failed', $content->get_error_message(), [ 'status' => 500 ] );
		}

		return $content;
	}

	/**
	 * Prepare the variables passed in the request.
	 *
	 * @param array<string,mixed>[] $variables The variables to be included in the .env file.
	 *
	 * @return array<string,string> The prepared variables.
	 */
	private function prepare_variables( array $variables ): array {
		$prepared = [];

		foreach ( $variables as $variable ) {
			$prepared[ $variable['name'] ] = $variable['value'];
		}

		return $prepared;
	}
}
