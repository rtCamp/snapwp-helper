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

		$env_file_content = snapwp_helper_get_env_content();

		if ( is_wp_error( $env_file_content ) ) {
			$env_file_content = '';
		}

		?>
		<div class="wrap" id="snapwp-admin">
			<h2><?php esc_html_e( 'SnapWP', 'snapwp-helper' ); ?></h2>
			<h3><?php esc_html_e( 'Local Installation Guide for Frontend', 'snapwp-helper' ); ?></h3>

			<div>
				<p>
					<?php esc_html_e( 'To view your headless site locally, you need to set up a localhost environment.', 'snapwp-helper' ); ?>
				</p>
			</div>

			<div>
				<p><?php esc_html_e( '1. Clone the Repository.', 'snapwp-helper' ); ?></p>
				<div>
					<p><?php esc_html_e( 'Clone the repository to your local machine.', 'snapwp-helper' ); ?></p>
					<code>git clone https://github.com/rtCamp/headless.git</code>
				</div>
			</div>

			<div>
				<p><?php esc_html_e( '2. Create an Environment File.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php esc_html_e( 'Navigate to `./frontend/examples/nextjs/starter/`, create a new `.env` file, and paste in the following variables. Then, uncomment & update the NEXT_URL variable as needed.', 'snapwp-helper' ); ?>
					</p>
					<code style="display: block; white-space: pre-wrap;"><?php echo esc_html( trim( str_replace( '\n', "\n", $env_file_content ) ) ); ?></code>
				</div>
			</div>

			<div>
				<p><?php esc_html_e( '3. Scaffold your new Frontend.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php
							esc_html_e( 'Navigate to `./frontend` and run the following command.', 'snapwp-helper' );
						?>
					</p>
					<code>npm run snapwp</code>
				</div>
			</div>

			<div>
				<p>All Set!</p>
				<div>
					<p>
						<?php esc_html_e( 'You are now ready to view your headless site locally!', 'snapwp-helper' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Open your new directory, run `npm run dev` or `npm run build && npm run start` and visit the `NEXT_URL` from `.env` (updated in Step 2), in your browser to see your new headless site.', 'snapwp-helper' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}
