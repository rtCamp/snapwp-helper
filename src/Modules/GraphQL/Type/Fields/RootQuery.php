<?php
/**
 * Registers custom fields to the GraphQL RootQuery Object.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

use SnapWP\Helper\Modules\GraphQL\Data\TemplateResolver;
use SnapWP\Helper\Modules\GraphQL\Model\RenderedTemplate as ModelRenderedTemplate;
use SnapWP\Helper\Modules\GraphQL\Type\WPObject\GlobalStyles;
use SnapWP\Helper\Modules\GraphQL\Type\WPObject\RenderedTemplate;
use WPGraphQL\AppContext;

/**
 * Class - RootQuery
 */
final class RootQuery extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'RootQuery';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'globalStyles'  => [
				'type'        => GlobalStyles::get_type_name(),
				'description' => __( 'The FSE template.', 'snapwp-helper' ),
				// The fields are resolved in the object itself.
				'resolve'     => static fn () => [],
			],
			'templateByUri' => [
				'type'        => RenderedTemplate::get_type_name(),
				'description' => __( 'Fetches an object given its Unique Resource Identifier', 'snapwp-helper' ),
				'args'        => [
					'uri' => [
						'type'        => [ 'non_null' => 'String' ],
						'description' => __( 'Unique Resource Identifier in the form of a path or permalink for the WordPress frontend. Ex: "/hello-world"', 'snapwp-helper' ),
					],
				],
				'resolve'     => static function ( $_root, array $args, AppContext $context ) {
					$template_resolver = new TemplateResolver( $context );

					$resolved_template = $template_resolver->resolve_uri( $args['uri'] );

					return ! empty( $resolved_template ) ? new ModelRenderedTemplate( $resolved_template ) : null;
				},
			],
		];
	}
}
