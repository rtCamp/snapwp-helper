<?php
/**
 * Filters on the existing WPGraphQL schema.
 *
 * @package SnapWP\Helper\Modules\GraphQL
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL;

use SnapWP\Helper\Interfaces\Registrable;
use SnapWP\Helper\Modules\GraphQL\Data\ContentBlocksResolver;
use SnapWP\Helper\Modules\GraphQL\Server\DisableIntrospectionRule;
use SnapWP\Helper\Modules\GraphQL\Type\WPObject\RenderedTemplate;

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
	 *
	 * There's need to check for dependencies, since missing filters will just be ignored.
	 */
	public function register_hooks(): void {
		// Register custom validation rule for introspection.
		add_filter( 'graphql_validation_rules', [ $this, 'add_custom_validation_rule' ] );

		// Use our own resolver for content blocks.
		add_filter( 'wpgraphql_content_blocks_resolver_content', [ $this, 'get_content_for_resolved_template' ], 10, 2 );
		add_filter( 'graphql_object_fields', [ $this, 'overload_content_blocks_resolver' ], 10, 2 );
		add_filter( 'graphql_interface_fields', [ $this, 'overload_rendered_html' ], 10, 2 );

		// Cache rendered blocks.
		add_filter( 'pre_render_block', [ $this, 'get_cached_rendered_block' ], 10, 2 );
		// We want to cache the rendered block as late as possible to ensure we're caching the final output.
		add_filter( 'render_block', [ $this, 'cache_rendered_block' ], PHP_INT_MAX - 1, 2 );
	}

	/**
	 * Adds a custom validation rule for introspection.
	 *
	 * @param array<int|string,\GraphQL\Validator\Rules\ValidationRule> $rules The existing validation rules.
	 *
	 * @return array<int|string,\GraphQL\Validator\Rules\ValidationRule> The modified validation rules.
	 */
	public function add_custom_validation_rule( array $rules ): array {
		// Replace the default DisableIntrospection rule with our own.
		$rules['disable_introspection'] = new DisableIntrospectionRule();
		return $rules;
	}

	/**
	 * Gets the content from the model for parsing by WPGraphQL ContentBlocks.
	 *
	 * @param string                 $content The content to parse.
	 * @param \WPGraphQL\Model\Model $model   The model to get content from.
	 *
	 * @return string The content to parse.
	 */
	public function get_content_for_resolved_template( $content, $model ) {
		if ( is_array( $model ) && isset( $model['content'] ) ) {
			return $model['content'] ?? '';
		}

		return $content;
	}

	/**
	 * Overloads the content blocks resolver to use ourn own resolver.
	 *
	 * @todo This is necessary because WPGraphQL Content Blocks' resolver is broken.
	 *
	 * @param array<string,mixed> $fields The config for the interface type.
	 * @param string              $typename The name of the interface type.
	 *
	 * @return array<string,mixed>
	 */
	public function overload_content_blocks_resolver( array $fields, $typename ): array {
		if ( ! isset( $fields['editorBlocks'] ) ) {
			return $fields;
		}

		// RenderedTemplate is a special case, as it already has the blocks resolved.
		if ( RenderedTemplate::get_type_name() === $typename ) {
			$fields['editorBlocks']['resolve'] = static function ( $node, $args ) {
				if ( $args['flat'] ) {
					return ContentBlocksResolver::flatten_block_list( $node->parsed_blocks );
				}
				return $node->parsed_blocks;
			};

			return $fields;
		}

		// Use our own resolver for the rest of the types.
		$fields['editorBlocks']['resolve'] = static function ( $node, $args ) {
			return ContentBlocksResolver::resolve_content_blocks( $node, $args );
		};

		return $fields;
	}

	/**
	 * Overloads the EditorBlock renderedHtml to avoid using render_block() multiple times.
	 *
	 * @param array<string,mixed> $fields The config for the interface type.
	 * @param string              $typename The name of the interface type.
	 *
	 * @return array<string,mixed>
	 */
	public function overload_rendered_html( array $fields, $typename ): array {
		if ( 'EditorBlock' === $typename && isset( $fields['renderedHtml'] ) ) {
			$fields['renderedHtml']['resolve'] = static function ( $block ) {
				return $block['renderedHtml'] ?? null;
			};
		}

		return $fields;
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
		if ( ! class_exists( 'WPGraphQL' ) || ! \WPGraphQL::is_graphql_request() ) {
			return $block_content;
		}

		// Bail if block content is already set.
		if ( null !== $block_content || empty( $parsed_block ) ) {
			return $block_content;
		}

		$cache_key = $this->get_cache_key( $parsed_block );

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
		if ( ! class_exists( 'WPGraphQL' ) || ! \WPGraphQL::is_graphql_request() ) {
			return $block_content;
		}

		$cache_key = $this->get_cache_key( $parsed_block );

		// Bail if we couldn't get a cache key.
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
		if ( empty( $parsed_block['clientId'] ) ) {
			return null;
		}

		return self::BLOCK_CACHE_KEY_PREFIX . $parsed_block['clientId'];
	}
}
