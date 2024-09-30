<?php
/**
 * This file handles the registration and management of settings for the plugin.
 *
 * @package SnapWP\Helper\Modules\Admin
 */

declare(strict_types=1);

namespace SnapWP\Helper\Modules\Admin;

use SnapWP\Helper\Interfaces\Registrable;

/**
 * Class Settings
 */
class Settings implements Registrable {
	/**
	 * Prefix for all settings.
	 */
	public const PREFIX = 'snapwp_helper_';

	/**
	 * Constant for the Frontend URL setting key.
	 */
	public const FRONTEND_URL_KEY = self::PREFIX . 'config';

	/**
	 * Constant for the option group.
	 */
	public const OPTION_GROUP = self::PREFIX . 'settings';

	/**
	 * Register WordPress hooks related to the settings.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings with WordPress.
	 */
	public function register_settings(): void {
		foreach ( self::get_settings() as $option_name => $args ) {
			register_setting( self::OPTION_GROUP, $option_name, $args );
		}
	}

	/**
	 * Get all registered settings.
	 *
	 * @return array<string,array<string,mixed>> Array of all registered settings.
	 */
	public static function get_settings(): array {
		return [
			self::FRONTEND_URL_KEY => [
				'type'              => 'string',
				'description'       => __( 'The URL used for the headless frontend', 'snapwp-helper' ),
				'default'           => false,
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => [
					'schema' => [
						'type'        => 'string',
						'description' => __( 'The URL used for the headless frontend', 'snapwp-helper' ),
						'default'     => false,
						'format'      => 'uri',
					],
				],
			],
		];
	}

	/**
	 * Setters & Getters
	 */

	/**
	 * Get the Frontend URL.
	 */
	public static function get_frontend_url(): ?string {
		$url = get_option( self::FRONTEND_URL_KEY );

		if ( ! $url || ! is_string( $url ) ) {
			return null;
		}

		return esc_url_raw( $url ) ?: null;
	}

	/**
	 * Set the Frontend URL.
	 *
	 * @param string $url The URL to set.
	 */
	public static function set_frontend_url( string $url ): bool {
		$url = esc_url_raw( $url );

		return update_option( self::FRONTEND_URL_KEY, $url );
	}
}
