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
 *  dependencies: array<string,mixed>,
 *  extraData: string|null,
 *  version: string|false|null,
 * }
 */
final class ScriptModuleUtils {
	/**
	 * Get the registered script modules and their associated script module data.
	 *
	 * Inspired by https://github.com/johnbillion/query-monitor/blob/6b66f6513580023415fe21e6f0218bd256b3c59a/classes/Collector_Assets.php#L271
	 *
	 * Workaround until WP provides a public API.
	 *
	 * @see https://core.trac.wordpress.org/ticket/60597
	 * @see https://github.com/WordPress/wordpress-develop/blob/86f31c81668c0a680b6db275b41298f7e8513389/src/wp-includes/class-wp-script-modules.php#L380
	 *
	 * @return array<string,ScriptModuleData>
	 */
	public static function get_enqueued_script_modules(): array {
		$modules = wp_script_modules();

		$reflector = new \ReflectionClass( $modules );

		// Get required methods.
		$get_marked_for_enqueue = self::get_accessible_method( $reflector, 'get_marked_for_enqueue' );
		$get_dependencies       = self::get_accessible_method( $reflector, 'get_dependencies' );
		$get_import_map         = self::get_accessible_method( $reflector, 'get_import_map' );

		// Expose required props.
		$a11y_available = $reflector->getProperty( 'a11y_available' );
		$a11y_available->setAccessible( true );

		// Get enqueued modules.
		$enqueued = $get_marked_for_enqueue->invoke( $modules );

		// Restore a11y availability.
		$a11y_available->setValue( $modules, false );

		// Get dependencies for enqueued modules.
		$enqueued_dependencies = $get_dependencies->invoke( $modules, array_keys( $enqueued ) );

		// Merge enqueued modules and their dependencies.
		$all_modules = array_merge( $enqueued, $enqueued_dependencies );

		/**
		 * Check if a11y is available.
		 *
		 * This traditionally runs as part of WP_Script_Modules::print_script_module_data(), so we prime it before we call our
		 */
		foreach ( array_keys( $enqueued ) as $id ) {
			if ( '@wordpress/a11y' === $id ) {
				$a11y_available->setValue( $modules, true );
			}
		}

		foreach ( array_keys( $get_import_map->invoke( $modules ) ) as $id ) {
			if ( '@wordpress/a11y' === $id ) {
				$a11y_available->setValue( $modules, true );
			}
		}

		$sources = [];
		foreach ( $all_modules as $id => $module ) {
			$sources[ $id ] = [
				'id'           => $id,
				'src'          => $module['src'] ?? null,
				'version'      => $module['version'] ?? null,
				'dependencies' => $module['dependencies'] ?? [],
				'extraData'    => self::get_script_module_data( $id ),
			];
		}

		return $sources;
	}

	/**
	 * Gets formatted array of script module data.
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/86f31c81668c0a680b6db275b41298f7e8513389/src/wp-includes/class-wp-script-modules.php#L380
	 *
	 * @param string $module_id The module ID.
	 */
	private static function get_script_module_data( $module_id ): ?string {
		/**
		 * This filter is documented in wp-includes/class-wp-script-modules.php
		 */
		$data = apply_filters( "script_module_data_{$module_id}", [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( ! is_array( $data ) || empty( $data ) ) {
			return null;
		}

		$json_encode_flags = JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS;
		if ( ! is_utf8_charset() ) {
			$json_encode_flags = JSON_HEX_TAG | JSON_UNESCAPED_SLASHES;
		}

		return wp_json_encode( $data, $json_encode_flags ) ?: null;
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
