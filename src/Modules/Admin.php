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
use SnapWP\Helper\Modules\GraphQL\Data\IntrospectionToken;

/**
 * Class - Admin
 */
class Admin implements Module {
	/**
	 * The capability required to access the SnapWP Helper screen and do admin-related actions.
	 *
	 * Filtered by `snapwp_helper/admin/capability`.
	 *
	 * @var string
	 */
	private string $capability;

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
		add_action( 'current_screen', [ $this, 'handle_token_regeneration' ] );
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
			$this->get_capability(), // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- It's user filterable.
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

		$variables = snapwp_helper_get_env_variables();

		// Display an error message if the variables could not be loaded.
		if ( is_wp_error( $variables ) ) {
			wp_admin_notice(
				sprintf(
					// translators: %s is the error message.
					__( 'Unable to load environment variables: %s', 'snapwp-helper' ),
					$variables->get_error_message()
				),
				[
					'type' => 'error',
				]
			);
		}

		$env_file_content = snapwp_helper_get_env_content();

		if ( is_wp_error( $env_file_content ) ) {
			$error_message = sprintf(
				// translators: %s is the error message.
				__( 'Unable to generate the `.env` file content: %s', 'snapwp-helper' ),
				$env_file_content->get_error_message()
			);
			wp_admin_notice(
				$error_message,
				[
					'type' => 'error',
				]
			);

			// translators: %s is the error message.
			$env_file_content = sprintf( __( 'Error: %s', 'snapwp-helper' ), $error_message );
		}

		?>
		<div class="wrap" id="snapwp-admin">
			<h2><?php esc_html_e( 'SnapWP', 'snapwp-helper' ); ?></h2>

			<?php if ( is_array( $variables ) ) : ?>
				<h3><?php esc_html_e( 'Environment Variables', 'snapwp-helper' ); ?></h3>
				<p><?php esc_html_e( 'These `.env` variables are used by SnapWP\'s frontend to connect with your WordPress backend.', 'snapwp-helper', ); ?></p>
				<p>
					<?php
					printf(
						// translators: %s is the hyperlink to the 'Config API & Environment Variables' doc.
						esc_html__( 'Need help setting up environment variables? Refer to the %s doc.', 'snapwp-helper' ),
						'<a href="https://github.com/rtcamp/snapwp/blob/develop/docs/config-api.md#env-variables" target="_blank">' . esc_html__( 'Config API and Environment Variables', 'snapwp-helper' ) . '</a>'
					);
					?>
				</p>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Variable', 'snapwp-helper' ); ?></th>
							<th><?php esc_html_e( 'Value', 'snapwp-helper' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $variables as $key => $value ) : ?>
							<?php
							if ( in_array( $key, [ 'NODE_TLS_REJECT_UNAUTHORIZED', 'NEXT_PUBLIC_URL' ], true ) ) {
								continue; }
							?>
							<tr>
								<td><?php echo esc_html( $key ); ?></td>
								<td>
									<?php echo wp_kses_post( sprintf( '<code>%s</code>', $value ) ); ?>

									<?php if ( 'INTROSPECTION_TOKEN' === $key ) : ?>
										<form method="POST">
											<?php wp_nonce_field( 'regenerate_token_action', 'regenerate_token_nonce' ); ?>
											<input type="submit" name="regenerate_token" class="button-primary" value="<?php esc_attr_e( 'Regenerate Token', 'snapwp-helper' ); ?>">
										</form>
										
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3><?php esc_html_e( 'SnapWP Frontend Setup Guide', 'snapwp-helper' ); ?></h3>

			<p>
				<?php
					printf(
						// translators: %s is the hyperlink to the 'Frontend Setup' section in the 'Getting Started' doc.
						esc_html__( 'To get started with using SnapWP locally, you can read the %s or can follow the steps below:', 'snapwp-helper' ),
						'<a href="https://github.com/rtcamp/snapwp/blob/develop/docs/getting-started.md#frontend-setup" target="_blank">' . esc_html__( 'SnapWP Frontend Setup Guide', 'snapwp-helper' ) . '</a>'
					);
				?>
			</p>

			<ol>
				<li>
					<p><?php esc_html_e( 'Scaffold a decoupled frontend.', 'snapwp-helper' ); ?></p>
					<p><?php esc_html_e( 'Run the following command to generate a decoupled frontend.', 'snapwp-helper' ); ?></p>
					<code>npx snapwp</code>
				</li>

				<li>
					<!-- @todo: Required .env variables should be passed to the scaffold command. -->
					<p><?php esc_html_e( 'Create an Environment File.', 'snapwp-helper' ); ?></p>
					<p>
						<?php esc_html_e( 'Navigate to your newly-created frontend application, and update the `.env` file with the following variables:', 'snapwp-helper' ); ?>
					</p>
					<code style="display: block; white-space: pre-wrap;"><?php echo esc_html( trim( str_replace( '\n', "\n", $env_file_content ) ) ); ?></code>
					<p>
						<?php
						printf(
							// translators: %s is the command, wrapped in code tags.
							esc_html__( 'Then update the %s variable with the URL for your headless frontend.', 'snapwp-helper' ),
							'<code>NEXT_PUBLIC_URL</code>'
						);
						?>
					</p>
				</li>

				<li>
					<p><?php esc_html_e( 'Start the SnapWP frontend.', 'snapwp-helper' ); ?></p>
					<p>
						<?php esc_html_e( 'You are now ready to view your headless site locally!', 'snapwp-helper' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Follow these steps to start your headless WordPress app:', 'snapwp-helper' ); ?>
					</p>
					<ol style="list-style-type: lower-roman;">
						<li><?php esc_html_e( 'Navigate to the newly created app.', 'snapwp-helper' ); ?></li>
						<li>
							<?php
								printf(
									// Translators: %s is the command, wrapped in code tags.
									esc_html__( 'Run %s', 'snapwp-helper' ),
									'<code>npm install</code>',
								);
							?>
						</li>
						<li>
							<p>
								<?php
									printf(
										// Translators: %1$s and %2$s are the commands, wrapped in code tags.
										esc_html__( 'Run %1$s (for development) or %2$s (for production).', 'snapwp-helper' ),
										'<code>npm run dev</code>',
										'<code>npm run build && npm run start</code>'
									);
								?>
							</p>
						</li>
						<li><?php esc_html_e( 'Visit the `NEXT_PUBLIC_URL` from `.env` (updated in Step 2), in your browser to see SnapWP in action!', 'snapwp-helper' ); ?></li>
					</ol>
				</li>
			</ol>

			<p></p>

			<p>
				<?php
					printf(
						// translators: %s is the hyperlink to the 'Getting Started' doc.
						esc_html__( 'For detailed setup instructions, please refer to the %s.', 'snapwp-helper' ),
						'<a href="https://github.com/rtcamp/snapwp/blob/develop/docs/getting-started.md#getting-started" target="_blank">' . esc_html__( 'Getting Started Guide', 'snapwp-helper' ) . '</a>'
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle the token regeneration.
	 */
	public function handle_token_regeneration(): void {
		$current_screen = get_current_screen();

		// Check if the current screen is null or doesn't match our admin screen.
		if ( ! $current_screen || 'graphql_page_snapwp-helper' !== $current_screen->id ) {
			return;
		}

		if ( ! isset( $_POST['regenerate_token'] ) ) {
			// Nothing to process if the regenerate_token action isn't triggered.
			return;
		}

		if ( ! isset( $_POST['regenerate_token_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['regenerate_token_nonce'] ), 'regenerate_token_action' ) ) {
			wp_admin_notice(
				__( 'Could not regenerate the introspection token: nonce verification failed.', 'snapwp-helper' ),
				[
					'type' => 'error',
				]
			);
			return;
		}

		if ( ! current_user_can( $this->get_capability() ) ) { // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- It's user filterable.
			wp_admin_notice(
				__( 'Could not regenerate the introspection token: insufficient permissions.', 'snapwp-helper' ),
				[
					'type' => 'error',
				]
			);
			return;
		}

		// Generate a new introspection token.
		$introspection_token = IntrospectionToken::generate_token();

		// Check is WP_Error.
		if ( is_wp_error( $introspection_token ) ) {
			wp_admin_notice(
				sprintf(
					// translators: %s is the error message.
					__( 'Could not regenerate introspection token: %s', 'snapwp-helper' ),
					$introspection_token->get_error_message()
				),
				[
					'type' => 'error',
				]
			);
			return;
		}

		wp_admin_notice(
			__( 'Introspection token regenerated successfully. Please make sure to update your `.env` file.', 'snapwp-helper' ),
			[
				'type' => 'success',
			]
		);
	}

	/**
	 * Get the capability required to access the SnapWP Helper screen and do admin-related actions.
	 *
	 * @uses snapwp_helper/admin/capability filter
	 */
	private function get_capability(): string {
		if ( ! isset( $this->capability ) ) {
			/**
			 * Filter the capability required to access the SnapWP Helper admin screen and do admin-related actions.
			 *
			 * Defaults to `manage_options`.
			 *
			 * @param string $capability The capability required to access the SnapWP Helper admin screen and do admin-related actions.
			 */
			$this->capability = apply_filters( 'snapwp_helper/admin/capability', 'manage_options' );
		}

		return $this->capability;
	}
}
