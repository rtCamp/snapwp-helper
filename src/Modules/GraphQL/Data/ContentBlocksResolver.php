<?php
/**
 * Used to resolve content blocks from a node.
 *
 * Replaces WPGraphQL\ContentBlocks\Data\ContentBlocksResolver to avoid bugs.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Data
 */

namespace SnapWP\Helper\Modules\GraphQL\Data;

use WPGraphQL\Model\Post;

/**
 * Class ContentBlocksResolver
 */
final class ContentBlocksResolver {
	/**
	 * Retrieves a list of content blocks
	 *
	 * @param \WPGraphQL\Model\Model $node The node we are resolving.
	 * @param array<string,mixed>    $args GraphQL query args to pass to the connection resolver.
	 * @param string[]               $allowed_block_names The list of allowed block names to filter.
	 *
	 * @return array<string,mixed> The list of content blocks.
	 */
	public static function resolve_content_blocks( $node, $args, $allowed_block_names = [] ): array {
		global $post_id;

		$content = null;
		if ( $node instanceof Post ) {

			// @todo: this is restricted intentionally.
			// $content = $node->contentRaw;

			// This is the unrestricted version, but we need to
			// probably have a "Block" Model that handles
			// determining what fields should/should not be
			// allowed to be returned?
			$post    = get_post( $node->databaseId );
			$content = ! empty( $post->post_content ) ? $post->post_content : null;
		}

		/**
		 * Filters the content retrieved from the node used to parse the blocks.
		 *
		 * @param ?string                $content The content to parse.
		 * @param \WPGraphQL\Model\Model $node    The node we are resolving.
		 * @param array                  $args    GraphQL query args to pass to the connection resolver.
		 */
		$content = apply_filters( 'wpgraphql_content_blocks_resolver_content', $content, $node, $args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPGraphQL filter.

		if ( empty( $content ) ) {
			return [];
		}

		// Parse the blocks from HTML comments to an array of blocks.
		$parsed_blocks = self::parse_blocks( $content );
		if ( empty( $parsed_blocks ) ) {
			return [];
		}

		// Flatten block list here if requested or if 'flat' value is not selected (default).
		if ( ! isset( $args['flat'] ) || 'true' == $args['flat'] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			$parsed_blocks = self::flatten_block_list( $parsed_blocks );
		}

		// Final level of filtering out blocks not in the allowed list.
		if ( ! empty( $allowed_block_names ) ) {
			$parsed_blocks = array_filter(
				$parsed_blocks,
				static function ( $parsed_block ) use ( $allowed_block_names ) {
					return in_array( $parsed_block['blockName'], $allowed_block_names, true );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}
		return $parsed_blocks;
	}

	/**
	 * Flattens a list blocks into a single array
	 *
	 * @param array<string,mixed> $blocks A list of blocks to flatten.
	 *
	 * @return array<string,mixed> The flattened list of blocks.
	 */
	private static function flatten_block_list( $blocks ): array {
		$result = [];
		foreach ( $blocks as $block ) {
			$result = array_merge( $result, self::flatten_inner_blocks( $block ) );
		}
		return $result;
	}

	/**
	 * Flattens a block and its inner blocks into a single while attaching unique clientId's
	 *
	 * @param array<string,mixed> $block A block.
	 *
	 * @return array<string,mixed> The flattened block.
	 */
	private static function flatten_inner_blocks( $block ): array {
		$result            = [];
		$block['clientId'] = isset( $block['clientId'] ) ? $block['clientId'] : uniqid();
		array_push( $result, $block );

		foreach ( $block['innerBlocks'] as $child ) {
			$child['parentClientId'] = $block['clientId'];

			$result = array_merge( $result, self::flatten_inner_blocks( $child ) );
		}

		/** @var array<string,mixed> $result */
		return $result;
	}

	/**
	 * Get blocks from html string.
	 *
	 * @param string $content Content to parse.
	 *
	 * @return array<string,mixed> List of blocks.
	 */
	private static function parse_blocks( $content ): array {
		$blocks = parse_blocks( $content );

		return self::handle_do_blocks( $blocks );
	}

	/**
	 * Recursively process blocks.
	 *
	 * @param array<string,mixed>[] $blocks Blocks data.
	 *
	 * @return array<string,mixed>[] The processed blocks.
	 */
	private static function handle_do_blocks( array $blocks ): array {
		$parsed = [];
		foreach ( $blocks as $block ) {
			$block_data = self::handle_do_block( $block );
			if ( $block_data ) {
				$parsed[] = $block_data;
			}
		}

		// Remove empty blocks.
		return array_filter( $parsed );
	}

	/**
	 * Process a block, getting all extra fields.
	 *
	 * @param array<string,mixed> $block Block data.
	 *
	 * @return ?array<string,mixed> The processed block.
	 */
	private static function handle_do_block( array $block ): ?array {
		if ( self::is_block_empty( $block ) ) {
			return null;
		}

		// Set the block name to `core/freeform` if it's empty.
		if ( empty( $block['blockName'] ) ) {
			$block['blockName'] = 'core/freeform';
		}

		// Assign a unique clientId to the block.
		$block['clientId'] = uniqid();

		// Handle core/template-part blocks.
		$block = self::populate_template_part_inner_blocks( $block );

		$block = self::populate_post_content_inner_blocks( $block );

		$block = self::populate_reusable_blocks( $block );

		$block = self::populate_pattern_inner_blocks( $block );

		// Prepare innerBlocks.
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = self::handle_do_blocks( $block['innerBlocks'] );
		}

		return $block;
	}

	/**
	 * Checks whether a block is really empty, and not just a `core/freeform`.
	 *
	 * @param array<string,mixed> $block The block to check.
	 */
	private static function is_block_empty( array $block ): bool {
		// If we have a blockName, no need to check further.
		if ( ! empty( $block['blockName'] ) ) {
			return false;
		}

		if ( ! empty( $block['innerBlocks'] ) || ! empty( trim( $block['innerHTML'] ) ) ) {
			return false;
		}

		// $block['innerContent'] can be an array, we need to check if it's empty, including empty strings.
		if ( ! empty( $block['innerContent'] ) ) {
			$inner_content = implode( '', $block['innerContent'] );
			if ( ! empty( trim( $inner_content ) ) ) {
				return false;
			}
		}

		$stripped = preg_replace( '/<!--(.*)-->/Uis', '', render_block( $block ) );

		return empty( trim( $stripped ?? '' ) );
	}

	/**
	 * Populates the innerBlocks of a template part block with the blocks from the template part.
	 *
	 * @param array<string,mixed> $block The block to populate.
	 *
	 * @return array<string,mixed> The populated block.
	 */
	private static function populate_template_part_inner_blocks( array $block ): array {
		if ( 'core/template-part' !== $block['blockName'] || ! isset( $block['attrs']['slug'] ) ) {
			return $block;
		}

		$matching_templates = get_block_templates( [ 'slug__in' => [ $block['attrs']['slug'] ] ], 'wp_template_part' );

		$template_blocks = ! empty( $matching_templates[0]->content ) ? self::parse_blocks( $matching_templates[0]->content ) : null;

		if ( empty( $template_blocks ) ) {
			return $block;
		}

		$block['innerBlocks'] = $template_blocks;

		return $block;
	}

	/**
	 * Populates reusable blocks with the blocks from the reusable ref ID.
	 *
	 * @param array<string,mixed> $block The block to populate.
	 *
	 * @return array<string,mixed> The populated block.
	 */
	private static function populate_reusable_blocks( array $block ): array {
		if ( 'core/block' !== $block['blockName'] || ! isset( $block['attrs']['ref'] ) ) {
			return $block;
		}

		$reusable_block = get_post( $block['attrs']['ref'] );

		if ( ! $reusable_block ) {
			return $block;
		}

		$parsed_blocks = ! empty( $reusable_block->post_content ) ? self::parse_blocks( $reusable_block->post_content ) : null;

		if ( empty( $parsed_blocks ) ) {
			return $block;
		}

		return array_merge( ...$parsed_blocks );
	}

	/**
	 * Populates the innerBlocks of a core/post-content block with the blocks from the post content.
	 *
	 * @param array<string,mixed> $block The block to populate.
	 *
	 * @return array<string,mixed> The populated block.
	 */
	private static function populate_post_content_inner_blocks( array $block ): array {
		if ( 'core/post-content' !== $block['blockName'] ) {
			return $block;
		}

		$post = get_post();

		if ( ! $post ) {
			return $block;
		}

		$parsed_blocks = ! empty( $post->post_content ) ? self::parse_blocks( $post->post_content ) : null;

		if ( empty( $parsed_blocks ) ) {
			return $block;
		}

		$block['innerBlocks'] = $parsed_blocks;

		return $block;
	}

	/**
	 * Populates the pattern innerBlocks with the blocks from the pattern.
	 *
	 * @param array<string,mixed> $block The block to populate.
	 * @return array<string,mixed> The populated block.
	 */
	private static function populate_pattern_inner_blocks( array $block ): array {
		if ( 'core/pattern' !== $block['blockName'] || ! isset( $block['attrs']['slug'] ) ) {
			return $block;
		}

		$resolved_patterns = resolve_pattern_blocks( [ $block ] );

		if ( empty( $resolved_patterns ) ) {
			return $block;
		}

		$block['innerBlocks'] = $resolved_patterns;

		return $block;
	}
}
