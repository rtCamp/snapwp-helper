<?php
/**
 * Registers wp-admin functionality.
 *
 * @package SnapWP\Helper\Modules
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules;

use SnapWP\Helper\Interfaces\Module;
use SnapWP\Helper\Modules\Admin\Settings;

/**
 * Class - Admin
 */
class Admin implements Module {
	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return 'admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		$classes_to_register = [
			Settings::class,
		];

		foreach ( $classes_to_register as $class ) {
			$class_instance = new $class();
			$class_instance->register_hooks();
		}
		$this->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		/**
		 * @todo The priority is set to 101 to avoid a bug with WPGraphQL IDE
		 * @see https://github.com/wp-graphql/wpgraphql-ide/issues/214
		 */
		add_action( 'admin_menu', [ $this, 'register_menu' ], 101 );
	}

	/**
	 * Register the admin menu.
	 */
	public function register_menu(): void {
		// If WPGraphQL's menu exists, add the plugin menu as a submenu, and to `Tools` otherwise.
		$wpgraphql_menu_slug = 'graphiql-ide';
		$wpgraphql_menu_url  = menu_page_url( $wpgraphql_menu_slug, false );

		$parent_menu_slug = ! empty( $wpgraphql_menu_url ) ? $wpgraphql_menu_slug : 'tools.php';

		add_submenu_page(
			$parent_menu_slug,
			__( 'SnapWP', 'snapwp-helper' ),
			__( 'SnapWP', 'snapwp-helper' ),
			'edit_plugins',
			'snapwp-helper',
			[ $this, 'render_menu' ],
			999
		);
	}

	/**
	 * Render the admin menu.
	 */
	public function render_menu(): void {
		wp_enqueue_script( Assets::ADMIN_SCRIPT_HANDLE );

		?>
		<div class="wrap" id="snapwp-admin">
			<h1><?php esc_html_e( 'SnapWP', 'snapwp-helper' ); ?></h1>
			<p><?php esc_html_e( 'Welcome to SnapWP.', 'snapwp-helper' ); ?></p>
		</div>
		<?php
	}
}
