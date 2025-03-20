<?php
/**
 * VariableRegistry class to manage environment variables with descriptions and default values.
 *
 * @package SnapWP\Helper\EnvGenerator
 */

namespace SnapWP\Helper\Modules\EnvGenerator;

/**
 * VariableRegistry class to manage environment variables with descriptions and default values.
 */
class VariableRegistry {
	/**
	 * Array of environment variables.
	 *
	 * @todo Support conditional variables.
	 *
	 * @var array<string,array{description:string,default:string,required:bool}>
	 */
	private const VARIABLES = [
		'NODE_TLS_REJECT_UNAUTHORIZED'     => [
			'description' => 'Only enable if connecting to a self-signed cert',
			'default'     => '0',
			'required'    => true,
		],
		'NEXT_PUBLIC_FRONTEND_URL'         => [
			'description' => 'The headless frontend domain URL. Make sure the value matches the URL used by your frontend app.',
			'default'     => 'http://localhost:3000',
			'required'    => true,
		],
		'NEXT_PUBLIC_WP_HOME_URL'          => [
			'description' => 'The WordPress "frontend" domain URL e.g. https://my-headless-site.local',
			'default'     => '',
			'required'    => true,
		],
		'NEXT_PUBLIC_WP_SITE_URL'          => [
			'description' => 'The WordPress "backend" Site Address. Uncomment if different than `NEXT_PUBLIC_WP_HOME_URL` e.g. https://my-headless-site.local/wp/',
			'default'     => '',
			'required'    => false,
		],
		'NEXT_PUBLIC_GRAPHQL_ENDPOINT'     => [
			'description' => 'The WordPress GraphQL endpoint',
			'default'     => 'index.php?graphql',
			'required'    => true,
		],
		'NEXT_PUBLIC_WP_UPLOADS_DIRECTORY' => [
			'description' => 'The WordPress Uploads directory path',
			'default'     => '/wp-content/uploads',
			'required'    => false,
		],
		'NEXT_PUBLIC_REST_URL_PREFIX'      => [
			'description' => 'The WordPress REST URL Prefix',
			'default'     => '/wp-json',
			'required'    => false,
		],
		'INTROSPECTION_TOKEN'              => [
			'description' => 'Token used for authenticating GraphQL introspection queries',
			'default'     => '',
			'required'    => true,
		],
		'NEXT_PUBLIC_CORS_PROXY_PREFIX'    => [
			'description' => 'The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server, Possible values: string|false Default: /proxy, This means for script module next app will make request NEXT_PUBLIC_FRONTEND_URL/proxy/{module-path}',
			'default'     => '/proxy',
			'required'    => false,
		],
	];

	/**
	 * Array to store registered environment variables with details.
	 *
	 * @var array<string,array{description:string,default:string,required:bool}>
	 */
	private array $variables;

	/**
	 * Constructor
	 */
	public function __construct() {
		/**
		 * Filters the list of environment variables recognized by SnapWP.
		 *
		 * @param array<string,array{description:string,default:string,required:bool}> $variables The default environment variables, keyed by name.
		 */
		$this->variables = (array) apply_filters( 'snapwp_helper/env/variables', self::VARIABLES );
	}

	/**
	 * Gets the value of a registered variable.
	 *
	 * @param string $name The name of the variable to retrieve.
	 *
	 * @return ?array{description:string,default:string,required:bool} The details of the variable or null if not found.
	 */
	public function get_variable_config( string $name ) {
		return $this->variables[ $name ] ?? null;
	}

	/**
	 * Retrieve all registered environment variables with their details.
	 *
	 * @return array<string,array<string,mixed>> An associative array with all registered variables and their details.
	 */
	public function get_all_variable_configs(): array {
		return $this->variables;
	}
}
