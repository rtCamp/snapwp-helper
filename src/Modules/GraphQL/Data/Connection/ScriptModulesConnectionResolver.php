<?php
/**
 * Connection resolver for enqueued Script Modules
 *
 * @package SnapWP\Helper\Modules\GraphQL\Data\Connection
 */

namespace SnapWP\Helper\Modules\GraphQL\Data\Connection;

use WPGraphQL\Data\Connection\AbstractConnectionResolver;

/**
 * Class ScriptModulesConnectionResolver
 *
 * @extends \WPGraphQL\Data\Connection\AbstractConnectionResolver<string[]>
 */
class ScriptModulesConnectionResolver extends AbstractConnectionResolver {
	/**
	 * {@inheritDoc}
	 */
	public function get_ids_from_query() {
		$ids     = [];
		$queried = $this->get_query();

		if ( empty( $queried ) ) {
			return $ids;
		}

		foreach ( $queried as $key => $item ) {
			$ids[ $key ] = $item;
		}

		return $ids;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function prepare_query_args( array $args ): array {
		// If any args are added to filter/sort the connection.
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function query( array $query_args ) {
		return ! empty( $this->source->enqueuedScriptModulesQueue ) ? $this->source->enqueuedScriptModulesQueue : [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function loader_name(): string {
		return 'script_module';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Since we load _all_ script modules regardless of the query, there's no performance benefit to limiting pagination.
	 */
	protected function max_query_amount(): int {
		return 1000;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WPGraphQL\Model\Model $model The model to validate.
	 */
	protected function is_valid_model( $model ) {
		return isset( $model->handle );
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_valid_offset( $offset ) {
		// @todo - this is too expensive to implement as long as reflection used in ScriptModuleUtils.
		return true;
	}
}
