<?php
/**
 * Methods for interacting with WordPress Script Modules.
 *
 * Workaround until WP provides a public API.
 *
 * @see https://core.trac.wordpress.org/ticket/60597
 *
 * @package SnapWP\Helper\Modules\GraphQL\Utils
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Utils;

/**
 * Class - ScriptModuleUtils
 *
 * @phpstan-type ScriptModuleData array{
 *  id: string,
 *  src: string,
 *  version: string|false|null,
 *  dependencies: array<string,mixed>
 * }
 */
final class ScriptModuleUtils {
	/**
	 * Get the registered script modules.
	 *
	 * Inspired by https://github.com/johnbillion/query-monitor/blob/6b66f6513580023415fe21e6f0218bd256b3c59a/classes/Collector_Assets.php#L271
	 *
	 * Workaround until WP provides a public API.
	 *
	 * @see https://core.trac.wordpress.org/ticket/60597
	 *
	 * @return array<string,ScriptModuleData>|null
	 */
	public static function get_enqueued_script_modules(): ?array {
		// Check for WP 6.5+ compatibility.
		if ( ! function_exists( 'wp_script_modules' ) ) {
			return null;
		}

		$modules = wp_script_modules();

		$reflector = new \ReflectionClass( $modules );

		// Get required methods.
		$get_marked_for_enqueue = self::get_accessible_method( $reflector, 'get_marked_for_enqueue' );
		$get_dependencies       = self::get_accessible_method( $reflector, 'get_dependencies' );

		// Get enqueued modules.
		$enqueued = $get_marked_for_enqueue->invoke( $modules );

		// Get dependencies for enqueued modules.
		$enqueued_dependencies = $get_dependencies->invoke( $modules, array_keys( $enqueued ) );

		// Merge enqueued modules and their dependencies.
		$all_modules = array_merge( $enqueued, $enqueued_dependencies );

		$sources = [];
		foreach ( $all_modules as $id => $module ) {
			$sources[ $id ] = [
				'id'           => $id,
				'src'          => $module['src'] ?? null,
				'version'      => $module['version'] ?? null,
				'dependencies' => $module['dependencies'] ?? [],
			];
		}

		return $sources;
	}

	/**
	 * Get an accessible method from a reflector.
	 *
	 * @param \ReflectionClass<\WP_Script_Modules> $reflector The reflector.
	 * @param string                               $methodName The method name.
	 *
	 * @throws \ReflectionException
	 */
	private static function get_accessible_method( \ReflectionClass $reflector, string $methodName ): \ReflectionMethod {
		$method = $reflector->getMethod( $methodName );
		$method->setAccessible( true );
		return $method;
	}
}
