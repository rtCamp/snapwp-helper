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
	 * Array to store the generated environment variables.
	 *
	 * @var array<string,string>
	 */
	private $values = [];

	/**
	 * The instance of the VariableRegistry class.
	 *
	 * @var \SnapWP\Helper\Modules\EnvGenerator\VariableRegistry
	 */
	private VariableRegistry $registry;

	/**
	 * Constructor
	 *
	 * @param array<string,string>                                 $values The values for the environment variables.
	 * @param \SnapWP\Helper\Modules\EnvGenerator\VariableRegistry $registry The instance of the VariableRegistry class.
	 */
	public function __construct( array $values, VariableRegistry $registry ) {
		$this->registry = $registry;
		$this->values   = $values;
	}

	/**
	 * Generate the content for the .env file.
	 *
	 * @throws \InvalidArgumentException Thrown from prepare_variable method.
	 */
	public function generate(): ?string {
		return $this->prepare_variables( $this->values );
	}

	/**
	 * Add environment variables to the generator based on the provided args.
	 *
	 * @param array<string,string> $variables Associative array of environment variables to add.
	 *
	 * @throws \InvalidArgumentException If a required variable is missing a value.
	 */
	protected function prepare_variables( array $variables ): ?string {
		// Prime the output string.
		$output = '';

		foreach ( $variables as $name => $value ) {
			$variable_output = $this->prepare_variable( $name, $value );

			if ( null !== $variable_output ) {
				$output .= $variable_output;
			}
		}

		return $output ?: null;
	}

	/**
	 * Prepare a single environment variable for output.
	 *
	 * @param string  $name  The name of the variable.
	 * @param ?string $value The value of the variable.
	 *
	 * @throws \InvalidArgumentException If a required variable is missing a value.
	 */
	protected function prepare_variable( string $name, ?string $value ): ?string {
		$variable = $this->registry->get_variable_config( $name );

		// Skip if the variable is not registered. This acts as sanitization.
		if ( null === $variable ) {
			return null;
		}

		$description = isset( $variable['description'] ) && is_string( $variable['description'] ) ? $variable['description'] : '';
		$default     = isset( $variable['default'] ) && is_string( $variable['default'] ) ? $variable['default'] : '';
		$required    = ! empty( $variable['required'] );

		// Check if a required variable has a value.
		if ( $required && empty( $value ) ) {
			throw new \InvalidArgumentException( 'Required variables must have a value.' );
		}

		// Determine the final value to output.
		$resolved_value = ! empty( $value ) ? $value : $default;
		if ( empty( $resolved_value ) ) {
			$resolved_value = null;
		}

		$comment = ! empty( $description ) ? sprintf( "\n# %s\n", $description ) : '';
		$output  = null !== $resolved_value ? sprintf( '%s=%s\n', $name, $resolved_value ) : sprintf( '# %s=\'0\'\n', $name );

		return $comment . $output;
	}
}
