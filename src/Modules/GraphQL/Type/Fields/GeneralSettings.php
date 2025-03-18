<?php
/**
 * Registers custom fields to the GraphQL GeneralSettings Object.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Type\Fields
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Type\Fields;

use WPGraphQL\AppContext;

/**
 * Class - GeneralSettings
 */
final class GeneralSettings extends AbstractFields {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'GeneralSettings';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_fields(): array {
		return [
			'siteIcon' => [
				'type'        => 'MediaItem',
				'description' => __( 'Site Icon', 'snapwp-helper' ),
				'resolve'     => static function ( $_source, $_args, AppContext $context ) {
					$site_icon_id = (int) get_option( 'site_icon' );

					if ( empty( $site_icon_id ) ) {
						return null;
					}

					return $context->get_loader( 'post' )->load_deferred( $site_icon_id );
				},
			],
		];
	}
}
