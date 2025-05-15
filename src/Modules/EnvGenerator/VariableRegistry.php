<?php
/**
 * VariableRegistry class to manage environment variables with descriptions and default values.
 *
 * @package SnapWP\Helper\EnvGenerator
 */

namespace SnapWP\Helper\Modules\EnvGenerator;

use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;

/**
 * VariableRegistry class to manage environment variables with descriptions and default values.
 *
 * @phpstan-type AllowedOutputMode = ('visible'|'commented'|'hidden')
 *
 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation.NonFullyQualifiedClassName
 * @phpstan-type VariableConfig array{
 *   description: string,
 *   default?: string|(callable(self): ?string)|null,
 *   outputMode: (AllowedOutputMode)|callable(self):(AllowedOutputMode),
 *   required: bool|(callable(self):bool),
 *   value?: string|(callable(self): ?string)|null,
 * }
 *
 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation.NonFullyQualifiedClassName
 */
class VariableRegistry {
	/**
	 * Possible output modes for environment variables.
	 */
	public const OUTPUT_VISIBLE   = 'visible';
	public const OUTPUT_COMMENTED = 'commented';
	public const OUTPUT_HIDDEN    = 'hidden';

	/**
	 * Array to store registered environment variables with details.
	 *
	 * @var array<string,VariableConfig>
	 */
	private array $variables;

	/**
	 * Constructor
	 */
	public function __construct() {
		/**
		 * Filters the list of environment variables recognized by SnapWP.
		 *
		 * @param array<string,VariableConfig> $variables The default environment variables, keyed by name.
		 */
		$this->variables = (array) apply_filters( 'snapwp_helper/env/variables', self::get_default_variables() );
	}

	/**
	 * Get the default environment variables.
	 *
	 * @return array<string,VariableConfig> The default environment variables.
	 */
	private static function get_default_variables(): array {
		return [
			'NODE_TLS_REJECT_UNAUTHORIZED' => [
				'description' => 'Enable if connecting to a self-signed cert.',
				'default'     => '0',
				'outputMode'  => self::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => static function ( self $registry ): string {
					$frontend_url = $registry->get_value( 'NEXT_PUBLIC_FRONTEND_URL' );

					// If the frontend is https, enable the flag.
					return strpos( $frontend_url, 'https://' ) === 0 ? '1' : '0';
				},
			],
			'NEXT_PUBLIC_FRONTEND_URL'     => [
				'description' => 'The URL of the Next.js "headless" frontend.',
				'default'     => 'http://localhost:3000',
				'outputMode'  => self::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => 'http://localhost:3000', // @todo Allow this to be stored and reused.
			],
			'NEXT_PUBLIC_WP_HOME_URL'      => [
				'description' => 'The traditional WordPress frontend domain URL. E.g. https://my-headless-site.local',
				'default'     => null,
				'outputMode'  => self::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => static fn (): string => untrailingslashit( get_home_url() ),
			],
			'WP_SITE_URL'                  => [
				'description' => 'The WordPress "backend" Site Address. Uncomment if different than `NEXT_PUBLIC_WP_HOME_URL`. E.g. https://my-headless-site.local/wp/',
				'default'     => null,
				'outputMode'  => static function ( self $registry ): string {
					$home_url = $registry->get_value( 'NEXT_PUBLIC_WP_HOME_URL' );
					$value    = $registry->get_value( 'WP_SITE_URL' );

					// If the value is the same as the home URL, hide it.
					return $value === $home_url ? self::OUTPUT_HIDDEN : self::OUTPUT_VISIBLE;
				},
				'required'    => static fn ( self $registry ): bool => $registry->get_value( 'NEXT_PUBLIC_WP_HOME_URL' ) !== $registry->get_value( 'WP_SITE_URL' ),
				'value'       => static fn (): string => untrailingslashit( get_site_url() ),
			],
			'GRAPHQL_ENDPOINT'             => [
				'description' => 'The WordPress GraphQL endpoint.',
				'default'     => 'index.php?graphql',
				'outputMode'  => self::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => static fn (): ?string => function_exists( 'graphql_get_endpoint' ) ? graphql_get_endpoint() : null,
			],
			'REST_URL_PREFIX'              => [
				'description' => 'The WordPress REST API URL prefix.',
				'default'     => '/wp-json',
				'outputMode'  => static fn ( self $registry ): string => $registry->hide_if_default( 'REST_URL_PREFIX' ),
				'required'    => static fn ( self $registry ): bool => $registry->require_if_not_default( 'REST_URL_PREFIX' ),
				'value'       => static fn (): string => '/' . rest_get_url_prefix(),
			],
			'WP_UPLOADS_DIRECTORY'         => [
				'description' => 'The relative path to the WordPress uploads directory.',
				'default'     => '/wp-content/uploads',
				'outputMode'  => static fn ( self $registry ): string => $registry->hide_if_default( 'WP_UPLOADS_DIRECTORY' ),
				'required'    => static fn ( self $registry ): bool => $registry->require_if_not_default( 'WP_UPLOADS_DIRECTORY' ),
				'value'       => static function (): string {
					$upload_dir = wp_get_upload_dir();
					return '/' . ltrim( str_replace( ABSPATH, '', $upload_dir['basedir'] ), '/' );
				},
			],
			'CORS_PROXY_PREFIX'            => [
				'description' => 'The CORS proxy prefix to use when bypassing CORS restrictions from WordPress server. If unset, no proxy will be used.',
				'default'     => '/proxy',
				'outputMode'  => self::OUTPUT_COMMENTED,
				'required'    => false,
				'value'       => '',
			],
			'INTROSPECTION_TOKEN'          => [
				'description' => 'Token used for authenticating GraphQL introspection queries.',
				'default'     => null,
				'outputMode'  => self::OUTPUT_VISIBLE,
				'required'    => true,
				'value'       => static function () {
					$token = IntrospectionToken::get_token();

					return is_wp_error( $token ) ? null : $token;
				},
			],
		];
	}

	/**
	 * Retrieve all registered environment variables with their details.
	 *
	 * @return array<string,VariableConfig> An associative array with all registered variables and their details.
	 */
	public function get_all_variable_configs(): array {
		return $this->variables;
	}

	/**
	 * Gets the configuration of a registered variable.
	 *
	 * @param string $name The name of the variable to retrieve.
	 *
	 * @return ?VariableConfig The configuration of the variable, or null if not found.
	 */
	public function get_variable_config( string $name ): ?array {
		return $this->get_all_variable_configs()[ $name ] ?? null;
	}

	/**
	 * Gets the default value for a variable, evaluating callbacks if necessary.
	 *
	 * @param string $name The name of the variable.
	 */
	public function get_default_value( string $name ): ?string {
		$config = $this->get_variable_config( $name );
		if ( null === $config || ! isset( $config['default'] ) ) {
			return null;
		}

		$default = $config['default'];

		if ( is_callable( $default ) ) {
			try {
				// Compute the default value.
				$default = $default( $this );

				// Update the config to store the computed value.
				$this->variables[ $name ]['default'] = $default;
			} catch ( \Throwable $e ) {
				return null;
			}
		}

		// All values are output as strings.
		return isset( $default ) ? (string) $default : null;
	}

	/**
	 * Gets the dynamic value for a variable, evaluating callbacks if necessary.
	 * Does not consider provided values, only the registry's configuration.
	 *
	 * @param string $name The name of the variable.
	 */
	public function get_computed_value( string $name ): ?string {
		$config = $this->get_variable_config( $name );
		if ( null === $config ) {
			return null;
		}

			// Check if we already computed and cached this value.
		if ( isset( $config['computed_value'] ) ) {
			return $config['computed_value'];
		}

		// If no value is set, return default.
		if ( ! isset( $config['value'] ) ) {
			return null;
		}

		$value = $config['value'];

		if ( is_callable( $value ) ) {
			try {
				// Compute the value.
				$value = $value( $this );
			} catch ( \Throwable $e ) {
				return null;
			}
		}

		// Update the config to store the computed value.
		if ( null !== $value ) {
			// Store the computed result so we don't have to recalculate.
			$this->variables[ $name ]['computed_value'] = (string) $value;
		}

		// All values are output as strings.
		return isset( $value ) ? (string) $value : null;
	}

	/**
	 * Gets the final value for a variable, considering provided value, computed value, and default.
	 *
	 * @param string $name The name of the variable.
	 */
	public function get_value( string $name ): string {
		// Try the computed value from the system information.
		$computed_value = $this->get_computed_value( $name );

		// Fallback to default if needed.
		return isset( $computed_value ) ? (string) $computed_value : (string) $this->get_default_value( $name );
	}

	/**
	 * Determines if a variable is required.
	 *
	 * @param string $name The name of the variable.
	 */
	public function get_is_required( string $name ): bool {
		$config = $this->get_variable_config( $name );

		// Default to false if not set.
		if ( null === $config ) {
			return false;
		}

		// If no required setting, use legacy behavior.
		if ( ! isset( $config['required'] ) ) {
			return false;
		}

		$required = $config['required'];
		if ( is_callable( $required ) ) {
			try {
				$required = $required( $this );
				// Update the config to store the computed value.
				$this->variables[ $name ]['required'] = $required;
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		return (bool) $required;
	}

	/**
	 * Determines the output mode for a variable.
	 *
	 * @param string $name The name of the variable.
	 *
	 * @return AllowedOutputMode
	 */
	public function get_output_mode( string $name ): string {
		$config = $this->get_variable_config( $name );

		// Default to visible if not set.
		if ( null === $config ) {
			return self::OUTPUT_VISIBLE;
		}

		// Check for the outputMode property according to the schema.
		if ( isset( $config['outputMode'] ) ) {
			$output = $config['outputMode'];
			if ( is_callable( $output ) ) {
				try {
					$output = $output( $this );
					// Update the config to store the computed value.
					$this->variables[ $name ]['outputMode'] = $output;
				} catch ( \Throwable $e ) {
					// Fallback to visible on error.
					return self::OUTPUT_VISIBLE;
				}
			}
			return $output;
		}

		// If no output setting, use default behavior.
		$default  = $this->get_default_value( $name );
		$value    = $this->get_value( $name );
		$required = $this->get_is_required( $name );

		// If the value is the same as the default and not required, comment it.
		return ( ! $required && $value === $default ) ? self::OUTPUT_COMMENTED : self::OUTPUT_VISIBLE;
	}

	/**
	 * Get all variable values.
	 *
	 * @return array<string,string> The resolved values for all variables.
	 */
	public function get_all_values(): array {
		$values = [];

		foreach ( array_keys( $this->get_all_variable_configs() ) as $name ) {
			$values[ $name ] = $this->get_value( $name );
		}

		return $values;
	}

	/**
	 * Check if a variable is using its default value.
	 *
	 * @param string $name The name of the variable.
	 */
	public function is_using_default_value( string $name ): bool {
		$config = $this->get_variable_config( $name );

		// If there's no configuration or no default, it can't be using default.
		if ( null === $config || ! isset( $config['default'] ) ) {
			return false;
		}

		$value   = $this->get_computed_value( $name );
		$default = $this->get_default_value( $name );

		return $value === $default;
	}

	/**
	 * Callback to set the display mode to hidden for variables that are not required and their value matches the default.
	 *
	 * @param string $variable The name of the variable.
	 *
	 * @return 'visible'|'hidden' The output mode.
	 */
	private function hide_if_default( string $variable ): string {
		return $this->is_using_default_value( $variable ) ? self::OUTPUT_HIDDEN : self::OUTPUT_VISIBLE;
	}

	/**
	 * Callback to set the required status if the value is different from the default.
	 *
	 * @param string $variable The name of the variable.
	 */
	private function require_if_not_default( string $variable ): bool {
		return ! $this->is_using_default_value( $variable );
	}
}
