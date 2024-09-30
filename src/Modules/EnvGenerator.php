<?php
/**
 * EnvGenerator class file for the EnvGenerator module.
 *
 * @package SnapWP\Helper\Modules
 */

namespace SnapWP\Helper\Modules;

use SnapWP\Helper\Interfaces\Module;
use SnapWP\Helper\Modules\EnvGenerator\RestController;

/**
 * EnvGenerator class.
 */
class EnvGenerator implements Module {
	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return 'env-generator';
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		/**
		 * Classes to register.
		 *
		 * @var array<class-string<\SnapWP\Helper\Interfaces\Registrable>>
		 */
		$classes_to_register = [
			RestController::class,
		];

		foreach ( $classes_to_register as $class ) {
			$class_instance = new $class();
			$class_instance->register_hooks();
		}
	}
}
