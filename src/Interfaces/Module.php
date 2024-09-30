<?php
/**
 * Interface for Module classes.
 *
 * Modules are entry-point classes that are responsible for registering hooks and loading other classes.
 *
 * Every module inside `src/Modules` should implement this interface.
 *
 * @package SnapWP\Helper
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Interfaces;

/**
 * Interface - Module
 */
interface Module extends Registrable {
	/**
	 * The name of the module.
	 */
	public function name(): string;

	/**
	 * Initializes the module.
	 */
	public function init(): void;
}
