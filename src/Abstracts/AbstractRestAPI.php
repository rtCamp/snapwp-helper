<?php
/**
 * Wraps the WP_REST_Controller class to provide a base class for custom REST API endpoints.
 *
 * @package SnapWP\Helper\EnvGenerator
 */

namespace SnapWP\Helper\Abstracts;

use SnapWP\Helper\Interfaces\Registrable;

/**
 * Class initialises the data for the custom endpoint.
 */
abstract class AbstractRestAPI extends \WP_REST_Controller implements Registrable {
	/**
	 * Version.
	 *
	 * @var string
	 */
	protected string $version = '1';

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'snapwp/v';

	/**
	 * To setup action/filter.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * We throw an exception here to force the child class to implement this method.
	 *
	 * @throws \Exception If method not implemented.
	 */
	public function register_routes(): void {
		throw new \Exception( __FUNCTION__ . 'Method not implemented.' );
	}
}
