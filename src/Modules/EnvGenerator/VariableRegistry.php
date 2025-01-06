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
	 * @var array<string,array{description:string,default:string,required:bool}>
	 */
	private const VARIABLES = [
		'NODE_TLS_REJECT_UNAUTHORIZED' => [
			'description' => 'Enable if connecting to a self-signed cert',
			'default'     => '',
			'required'    => false,
		],
		'NEXT_URL'                     => [
			'description' => 'The headless frontend domain URL',
			'default'     => '',
			'required'    => false,
		],
		'HOME_URL'                     => [
			'description' => 'The WordPress "frontend" domain URL',
			'default'     => '',
			'required'    => true,
		],
		'GRAPHQL_ENDPOINT'             => [
			'description' => 'The WordPress GraphQL endpoint',
			'default'     => 'graphql',
			'required'    => false,
		],
		'INTROSPECTION_TOKEN'          => [
			'description' => 'Token used for authenticating GraphQL introspection queries',
			'default'     => '',
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
		// @todo make filterable and allow for conditionals.
		$this->variables = self::VARIABLES;
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
