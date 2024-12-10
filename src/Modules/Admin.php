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
			<h2><?php esc_html_e( 'SnapWP', 'snapwp-helper' ); ?></h2>
			<h3><?php esc_html_e( 'Local Installation Guide.', 'snapwp-helper' ); ?></h3>

			<div class="mb-3">
				<p><?php esc_html_e( '1. Clone the Repository.', 'snapwp-helper' ); ?></p>
				<div>
					<p><?php esc_html_e( 'Clone the repository to your local machine under wp-content/plugins directory.', 'snapwp-helper' ); ?></p>
					<code>git clone https://github.com/rtCamp/headless.git</code>
				</div>
			</div>

			<div class="mb-3">
				<p><?php esc_html_e( '2. Create an Environment File.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php
						echo esc_html(
						// Translators: %s is the directory path of snapwp-helper plugin.
							sprintf( __( 'Navigate to `%s`, create a new `.env` file, and paste in the following:', 'snapwp-helper' ), SNAPWP_HELPER_PLUGIN_DIR ),
						);
						?>
					</p>
					<code style="display: block; white-space: pre-wrap;">PLUGIN_SLUG=snapwp-helper

# Configure these to match your existing testing environment or the one you want to create with Docker.
## Usually, these values should match the ones in the `wp-config.php` file.
## If using LocalWP, you can find the values in the LocalWP app under the site's settings.

# NOTE: Codeception will modify the database during testing. If you want to preserve your local data use a separate database.
DB_NAME=<?php echo esc_html( defined( 'DB_NAME' ) ? DB_NAME : 'WordPress' ); ?>

DB_HOST=<?php echo esc_html( defined( 'DB_HOST' ) ? DB_HOST : '127.0.0.1' ); ?>

# Make sure the user has the necessary permissions for the database. E.g. `GRANT ALL PRIVILEGES ON WordPress.* TO '<?php echo esc_html( defined( 'DB_USER' ) ? DB_USER : 'root' ); ?>'@'<?php echo esc_html( defined( 'DB_HOST' ) ? DB_HOST : '127.0.0.1' ); ?>';`
DB_USER=<?php echo esc_html( defined( 'DB_USER' ) ? DB_USER : 'root' ); ?>

DB_PASSWORD=<?php echo esc_html( defined( 'DB_PASSWORD' ) ? DB_PASSWORD : 'password' ); ?>

# Can be found by connecting to the database and running `SHOW GLOBAL VARIABLES LIKE 'PORT';`
DB_PORT=3306

# The local path to the WordPress root directory, the one containing the wp-load.php file.
## This can be a relative path from the directory that contains the codeception.yml file, or an absolute path.
WORDPRESS_ROOT_DIR="<?php echo esc_html( get_home_path() ); ?>"

# This table prefix used by the WordPress site, and in Acceptance tests.
WORDPRESS_TABLE_PREFIX=wp_

# The URL and domain of the WordPress site, and in Acceptance tests.
## If the port is in use, you can change it to a different port.
WORDPRESS_URL=<?php echo esc_url( home_url() ); ?>

WORDPRESS_DOMAIN=<?php echo esc_url( preg_replace( '(^https?://)', '', home_url() ) ); ?>

WORDPRESS_ADMIN_PATH=/wp-admin

# The username and password of the administrator user of the WordPress site, and in Acceptance tests.
WORDPRESS_ADMIN_USER=
WORDPRESS_ADMIN_PASSWORD=

# Additional variables for the WordPress installation.
SITE_TITLE='<?php echo esc_html( get_bloginfo( 'name' ) ); ?>'

WORDPRESS_DEBUG=1
WORDPRESS_CONFIG_EXTRA="define( 'SCRIPT_DEBUG', true ); define( 'FS_METHOD', 'direct' );"

# Tests will require a MySQL database to run.
# Do not use a database that contains important data!
WORDPRESS_DB_HOST=${DB_HOST}
WORDPRESS_DB_USER=${DB_USER}
WORDPRESS_DB_PASSWORD=${DB_PASSWORD}
WORDPRESS_DB_NAME=${DB_NAME}
WORDPRESS_DB_PORT=${DB_PORT}

# Integration tests will use these variables instead.
# By default this is the same as WordPress
TEST_DB_HOST=${WORDPRESS_DB_HOST}
TEST_DB_USER=${WORDPRESS_DB_USER}
TEST_DB_PASSWORD=${WORDPRESS_DB_PASSWORD}
TEST_DB_NAME=${WORDPRESS_DB_NAME}
TEST_DB_PORT=${WORDPRESS_DB_PORT}
# The Integration suite will use this table prefix for the WordPress tables.
TEST_TABLE_PREFIX=test_

# The DSN used by Acceptance tests.
TEST_DB_DSN="mysql:host=${TEST_DB_HOST};port=${TEST_DB_PORT};dbname=${TEST_DB_NAME}"</code>
				</div>
			</div>

			<div class="mb-3">
				<p><?php esc_html_e( '3. Update Username/Password in `.env`.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php
							esc_html_e( 'Add WordPress admin username & password to WORDPRESS_ADMIN_USER & WORDPRESS_ADMIN_PASSWORD constants respectively.', 'snapwp-helper' );
						?>
					</p>
				</div>
			</div>

			<div class="mb-3">
				<p><?php esc_html_e( '4. Install Dependencies.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php
						echo esc_html(
						// Translators: %s is the directory path of snapwp-helper plugin.
							sprintf( __( 'Navigate to `%s` and run the following command.', 'snapwp-helper' ), SNAPWP_HELPER_PLUGIN_DIR ),
						);
						?>
					</p>
					<code>nvm use && npm run install-local-deps</code>
				</div>
			</div>

			<div class="mb-3">
				<p><?php esc_html_e( '5. Install Test Environment.', 'snapwp-helper' ); ?></p>
				<div>
					<p>
						<?php
						echo esc_html(
							// Translators: %s is the directory path of snapwp-helper plugin.
							sprintf( __( 'Navigate to `%s` and run the following command.', 'snapwp-helper' ), SNAPWP_HELPER_PLUGIN_DIR ),
						);
						?>
					</p>
					<code>nvm use && npm run install-test-env</code>
				</div>
			</div>

			<div class="mb-3">
				<p>All Set!</p>
			</div>
		</div>
		<?php
	}
}
