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
					ob_end_clean();

					// Get the list of enqueued scripts.
					$enqueued_scripts = $wp_scripts->queue ?? [];

					$queue = $this->flatten_enqueued_assets_list( $enqueued_scripts, $wp_scripts );

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
	 * Get the handles of all scripts enqueued for a given content node
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
