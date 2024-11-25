<?php
/**
 * RenderedTemplate Model class
 *
 * @package SnapWP\Helper\Modules\GraphQL\Model
 */

namespace SnapWP\Helper\Modules\GraphQL\Model;

use GraphQLRelay\Relay;
use WPGraphQL\Model\Model;

/**
 * Class - RenderedTemplate
 *
 * @property string $id The ID of the block template.
 * @property ?string $content The content of the block template.
 * @property ?string $renderedHtml The rendered HTML of the block template.
 * @property ?string $uri The URI of the block template.
 * @property array<mixed> $enqueuedScriptsQueue The queue of enqueued scripts.
 * @property array<mixed> $enqueuedStylesheetsQueue The queue of enqueued stylesheets.
 */
class RenderedTemplate extends Model {
	/**
	 * {@inheritDoc}
	 *
	 * @var array{renderedHtml:string,uri:string,content:string}
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param array<mixed> $resolved_template_data The resolved template data.
	 *
	 * @throws \InvalidArgumentException If the required keys are not present in the resolved template data.
	 */
	public function __construct( array $resolved_template_data ) {
		// translators: %s: array key name.
		$error_message = __( 'The %s key is required to resolve instantiate the RenderedTemplate GraphQL model.', 'snapwp-helper' );

		if ( ! isset( $resolved_template_data['uri'] ) ) {
			throw new \InvalidArgumentException( esc_html( sprintf( $error_message, 'uri' ) ) );
		}

		if ( ! isset( $resolved_template_data['content'] ) ) {
			throw new \InvalidArgumentException( esc_html( sprintf( $error_message, 'content' ) ) );
		}

		if ( ! isset( $resolved_template_data['renderedHtml'] ) ) {
			throw new \InvalidArgumentException( esc_html( sprintf( $error_message, 'renderedHtml' ) ) );
		}

		$this->data = $resolved_template_data;

		parent::__construct();
	}

	/**
	 * Initializes the object
	 *
	 * @return void
	 */
	protected function init() {
		if ( empty( $this->fields ) ) {
			$this->fields = [
				'id'                       => function (): string {
					return Relay::toGlobalId( 'rendered-template', $this->data['uri'] );
				},
				'bodyClasses'              => static function (): ?array {
					$body_classes = get_body_class();
					return ! empty( $body_classes ) ? $body_classes : null;
				},
				'content'                  => fn (): ?string => ! empty( $this->data['content'] ) ? $this->data['content'] : null,
				'enqueuedScriptsQueue'     => function () {
					global $wp_scripts;

					// Simulate WP template rendering.
					ob_start();
					do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					// Add missing 'wp_footer' from WP lifecycle.
					wp_footer();
					ob_end_clean();

					// Get the list of enqueued scripts.
					$enqueued_scripts = $wp_scripts->queue ?? [];

					// @todo This is a temporary workaround for WPGraphQL's EnqueuedScriptConnectionResolver
					// which relies on $wp_scripts global. A proper solution will be implemented with a new
					// EnqueuedScript type and separate field from enqueuedScripts.
					// @see https://core.trac.wordpress.org/ticket/60597
					$script_modules = self::get_script_modules();
					if ( ! empty( $script_modules ) ) {
						$this->register_module_scripts( $script_modules );
					}

					$queue = $this->flatten_enqueued_assets_list( $enqueued_scripts, $wp_scripts );

					// Add script modules to the queue if available.
					if ( ! empty( $script_modules ) ) {
						foreach ( $script_modules as $module ) {
							// Sanitize the module ID into a valid WordPress handle.
							$handle = $module['id'];
							if ( ! in_array( $handle, $queue, true ) ) {
								$queue[] = $handle;
							}
						}
					}

					// Reset the scripts queue to avoid conflicts with other queries.
					$wp_scripts->reset();
					$wp_scripts->queue = [];

					return $queue;
				},
				'enqueuedStylesheetsQueue' => function () {
					global $wp_styles;

					// Prevent possible side effects printed to the output buffer.
					ob_start();
					do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					ob_end_clean();

					// Get the list of enqueued styles.
					$enqueued_styles = $wp_styles->queue ?? [];

					// Use the existing flatten_enqueued_assets_list method.
					$queue = $this->flatten_enqueued_assets_list( $enqueued_styles, $wp_styles );

					// Reset the styles queue to avoid conflicts with other queries.
					$wp_styles->reset();
					$wp_styles->queue = [];

					return $queue;
				},
				'renderedHtml'             => fn (): ?string => ! empty( $this->data['renderedHtml'] ) ? $this->data['renderedHtml'] : null,
				'uri'                      => fn (): ?string => ! empty( $this->data['uri'] ) ? $this->data['uri'] : null,
			];
		}
	}

	/**
	 * Get script modules registered with WordPress.
	 *
	 * This includes modules from the Interactivity API and other sources.
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/src/wp-includes/script-modules.php
	 *
	 * @return array<string,array{
	 *   id: string,
	 *   src: string,
	 *   version: string|false|null,
	 *   dependencies: list<string>,
	 *   dependents: list<string>,
	 * }>|null
	 */
	protected static function get_script_modules(): ?array {
		// Check for WP 6.5+ compatibility.
		if ( ! function_exists( 'wp_script_modules' ) ) {
			return null;
		}

		$modules = wp_script_modules();

		try {
			$reflector = new \ReflectionClass( $modules );

			// Get required methods.
			$get_marked_for_enqueue = $reflector->getMethod( 'get_marked_for_enqueue' );
			$get_dependencies       = $reflector->getMethod( 'get_dependencies' );
			$get_src                = $reflector->getMethod( 'get_src' );

			// Make methods accessible.
			$get_marked_for_enqueue->setAccessible( true );
			$get_dependencies->setAccessible( true );
			$get_src->setAccessible( true );

			// Get enqueued modules.
			$enqueued = $get_marked_for_enqueue->invoke( $modules );

			// Get dependencies for enqueued modules.
			$deps = $get_dependencies->invoke( $modules, array_keys( $enqueued ) );

			// Merge enqueued modules and their dependencies.
			$all_modules = array_merge( $enqueued, $deps );

			$sources = [];
			foreach ( $all_modules as $id => $module ) {
				$src                 = $get_src->invoke( $modules, $id );
				$script_dependencies = $get_dependencies->invoke( $modules, [ $id ] );

				// Ensure dependencies are always an array of strings.
				$dependencies = array_map( 'strval', array_keys( $script_dependencies ) );

				$dependents = [];
				foreach ( $all_modules as $dep_id => $dep ) {
					foreach ( $dep['dependencies'] as $dependency ) {
						if ( $dependency['id'] === $id ) {
							$dependents[] = strval( $dep_id ); // Ensure dependents are strings.
						}
					}
				}

				$sources[ $id ] = [
					'id'           => strval( $id ), // Ensure the ID is a string.
					'src'          => $src,
					'version'      => $module['version'],
					'dependencies' => $dependencies,
					'dependents'   => $dependents,
				];
			}

			// Reset method accessibility.
			$get_marked_for_enqueue->setAccessible( false );
			$get_dependencies->setAccessible( false );
			$get_src->setAccessible( false );

			return $sources;
		} catch ( \ReflectionException $e ) {
			graphql_debug( $e->getMessage() );
			return null;
		}
	}

	/**
	 * Registers module scripts with WordPress.
	 *
	 * @param array<string,array{id:string,src:string,version:string|false|null,dependencies:string[],dependents:string[]}> $modules Array of script modules to register.
	 */
	protected function register_module_scripts( array $modules ): void {
		global $wp_scripts;

		foreach ( $modules as $module ) {
			$handle = $module['id'];

			// Skip if already registered.
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				continue;
			}

			// Convert script handles into an array.
			$deps = [];
			foreach ( $module['dependencies'] as $dep ) {
				$deps[] = $dep;
			}

			// Register the script.
			wp_register_script(
				$handle,
				$module['src'],
				$deps,
				$module['version'] ?: null,
				true // in footer.
			);

			// Ensure it's marked as enqueued.
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Get the handles of all scripts enqueued for a given content node.
	 *
	 * @param array<string,string> $queue            List of scripts for a given content node.
	 * @param \WP_Dependencies     $wp_dependencies  A Global assets object.
	 *
	 * @return array<string>
	 */
	public function flatten_enqueued_assets_list( array $queue, \WP_Dependencies $wp_dependencies ): array {
		$registered_assets = $wp_dependencies->registered;
		$handles           = [];

		foreach ( $queue as $handle ) {

			// If the script is not registered, skip to the next iteration.
			if ( empty( $registered_assets[ $handle ] ) ) {
				continue;
			}

			// Retrieve the registered script object from the queue.
			/** @var \_WP_Dependency $script */
			$script = $registered_assets[ $handle ];

			// Add the script handle to the list of handles.
			$handles[] = $script->handle;

			// Recursively get the dependencies of the current script.
			$dependencies = self::flatten_enqueued_assets_list( $script->deps, $wp_dependencies );
			if ( empty( $dependencies ) ) {
				continue;
			}

			array_unshift( $handles, ...$dependencies );
		}

		// Remove duplicates and re-index the array of handles before returning it.
		return array_values( array_unique( $handles ) );
	}
}
