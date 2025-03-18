<?php
/**
 * Tests the RenderedTemplate class stylesheet enqueuing functionality.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Tests the stylesheet enqueuing functionality of RenderedTemplate.
 */
class EnqueuedStylesheetsTest extends IntegrationTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure WordPress has initialized the style queue.
		if ( ! isset( $GLOBALS['wp_styles'] ) ) {
			$GLOBALS['wp_styles'] = new \WP_Styles();
		}

		// Register stylesheets used across tests.
		wp_register_style( 'test-main-style', 'https://decoupled.local/test-main.css', [], '1.0' );
		wp_register_style( 'test-additional-style', 'https://decoupled.local/test-additional.css', [], '1.0' );
		wp_register_style( 'test-dependency-style', 'https://decoupled.local/test-dependency.css', [], '1.0' );
		wp_register_style( 'test-dependent-style', 'https://decoupled.local/test-dependent.css', [ 'test-dependency-style' ], '1.0' );
		wp_register_style( 'test-block-dependency', 'https://decoupled.local/test-block-dependency.css', [], '1.0' );
		wp_register_style( 'test-block-style', 'https://decoupled.local/test-block-style.css', [ 'test-block-dependency' ], '1.0' );

		// Reset the queue.
		$GLOBALS['wp_styles']->queue = [];

		// Clear the schema.
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		remove_all_actions( 'wp_enqueue_scripts' );

		// Reset the queue.
		if ( isset( $GLOBALS['wp_styles'] ) ) {
			$GLOBALS['wp_styles']->queue = [];
		}

		// Clear the schema.
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Helper to execute a GraphQL query with the post URL.
	 */
	private function query(): string {
		return '
            query GetCurrentScreen($uri: String!) {
                templateByUri(uri: $uri) {
                    enqueuedStylesheets(first: 100) {
                        nodes {
                            handle
                        }
                    }
                }
            }
        ';
	}

	/**
	 * Test that basic stylesheet enqueuing works correctly.
	 */
	public function testBasicStylesheetEnqueuing(): void {
		// Create a post.
		$post_id = $this->factory()->post->create(
			[
				'post_title' => 'Test Post with Basic Style',
			]
		);

		// Add a hook to enqueue a stylesheet.
		add_action(
			'wp_enqueue_scripts',
			static function () use ( $post_id ) {
				if ( get_the_ID() !== $post_id ) {
					return;
				}
				wp_enqueue_style( 'test-main-style' );
			}
		);

		// Get the permalink/URL for the created post.
		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query with the post URL.
		$query     = $this->query();
		$variables = [
			'uri' => $post_url,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedStylesheets']['nodes'], 'handle' );

		// Assert that the stylesheet is returned in the enqueuedStylesheets.
		$this->assertContains( 'test-main-style', $handles );

		// Assert only the expected stylesheet is enqueued.
		$this->assertNoUnexpectedStylesheetsEnqueued(
			$actual,
			[
				'test-additional-style',
				'test-dependency-style',
				'test-dependent-style',
			]
		);
	}

	/**
	 * Test that multiple stylesheets are enqueued correctly.
	 */
	public function testMultipleStylesheetEnqueuing(): void {
		// Create a post.
		$post_id = $this->factory()->post->create(
			[
				'post_title' => 'Test Post with Multiple Styles',
			]
		);

		// Add a hook to enqueue multiple stylesheets.
		add_action(
			'wp_enqueue_scripts',
			static function () use ( $post_id ) {
				if ( get_the_ID() !== $post_id ) {
					return;
				}
				wp_enqueue_style( 'test-main-style' );
				wp_enqueue_style( 'test-additional-style' );
			}
		);

		// Get the permalink/URL for the created post.
		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query.
		$query     = $this->query();
		$variables = [
			'uri' => $post_url,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedStylesheets']['nodes'], 'handle' );

		// Assert that both stylesheets are returned.
		$this->assertContains( 'test-main-style', $handles );
		$this->assertContains( 'test-additional-style', $handles );

		// Assert only the expected stylesheets are enqueued.
		$this->assertNoUnexpectedStylesheetsEnqueued(
			$actual,
			[
				'test-dependency-style',
				'test-dependent-style',
			]
		);
	}

	/**
	 * Test that stylesheets with dependencies are enqueued properly.
	 */
	public function testStylesheetDependencies(): void {
		// Create a post.
		$post_id = $this->factory()->post->create(
			[
				'post_title' => 'Test Post with Style Dependencies',
			]
		);

		// Add a hook to enqueue a stylesheet with dependencies.
		add_action(
			'wp_enqueue_scripts',
			static function () use ( $post_id ) {
				if ( get_the_ID() !== $post_id ) {
					return;
				}
				wp_enqueue_style( 'test-dependent-style' );
			}
		);

		// Get the permalink/URL for the created post.
		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query.
		$query     = $this->query();
		$variables = [
			'uri' => $post_url,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedStylesheets']['nodes'], 'handle' );

		// Assert that both the dependent stylesheet and its dependency are returned.
		$this->assertContains( 'test-dependency-style', $handles );
		$this->assertContains( 'test-dependent-style', $handles );

		// Assert that the dependency stylesheet appears before the dependent stylesheet.
		$this->assertLessThan(
			array_search( 'test-dependent-style', $handles ),
			array_search( 'test-dependency-style', $handles ),
			'Dependency stylesheet should be enqueued before dependent stylesheet'
		);

		// Assert only the expected stylesheets are enqueued.
		$this->assertNoUnexpectedStylesheetsEnqueued(
			$actual,
			[
				'test-main-style',
				'test-additional-style',
			]
		);
	}

	/**
	 * Test that block styles with dependencies are enqueued correctly.
	 */
	public function testBlockStyleWithDependency(): void {
		// Create a post with a block that would trigger the style
		$post_id = $this->factory()->post->create(
			[
				'post_title'   => 'Test Post with Block Style',
				'post_content' => '<!-- wp:test/block-with-style /-->',
			]
		);

		// Register the block style
		add_action(
			'wp_enqueue_scripts',
			static function () use ( $post_id ) {
				if ( get_the_ID() !== $post_id ) {
					return;
				}

				// Simulate block style registration
				wp_enqueue_style( 'test-block-style' );

				// Register it as a block style
				wp_enqueue_block_style(
					'test/block-with-style',
					[
						'handle' => 'test-block-style',
					]
				);
			}
		);

		// Get the permalink for the created post
		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query
		$query     = $this->query();
		$variables = [
			'uri' => $post_url,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		// Assert no errors
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response
		$handles = array_column( $actual['data']['templateByUri']['enqueuedStylesheets']['nodes'], 'handle' );

		// Assert that both the block style and its dependency are returned
		$this->assertContains( 'test-block-dependency', $handles );
		$this->assertContains( 'test-block-style', $handles );

		// Assert that the dependency appears before the block style
		$this->assertLessThan(
			array_search( 'test-block-style', $handles ),
			array_search( 'test-block-dependency', $handles ),
			'Block style dependency should be enqueued before the block style'
		);

		// Assert only the expected stylesheets are enqueued
		$this->assertNoUnexpectedStylesheetsEnqueued(
			$actual,
			[
				'test-main-style',
				'test-additional-style',
				'test-dependency-style',
				'test-dependent-style',
			]
		);
	}

	/**
	 * Assert that none of the unwanted stylesheets are enqueued.
	 *
	 * @param array $response           The GraphQL response.
	 * @param array $unwanted_handles   The handles of the stylesheets that should not be enqueued.
	 */
	private function assertNoUnexpectedStylesheetsEnqueued( array $response, array $unwanted_handles ): void {
		$enqueued_handles = array_column( $response['data']['templateByUri']['enqueuedStylesheets']['nodes'], 'handle' );

		// Assert each unwanted handle is not in the enqueued handles.
		foreach ( $unwanted_handles as $handle ) {
			$this->assertNotContains( $handle, $enqueued_handles, "Unexpected stylesheet $handle was enqueued." );
		}
	}
}
