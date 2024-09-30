<?php
/**
 * Singleton trait.
 *
 * @package SnapWP\Helper\Traits
 */

namespace SnapWP\Helper\Traits;

/**
 * Singleton trait.
 */
trait Singleton {
	/**
	 * Instance of the class.
	 *
	 * @var ?static
	 */
	protected static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @return static
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Prevent the class from being instantiated directly.
	 */
	protected function __construct() {
		// To be implemented by the class using the trait.
	}

	/**
	 * Prevent the class from being cloned.
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				// translators: %s: Class name.
				esc_html__( 'The %s class should not be cloned.', 'snapwp-helper' ),
				esc_html( static::class ),
			),
			'0.0.1'
		);
	}

	/**
	 * Prevent the class from being deserialized.
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				// translators: %s: Class name.
				esc_html__( 'De-serializing instances of %s is not allowed.', 'snapwp-helper' ),
				esc_html( static::class ),
			),
			'0.0.1'
		);
	}
}
