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

		add_action( 'admin_init', [ $this, 'handle_token_regeneration' ] );
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
							if ( in_array( $key, [ 'NODE_TLS_REJECT_UNAUTHORIZED', 'NEXT_URL' ], true ) ) {
								continue; }
							?>
							<tr>
								<td><?php echo esc_html( $key ); ?></td>
								<td><?php echo esc_html( $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Regenerate Introspection Token', 'snapwp-helper' ); ?></h3>
			<p>
				<?php esc_html_e( 'The introspection token is used by SnapWP\'s frontend to authenticate with your WordPress backend.', 'snapwp-helper' ); ?>
			</p>
			<form method="POST">
				<?php wp_nonce_field( 'regenerate_token_action', 'regenerate_token_nonce' ); ?>
				<input type="submit" name="regenerate_token" class="button-primary" value="<?php esc_attr_e( 'Regenerate Token', 'snapwp-helper' ); ?>">
			</form>

			<h3><?php esc_html_e( 'SnapWP Frontend Setup Guide', 'snapwp-helper' ); ?></h3>

			<p>
				<?php esc_html_e( 'To get started with using SnapWP locally, follow the steps below:', 'snapwp-helper' ); ?>
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
							esc_html__( 'Then update the %s variable with the URL of your WordPress site.', 'snapwp-helper' ),
							'<code>NEXT_URL</code>'
						);
						?>
					</p>
				</li>

				<li>
					<p><?php esc_html_e( 'Start the SnapWP frontend.', 'snapwp-helper' ); ?></p>
					<p>
						<?php esc_html_e( 'You are now ready to view your headless site locally!', 'snapwp-helper' ); ?>
					<p>
					<p>
						<?php
							printf(
								// Translators: %1$s and %2$s are the commands, wrapped in code tags.
								esc_html__( 'Run %1$s (for development) or %2$s (for production) and visit the `NEXT_URL` from `.env` (updated in Step 2), in your browser to see SnapWP in action!.', 'snapwp-helper' ),
								'<code>npm run dev</code>',
								'<code>npm run build && npm run start</code>'
							);
						?>
				</li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Handle the token regeneration.
	 */
	public function handle_token_regeneration(): void {
		$nonce = isset( $_POST['regenerate_token_nonce'] ) ? sanitize_text_field( $_POST['regenerate_token_nonce'] ) : '';

		if ( isset( $_POST['regenerate_token'] ) && ! empty( $nonce ) && wp_verify_nonce( $nonce, 'regenerate_token_action' ) ) {
			IntrospectionToken::get_token() ?? '';

			add_action(
				'admin_notices',
				static function () {
					echo '<div class="updated"><p>' . esc_html__( 'Introspection token regenerated successfully.', 'snapwp-helper' ) . '</p></div>';
				}
			);
		} elseif ( isset( $_POST['regenerate_token'] ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="error"><p>' . esc_html__( 'Nonce verification failed.', 'snapwp-helper' ) . '</p></div>';
				}
			);
		}
	}
}
