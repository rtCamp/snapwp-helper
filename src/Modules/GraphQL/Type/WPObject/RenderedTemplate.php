<?php
/**
 * Registers the RenderedTemplate Object to WPGraphQL.
 *
 * Temporary until supported by WPGraphQL / REST API.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\WPObject
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\WPObject;

use SnapWP\Helper\Modules\GraphQL\Data\Connection\ScriptModulesConnectionResolver;
use SnapWP\Helper\Modules\GraphQL\Interfaces\TypeWithConnections;
use SnapWP\Helper\Modules\GraphQL\Interfaces\TypeWithInterfaces;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\EnqueuedScriptsConnectionResolver;
use WPGraphQL\Data\Connection\EnqueuedStylesheetConnectionResolver;

/**
 * Class - RenderedTemplate
 */
final class RenderedTemplate extends AbstractObject implements TypeWithConnections, TypeWithInterfaces {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'RenderedTemplate';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'The rendered WordPress template for a specific URI.', 'snapwp-helper' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'content'       => [
				'type'        => 'String',
				'description' => __( 'The content for the template. This is the serialized block markup and HTML.', 'snapwp-helper' ),
			],
			'renderedHtml'  => [
				'type'        => 'String',
				'description' => __( 'The rendered HTML for the template.', 'snapwp-helper' ),
			],
			'bodyClasses'   => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The css classes for the HTML `<body>` tag.', 'snapwp-helper' ),
			],
			'connectedNode' => [
				'type'        => 'UniformResourceIdentifiable',
				'description' => __( 'The `nodeByUri` object for the given URI.', 'snapwp-helper' ),
				'resolve'     => static function ( $source, array $args, AppContext $context ) {
					$queried = get_queried_object();

					// Handle WP_Post object.
					if ( $queried instanceof \WP_Post ) {
						return $context->get_loader( 'post' )->load_deferred( $queried->ID );
					} elseif ( $queried instanceof \WP_Term ) {
						return $context->get_loader( 'term' )->load_deferred( $queried->term_id );
					} elseif ( $queried instanceof \WP_User ) {
						return $context->get_loader( 'user' )->load_deferred( $queried->ID );
					} elseif ( $queried instanceof \WP_Comment ) {
						return $context->get_loader( 'comment' )->load_deferred( $queried->comment_ID );
					} elseif ( $queried instanceof \WP_Taxonomy ) {
						return $context->get_loader( 'taxonomy' )->load_deferred( $queried->name );
					} elseif ( $queried instanceof \WP_Post_Type ) {
						return $context->get_loader( 'post_type' )->load_deferred( $queried->name );
					}

					// Fall back to the the NodeResolver.
					return ! empty( $source->uri ) ? $context->node_resolver->resolve_uri( $source->uri ) : null;
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_interfaces(): array {
		return [
			'UniformResourceIdentifiable',
			'NodeWithEditorBlocks',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_connections(): array {
		return [
			'enqueuedScripts'       => [
				'toType'      => 'EnqueuedScript',
				'description' => __( 'The scripts enqueued for the template.', 'snapwp-helper' ),
				'resolve'     => static function ( $source, $args, $context, $info ) {
					$resolver = new EnqueuedScriptsConnectionResolver( $source, $args, $context, $info );

					return $resolver->get_connection();
				},
			],
			'enqueuedScriptModules' => [
				'toType'      => ScriptModule::get_type_name(),
				'description' => __( 'The script modules enqueued for the template.', 'snapwp-helper' ),
				'resolve'     => static function ( $source, $args, $context, $info ) {
					$resolver = new ScriptModulesConnectionResolver( $source, $args, $context, $info );

					return $resolver->get_connection();
				},
			],
			'enqueuedStylesheets'   => [
				'toType'      => 'EnqueuedStylesheet',
				'description' => __( 'The stylesheets enqueued for the template.', 'snapwp-helper' ),
				'resolve'     => static function ( $source, $args, $context, $info ) {
					$resolver = new EnqueuedStylesheetConnectionResolver( $source, $args, $context, $info );
					return $resolver->get_connection();
				},
			],

		];
	}
}
