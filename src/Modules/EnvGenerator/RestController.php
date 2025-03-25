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
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Generate the .env content and return it via REST API.
	 *
	 * @param \WP_REST_Request<array{variables:array<string,mixed>[]}> $request The REST request object.
	 *
	 * @return \WP_REST_Response|\WP_Error The response object.
	 */
	public function get_item( $request ) {
		try {
			// Create registry and generator to get .env content.
			$registry  = new VariableRegistry();
			$generator = new Generator( $registry );

			$content = $generator->generate();

			// Check if content was generated.
			if ( empty( $content ) ) {
				return new \WP_Error(
					'env_content_generation_failed',
					__( 'No .env content was generated.', 'snapwp-helper' ),
					[ 'status' => 500 ]
				);
			}

			// Return the generated content in the response.
			$response = new \WP_REST_Response( [ 'content' => $content ], 200 );
			return rest_ensure_response( $response );
		} catch ( \Throwable $e ) {
			return new \WP_Error(
				'env_content_generation_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
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
}
