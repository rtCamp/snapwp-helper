<?php
/**
 * Generator class to create a .env file programmatically.
 *
 * @package SnapWP\Helper\EnvGenerator
 */

namespace SnapWP\Helper\Modules\EnvGenerator;

/**
 * Generator class to create a .env file programmatically.
 */
class Generator {
	/**
	 * The instance of the VariableRegistry class.
	 *
	 * @var \SnapWP\Helper\Modules\EnvGenerator\VariableRegistry
	 */
	private VariableRegistry $registry;

	/**
	 * Constructor
	 *
	 * @param \SnapWP\Helper\Modules\EnvGenerator\VariableRegistry $registry The instance of the VariableRegistry class.
	 */
	public function __construct( VariableRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Generate the content for the .env file.
	 *
	 * @throws \InvalidArgumentException Thrown from prepare_variable method.
	 */
	public function generate(): ?string {
		// Get all registered variable names.
		$variable_names = array_keys( $this->registry->get_all_variable_configs() );

		// Prepare output for all registered variables.
		$output_parts = [];

		foreach ( $variable_names as $name ) {
			$variable_output = $this->prepare_variable( $name );

			if ( ! empty( $variable_output ) ) {
				$output_parts[] = $variable_output;
			}
		}

		return ! empty( $output_parts ) ? implode( "\n\n", $output_parts ) : null;
	}

	/**
	 * Prepare a single environment variable for output.
	 *
	 * @param string $name The name of the variable.
	 *
	 * @throws \InvalidArgumentException If a required variable is missing a value.
	 */
	protected function prepare_variable( string $name ): ?string {
		$variable = $this->registry->get_variable_config( $name );

		// Skip if the variable is not registered. This acts as sanitization.
		if ( null === $variable ) {
			return null;
		}

		$description = isset( $variable['description'] ) && is_string( $variable['description'] ) ? $variable['description'] : '';
		$required    = $this->registry->get_is_required( $name );

		// Get resolved value (provided value, computed value, or default).
		$resolved_value = $this->registry->get_value( $name );

		// Check if a required variable has a value.
		if ( $required && empty( $resolved_value ) && '0' !== $resolved_value ) { // '0' is a valid value.
			throw new \InvalidArgumentException(
				sprintf(
					// translators: %s: The name of the variable.
					esc_html__( 'Required variable %s must have a value.', 'snapwp-helper' ),
					esc_html( $name ),
				)
			);
		}

		// Get the output mode for this variable.
		$output_mode = $this->registry->get_output_mode( $name );

		// Skip if configured to not show this variable.
		if ( VariableRegistry::OUTPUT_HIDDEN === $output_mode ) {
			return null;
		}

		// Prepare the output.
		$comment = ! empty( $description ) ? sprintf( '# %s', $description ) : '';

		// For commented variables with empty values, use the default value instead.
		if ( VariableRegistry::OUTPUT_COMMENTED === $output_mode &&
			( empty( $resolved_value ) && '0' !== $resolved_value ) ) {
			$default_value  = $this->registry->get_default_value( $name );
			$resolved_value = $default_value ?? '';
		}

		$env_output = sprintf( '%s=%s', $name, $resolved_value );

		// Comment out variables based on output mode.
		if ( VariableRegistry::OUTPUT_COMMENTED === $output_mode ) {
			$env_output = '# ' . $env_output;
		}

		// Combine comment and output.
		$result = '';
		if ( ! empty( $comment ) ) {
			$result .= $comment . "\n";
		}
		$result .= $env_output;

		return $result;
	}
}
