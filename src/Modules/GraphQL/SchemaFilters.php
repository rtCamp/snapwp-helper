<?php
/**
 * Filters on the existing WPGraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL;

use SnapWP\Helper\Interfaces\Registrable;
use SnapWP\Helper\Modules\GraphQL\Model\RenderedTemplate;
use WPGraphQL;

/**
 * Class - SchemaFilters
 */
final class SchemaFilters implements Registrable {
	/**
	 * The cache key prefix for rendered blocks.
	 *
	 * @var string
	 */
	protected const BLOCK_CACHE_KEY_PREFIX = 'rendered_block-';

	/**
	 * The cache group for rendered blocks.
	 *
	 * @var string
	 */
	protected const BLOCK_CACHE_GROUP = 'snapwp-helper';

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		// No need to check for dependencies, since missing filters will just be ignored.
		add_filter( 'wpgraphql_content_blocks_resolver_content', [ $this, 'get_content_from_model' ], 10, 2 );

		// Cache rendered blocks.
		add_filter( 'pre_render_block', [ $this, 'get_cached_rendered_block' ], 10, 2 ); // @todo: this should be as early priority as possible
		// We want to cache the rendered block as late as possible to ensure we're caching the final output.
		add_filter( 'render_block', [ $this, 'cache_rendered_block' ], PHP_INT_MAX - 1, 2 );
	}

	/**
	 * Gets the content from the model for parsing by WPGraphQL ContentBlocks.
	 *
	 * @param string                 $content The content to parse.
	 * @param \WPGraphQL\Model\Model $model   The model to get content from.
	 */
	public function get_content_from_model( $content, $model ): string {
		if ( $model instanceof RenderedTemplate ) {
			$content = $model->content ?? '';
		}

		return $content;
	}

	/**
	 * Get the cached rendered block.
	 *
	 * Blocks are cached to stabilize the rendering of inline classes, no matter how many times the block is rendered.
	 * This is necessary because WPGraphQL Content Blocks calls render_block multiple times for the same block, causing the unique ids appended to the injected classes to change.
	 *
	 * @param ?string             $block_content The prerendered block content. This will be null if the block has not been rendered yet.
	 * @param array<string,mixed> $parsed_block  The block array.
	 *
	 * @return ?string The cached rendered block.
	 */
	public function get_cached_rendered_block( $block_content, $parsed_block ) {
		// Bail if not a GraphQL request.
		if ( ! WPGraphQL::is_graphql_request() ) {
			return $block_content;
		}

		// Bail if block content is already set.
		if ( null !== $block_content || empty( $parsed_block ) ) {
			return $block_content;
		}

		$cache_key = $this->get_cache_key( $parsed_block );

		// Bail if we couldn't generate a cache key. This means the parsed_block is not serializable.
		if ( null === $cache_key ) {
			return $block_content;
		}

		$rendered_block = wp_cache_get( $cache_key, self::BLOCK_CACHE_GROUP );

		// If we've cached the block, return it.
		return false !== $rendered_block ? $rendered_block : $block_content;
	}

	/**
	 * Cache the rendered block.
	 *
	 * This filter is called as late as possible to ensure we're caching the final output.
	 *
	 * @param string              $block_content The rendered block content.
	 * @param array<string,mixed> $parsed_block  The block array.
	 *
	 * @return string The rendered block content.
	 */
	public function cache_rendered_block( $block_content, $parsed_block ) {
		// Bail if not a GraphQL request.
		if ( ! WPGraphQL::is_graphql_request() ) {
			return $block_content;
		}

		$cache_key = $this->get_cache_key( $parsed_block );

		// Bail if we couldn't generate a cache key. This means the parsed_block is not serializable.
		if ( null === $cache_key ) {
			return $block_content;
		}

		wp_cache_set( $cache_key, $block_content, self::BLOCK_CACHE_GROUP );

		return $block_content;
	}

	/**
	 * Gets the cache key for a block.
	 *
	 * @param array<string,mixed> $parsed_block The block array.
	 */
	protected function get_cache_key( array $parsed_block ): ?string {
		// WPGraphQL Content Blocks injects a clientId into the block array, so we want to exclude that from the cache key.
		if ( isset( $parsed_block['clientId'] ) ) {
			unset( $parsed_block['clientId'] );
		}

		$encoded_block = wp_json_encode( $parsed_block );

		if ( false === $encoded_block ) {
			return null;
		}

		return self::BLOCK_CACHE_KEY_PREFIX . md5( $encoded_block );
	}
}
