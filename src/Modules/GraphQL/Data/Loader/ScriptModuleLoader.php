<?php
/**
 * Dataloader for enqueued Script Modules.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Data\Loader
 */

namespace SnapWP\Helper\Modules\GraphQL\Data\Loader;

use SnapWP\Helper\Modules\GraphQL\Model\ScriptModule;
use SnapWP\Helper\Modules\GraphQL\Utils\ScriptModuleUtils;
use WPGraphQL\Data\Loader\AbstractDataLoader;

/**
 * Class - ScriptModuleLoader
 */
class ScriptModuleLoader extends AbstractDataLoader {
	/**
	 * {@inheritDoc}
	 *
	 * @param array<mixed> $entry The entry to load.
	 *
	 * @return ?\SnapWP\Helper\Modules\GraphQL\Model\ScriptModule
	 */
	protected function get_model( $entry, $key ) {
		if ( ! is_array( $entry ) ) {
			return null;
		}

		// The Model will throw an exception if the required keys are not present.
		try {
			return new ScriptModule( $entry );
		} catch ( \InvalidArgumentException $e ) {
			graphql_debug( $e->getMessage() );
			return null;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string[] $keys Array of script handles to load
	 *
	 * @return array<string,mixed>
	 */
	public function loadKeys( array $keys ) {
		if ( empty( $keys ) ) {
			return [];
		}

		// There's no query to make, so we'll just return the enqueued script modules.
		$script_modules = ScriptModuleUtils::get_enqueued_script_modules();

		$loaded = [];
		foreach ( $keys as $key ) {
			if ( isset( $script_modules[ $key ] ) ) {
				$loaded[ $key ] = $script_modules[ $key ];
			} else {
				$loaded[ $key ] = null;
			}
		}
		return $loaded;
	}
}
